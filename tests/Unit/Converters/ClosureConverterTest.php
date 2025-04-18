<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Cortex\JsonSchema\Converters\ClosureConverter;

covers(ClosureConverter::class);

it('can create a schema from a closure', function (): void {
    $closure = function (string $name, array $fooArray, ?int $age = null): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'fooArray' => [
                'type' => 'array',
            ],
            'age' => [
                'type' => [
                    'integer',
                    'null',
                ],
                'default' => null,
            ],
        ],
        'required' => [
            'name',
            'fooArray',
        ],
    ]);
});

it('can create a schema from a closure with a string backed enum', function (): void {
    enum MyEnum: string
    {
        case A = 'a';
        case B = 'b';
    }

    $closure = function (MyEnum $myEnum, bool $foo = true): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'myEnum' => [
                'type' => 'string',
                'enum' => [
                    'a',
                    'b',
                ],
            ],
            'foo' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'required' => [
            'myEnum',
        ],
    ]);
});

it('can create a schema from a closure with an integer backed enum', function (): void {
    enum Status: int
    {
        case Draft = 0;
        case Published = 1;
        case Archived = 2;
    }

    /**
     * Do something with the status
     *
     * @param Status $status The status of the post
     */
    $closure = function (Status $status): void {};

    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'description' => 'Do something with the status',
        'properties' => [
            'status' => [
                'type' => 'integer',
                'description' => 'The status of the post',
                'enum' => [
                    0,
                    1,
                    2,
                ],
            ],
        ],
        'required' => [
            'status',
        ],
    ]);
});

it('throws an exception if the enum is not a backed enum', function (): void {
    enum StatusNoBackingType
    {
        case Draft;
        case Published;
        case Archived;
    }

    $closure = function (StatusNoBackingType $statusNoBackingType): void {};
    (new ClosureConverter($closure))->convert();
})->throws(
    SchemaException::class,
    'Enum type has no backing type: Cortex\JsonSchema\Tests\Unit\Converters\StatusNoBackingType',
);

it('can create a schema from a closure with a union type', function (): void {
    $closure = function (int|string $foo): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'foo' => [
                'type' => [
                    'string',
                    'integer',
                ],
            ],
        ],
        'required' => [
            'foo',
        ],
    ]);
});

it('can create a schema from a closure with a nullable union type', function (): void {
    $closure = function (int|string|null $foo): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'foo' => [
                'type' => [
                    'string',
                    'integer',
                    'null',
                ],
            ],
        ],
        'required' => [
            'foo',
        ],
    ]);
});

it('can create a schema from a closure with array type hints', function (): void {
    $closure = function (array $items, array $tags = ['default']): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'items' => [
                'type' => 'array',
            ],
            'tags' => [
                'type' => 'array',
                'default' => ['default'],
            ],
        ],
        'required' => [
            'items',
        ],
    ]);
});

it('can create a schema from a closure with mixed type', function (): void {
    $closure = function (mixed $data): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'data' => [
                'type' => [
                    'string',
                    'number',
                    'integer',
                    'boolean',
                    'object',
                    'array',
                    'null',
                ],
            ],
        ],
        'required' => [
            'data',
        ],
    ]);
});

it('can create a schema from a closure with object type', function (): void {
    $closure = function (object $data): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'data' => [
                'type' => 'object',
            ],
        ],
        'required' => [
            'data',
        ],
    ]);
});

it('can create a schema from a closure with float type', function (): void {
    $closure = function (float $amount = 0.0): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'amount' => [
                'type' => 'number',
                'default' => 0.0,
            ],
        ],
    ]);
});

it('can create a schema from a closure with default values', function (): void {
    $closure = function (
        string $name,
        int $age,
        ?string $email = null,
        array $tags = [],
        bool $active = true,
    ): void {};

    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'age' => [
                'type' => 'integer',
            ],
            'email' => [
                'type' => [
                    'string',
                    'null',
                ],
                'default' => null,
            ],
            'tags' => [
                'type' => 'array',
                'default' => [],
            ],
            'active' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
        'required' => [
            'name',
            'age',
        ],
    ]);
});

it('can create a schema from a closure with array type', function (): void {
    $closure = function (array $items = ['default']): void {};
    $objectSchema = (new ClosureConverter($closure))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'items' => [
                'type' => 'array',
                'default' => ['default'],
            ],
        ],
    ]);
});

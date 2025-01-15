<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Converters\FromClosure;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a schema from a closure', function (): void {
    $closure = function (string $name, array $fooArray, ?int $age = null): string {
        return 'foo';
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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
            ],
        ],
        'required' => ['name', 'fooArray'],
    ]);
});

it('can create a schema from a closure with an enum', function (): void {
    enum MyEnum: string
    {
        case A = 'a';
        case B = 'b';
    }

    $closure = function (MyEnum $myEnum, bool $foo = true): string {
        return 'foo';
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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
        'required' => ['myEnum'],
    ]);
});

it('can create a schema from a closure with a union type', function (): void {
    $closure = function (int|string $foo): string {
        return 'foo';
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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
        'required' => ['foo'],
    ]);
});

it('can create a schema from a closure with a backed enum', function (): void {
    enum Status: int
    {
        case Draft = 0;
        case Published = 1;
        case Archived = 2;
    }

    $closure = function (Status $status): void {
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'status' => [
                'type' => 'integer',
                'enum' => [0, 1, 2],
            ],
        ],
        'required' => ['status'],
    ]);
});

it('can create a schema from a closure with a nullable union type', function (): void {
    $closure = function (int|string|null $foo): string {
        return 'foo';
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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
        'required' => ['foo'],
    ]);
});

it('can create a schema from a closure with array type hints', function (): void {
    $closure = function (array $items, array $tags = ['default']): void {
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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
        'required' => ['items'],
    ]);
});

it('can create a schema from a closure with mixed type', function (): void {
    $closure = function (mixed $data): void {
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'data' => [
                'type' => ['string', 'number', 'integer', 'boolean', 'array', 'object', 'null'],
            ],
        ],
        'required' => ['data'],
    ]);
})->todo();

it('can create a schema from a closure with object type', function (): void {
    $closure = function (object $data): void {
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'data' => [
                'type' => 'object',
            ],
        ],
        'required' => ['data'],
    ]);
});

it('can create a schema from a closure with float type', function (): void {
    $closure = function (float $amount = 0.0): void {
    };

    $schema = FromClosure::convert($closure);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
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

it('validates data against schema created from closure', function (): void {
    $closure = function (
        string $name,
        int $age,
        ?string $email = null,
        array $tags = [],
        bool $active = true,
    ): void {
    };

    $schema = FromClosure::convert($closure);

    // Check schema structure
    $schemaArray = $schema->toArray();
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray['properties'])->toHaveKeys(['name', 'age', 'email', 'tags', 'active']);
    expect($schemaArray['required'])->toBe(['name', 'age']);

    // Valid data
    expect(fn() => $schema->validate([
        'name' => 'John Doe',
        'age' => 30,
        'email' => 'john@example.com',
        'tags' => ['developer', 'php'],
        'active' => true,
    ]))->not->toThrow(SchemaException::class);

    // Missing required properties
    expect(fn() => $schema->validate([
        'email' => 'john@example.com',
    ]))->toThrow(
        SchemaException::class,
        'The required properties (name) are missing'
    );

    // Invalid types
    expect(fn() => $schema->validate([
        'name' => 123,
        'age' => 'not-a-number',
    ]))->toThrow(
        SchemaException::class,
        'The properties must match schema: name'
    );
});

it('validates data against schema created from closure with enum', function (): void {
    enum Status: string {
        case Draft = 'draft';
        case Published = 'published';
        case Archived = 'archived';
    }

    $closure = function (Status $status): void {
    };

    $schema = FromClosure::convert($closure);

    // Valid values
    expect(fn() => $schema->validate(['status' => 'draft']))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['status' => 'published']))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['status' => 'archived']))->not->toThrow(SchemaException::class);

    // Invalid values
    expect(fn() => $schema->validate(['status' => 'invalid']))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['status' => 123]))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['status' => null]))->toThrow(SchemaException::class);
})->todo();

it('validates data against schema created from closure with union type', function (): void {
    $closure = function (int|string|null $id): void {
    };

    $schema = FromClosure::convert($closure);

    // Valid values
    expect(fn() => $schema->validate(['id' => 123]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['id' => 'abc123']))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['id' => null]))->not->toThrow(SchemaException::class);

    // Invalid values
    expect(fn() => $schema->validate(['id' => true]))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['id' => []]))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['id' => new \stdClass()]))->toThrow(SchemaException::class);
});

it('validates data against schema created from closure with array type', function (): void {
    $closure = function (array $items = ['default']): void {
    };

    $schema = FromClosure::convert($closure);

    // Check schema structure
    $schemaArray = $schema->toArray();
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray['properties']['items'])->toHaveKey('type', 'array');
    expect($schemaArray['properties']['items'])->toHaveKey('default', ['default']);

    // Valid values
    expect(fn() => $schema->validate(['items' => ['a', 'b', 'c']]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['items' => [1, 2, 3]]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['items' => []]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['items' => ['default']]))->not->toThrow(SchemaException::class);

    // Invalid values
    expect(fn() => $schema->validate(['items' => 'not-an-array']))->toThrow(
        SchemaException::class,
        'The properties must match schema: items'
    );
    expect(fn() => $schema->validate(['items' => 123]))->toThrow(
        SchemaException::class,
        'The properties must match schema: items'
    );
    expect(fn() => $schema->validate(['items' => null]))->toThrow(
        SchemaException::class,
        'The properties must match schema: items'
    );
});

it('validates data against schema created from closure with object type', function (): void {
    $closure = function (object $data): void {
    };

    $schema = FromClosure::convert($closure);

    // Valid values
    expect(fn() => $schema->validate(['data' => new \stdClass()]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['data' => ['foo' => 'bar']]))->not->toThrow(SchemaException::class);

    // Invalid values
    expect(fn() => $schema->validate(['data' => 'not-an-object']))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['data' => 123]))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(['data' => null]))->toThrow(SchemaException::class);
});

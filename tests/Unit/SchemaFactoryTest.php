<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;

covers(Schema::class);

it('can create different schema types', function (): void {
    // Test array schema creation
    expect(Schema::array('items'))->toBeInstanceOf(ArraySchema::class);

    // Test boolean schema creation
    expect(Schema::boolean('active'))->toBeInstanceOf(BooleanSchema::class);

    // Test integer schema creation
    expect(Schema::integer('count'))->toBeInstanceOf(IntegerSchema::class);

    // Test null schema creation
    expect(Schema::null('deleted_at'))->toBeInstanceOf(NullSchema::class);

    // Test number schema creation
    expect(Schema::number('price'))->toBeInstanceOf(NumberSchema::class);

    // Test object schema creation
    expect(Schema::object('user'))->toBeInstanceOf(ObjectSchema::class);

    // Test string schema creation
    expect(Schema::string('name'))->toBeInstanceOf(StringSchema::class);

    // Test union schema creation
    expect(Schema::union([SchemaType::String, SchemaType::Integer]))->toBeInstanceOf(UnionSchema::class);

    // Test mixed schema creation
    expect(Schema::mixed())->toBeInstanceOf(UnionSchema::class);
});

it('can create schemas with default metadata', function (): void {
    $schema = Schema::string('title')
        ->description('Description')
        ->readOnly()
        ->writeOnly();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('title', 'title');
    expect($schemaArray)->toHaveKey('description', 'Description');
    expect($schemaArray)->toHaveKey('readOnly', true);
    expect($schemaArray)->toHaveKey('writeOnly', true);
});

it('can create a schema from a closure', function (): void {
    $closure = function (string $name, array $fooArray, ?int $age = null): void {};
    $schema = Schema::fromClosure($closure);

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
                'default' => null,
            ],
        ],
        'required' => [
            'name',
            'fooArray',
        ],
    ]);

    expect($schema->toJson())->toBe(json_encode($schema->toArray()));
});

it('can create a schema from a class', function (): void {
    /** This is the description of the class */
    $class = new class ('John Doe') {
        public function __construct(
            public string $name,
            public int $age = 20,
            protected ?string $email = null,
        ) {}
    };

    $schema = Schema::fromClass($class, publicOnly: true);

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'description' => 'This is the description of the class',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'age' => [
                'type' => 'integer',
            ],
        ],
        'required' => [
            'name',
            'age',
        ],
    ]);
});

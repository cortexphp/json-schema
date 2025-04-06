<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;

covers(SchemaFactory::class);

it('can create different schema types', function (): void {
    // Test array schema creation
    expect(SchemaFactory::array('items'))->toBeInstanceOf(ArraySchema::class);

    // Test boolean schema creation
    expect(SchemaFactory::boolean('active'))->toBeInstanceOf(BooleanSchema::class);

    // Test integer schema creation
    expect(SchemaFactory::integer('count'))->toBeInstanceOf(IntegerSchema::class);

    // Test null schema creation
    expect(SchemaFactory::null('deleted_at'))->toBeInstanceOf(NullSchema::class);

    // Test number schema creation
    expect(SchemaFactory::number('price'))->toBeInstanceOf(NumberSchema::class);

    // Test object schema creation
    expect(SchemaFactory::object('user'))->toBeInstanceOf(ObjectSchema::class);

    // Test string schema creation
    expect(SchemaFactory::string('name'))->toBeInstanceOf(StringSchema::class);

    // Test union schema creation
    expect(SchemaFactory::union([SchemaType::String, SchemaType::Integer]))->toBeInstanceOf(UnionSchema::class);

    // Test mixed schema creation
    expect(SchemaFactory::mixed())->toBeInstanceOf(UnionSchema::class);
});

it('can create schemas with default metadata', function (): void {
    $schema = SchemaFactory::string('title')
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
    $schema = SchemaFactory::fromClosure($closure);

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

    // Assert that the from method behaves in the same way as the fromClosure method
    expect(SchemaFactory::from($closure))->toEqual($schema);
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

    $schema = SchemaFactory::fromClass($class, publicOnly: true);

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

    // Assert that the from method behaves in the same way as the fromClass method
    expect(SchemaFactory::from($class))->toEqual($schema);
});

it('can create a schema from an enum', function (): void {
    /** This is a custom enum for testing */
    enum UserRole: string
    {
        case Admin = 'admin';
        case Editor = 'editor';
        case Viewer = 'viewer';
        case Guest = 'guest';
    }

    $schema = SchemaFactory::fromEnum(UserRole::class);

    expect($schema)->toBeInstanceOf(StringSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'string',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'title' => 'UserRole',
        'description' => 'This is a custom enum for testing',
        'enum' => ['admin', 'editor', 'viewer', 'guest'],
    ]);

    // Assert that the from method behaves in the same way as the fromEnum method
    expect(SchemaFactory::from(UserRole::class))->toEqual($schema);
});

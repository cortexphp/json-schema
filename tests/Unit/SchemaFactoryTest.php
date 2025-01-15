<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;

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
            ],
        ],
        'required' => [
            'name',
            'fooArray',
        ],
    ]);
});

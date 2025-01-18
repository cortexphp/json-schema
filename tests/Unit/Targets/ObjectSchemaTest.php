<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Enums\SchemaFormat;
use Opis\JsonSchema\Errors\ValidationError;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a basic object schema', function (): void {
    $schema = Schema::object('user')
        ->description('User schema')
        ->properties(
            Schema::string('name')
                ->description('Name of the user')
                ->minLength(3)
                ->maxLength(255)
                ->required(),
            Schema::string('email')
                ->format(SchemaFormat::Email)
                ->required(),
            Schema::integer('age')
                ->minimum(18)
                ->maximum(150),
        );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'object');
    expect($schemaArray)->toHaveKey('title', 'user');
    expect($schemaArray)->toHaveKey('description', 'User schema');
    expect($schemaArray)->toHaveKey('properties.name.type', 'string');
    expect($schemaArray)->toHaveKey('properties.name.minLength', 3);
    expect($schemaArray)->toHaveKey('properties.name.maxLength', 255);
    expect($schemaArray)->toHaveKey('properties.email.type', 'string');
    expect($schemaArray)->toHaveKey('properties.email.format', 'email');
    expect($schemaArray)->toHaveKey('properties.age.type', 'integer');
    expect($schemaArray)->toHaveKey('properties.age.minimum', 18);
    expect($schemaArray)->toHaveKey('properties.age.maximum', 150);
    expect($schemaArray)->toHaveKey('required', ['name', 'email']);

    // Validation tests
    expect(fn() => $schema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
    ]))->not->toThrow(SchemaException::class);

    // Missing required property
    expect(fn() => $schema->validate([
        'name' => 'John Doe',
        'age' => 30,
    ]))->toThrow(
        SchemaException::class,
        'The required properties (email) are missing',
    );

    // Invalid property type
    expect(fn() => $schema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => '30', // string instead of integer
    ]))->toThrow(
        SchemaException::class,
        'The properties must match schema: age',
    );

    // Invalid property value
    expect(fn() => $schema->validate([
        'name' => 'Jo', // too short
        'email' => 'john@example.com',
        'age' => 30,
    ]))->toThrow(
        SchemaException::class,
        'The properties must match schema: name',
    );
});

it('can get the underlying errors', function (): void {
    $schema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->format(SchemaFormat::Email),
        );

    try {
        $schema->validate([
            'name' => 'John Doe',
            'email' => 'foo',
        ]);
    } catch (SchemaException $e) {
        expect($e->getMessage())->toBe('The properties must match schema: email');
        expect($e->getErrors())->toBe([
            '/email' => [
                "The data must match the 'email' format",
            ],
        ]);
        expect($e->getError())->toBeInstanceOf(ValidationError::class);

        throw $e;
    }
})->throws(SchemaException::class);

it('can create an object schema with additional properties control', function (): void {
    $schema = Schema::object('config')
        ->description('Configuration object')
        ->properties(
            Schema::string('name')->required(),
            Schema::string('type')->required(),
        )
        ->additionalProperties(false);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('additionalProperties', false);
    expect($schemaArray)->toHaveKey('required', ['name', 'type']);

    // Validation tests
    expect(fn() => $schema->validate([
        'name' => 'config1',
        'type' => 'test',
        'extra' => 'not allowed', // additional property
    ]))->toThrow(
        SchemaException::class,
        'Additional object properties are not allowed: extra',
    );

    expect(fn() => $schema->validate([
        'name' => 'config1',
        'type' => 'test',
    ]))->not->toThrow(SchemaException::class);
});

it('can create an object schema with property count constraints', function (): void {
    $schema = Schema::object('metadata')
        ->description('Metadata object')
        ->properties(
            Schema::string('key1'),
            Schema::string('key2'),
            Schema::string('key3'),
        )
        ->minProperties(2)
        ->maxProperties(3);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('minProperties', 2);
    expect($schemaArray)->toHaveKey('maxProperties', 3);

    // Validation tests
    expect(fn() => $schema->validate([
        'key1' => 'value1',
    ]))->toThrow(
        SchemaException::class,
        'Object must have at least 2 properties, 1 found',
    );

    expect(fn() => $schema->validate([
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
        'key4' => 'value4',
    ]))->toThrow(
        SchemaException::class,
        'Object must have at most 3 properties, 4 found',
    );

    expect(fn() => $schema->validate([
        'key1' => 'value1',
        'key2' => 'value2',
    ]))->not->toThrow(SchemaException::class);
});

it('can specify a propertyNames schema', function (): void {
    $schema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
        )
        // ->additionalProperties(true)
        ->propertyNames(Schema::string()->pattern('^[a-zA-Z]+$'));

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('propertyNames.pattern', '^[a-zA-Z]+$');

    // Validation tests
    expect(fn() => $schema->validate([
        'name' => 'John Doe',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'name' => 123, // invalid property name pattern
    ]))->toThrow(SchemaException::class, 'The properties must match schema: name');
});

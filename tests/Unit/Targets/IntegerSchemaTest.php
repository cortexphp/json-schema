<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a basic integer schema', function (): void {
    $schema = Schema::integer('age')
        ->description('User age')
        ->minimum(0)
        ->maximum(120);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('title', 'age');
    expect($schemaArray)->toHaveKey('description', 'User age');
    expect($schemaArray)->toHaveKey('minimum', 0);
    expect($schemaArray)->toHaveKey('maximum', 120);

    // Validation tests
    expect(fn() => $schema->validate(25))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(120))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $schema->validate(-1))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 0',
    );

    expect(fn() => $schema->validate(121))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 120',
    );

    // Test invalid types
    expect(fn() => $schema->validate(25.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer',
    );

    expect(fn() => $schema->validate('25'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: integer',
    );

    expect(fn() => $schema->validate(null))->toThrow(
        SchemaException::class,
        'The data (null) must match the type: integer',
    );
});

it('can create an integer schema with exclusive range', function (): void {
    $schema = Schema::integer('level')
        ->description('Experience level')
        ->exclusiveMinimum(0)
        ->exclusiveMaximum(100);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('exclusiveMinimum', 0);
    expect($schemaArray)->toHaveKey('exclusiveMaximum', 100);

    // Validation tests
    expect(fn() => $schema->validate(50))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(1))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(99))->not->toThrow(SchemaException::class);

    // Test boundary values
    expect(fn() => $schema->validate(0))->toThrow(
        SchemaException::class,
        'Number must be greater than 0',
    );

    expect(fn() => $schema->validate(100))->toThrow(
        SchemaException::class,
        'Number must be lower than 100',
    );
});

it('can create an integer schema with multiple of constraint', function (): void {
    $schema = Schema::integer('quantity')
        ->description('Product quantity')
        ->multipleOf(5); // Must be in packs of 5

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('multipleOf', 5);

    // Validation tests
    expect(fn() => $schema->validate(5))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(10))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(100))->not->toThrow(SchemaException::class);

    // Test invalid multiples
    expect(fn() => $schema->validate(7))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 5',
    );

    expect(fn() => $schema->validate(3))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 5',
    );

    // Test non-integer multiples
    expect(fn() => $schema->validate(7.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer',
    );
});

it('can create a nullable integer schema', function (): void {
    $schema = Schema::integer('priority')
        ->description('Task priority (1-5)')
        ->minimum(1)
        ->maximum(5)
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['integer', 'null']);
    expect($schemaArray)->toHaveKey('minimum', 1);
    expect($schemaArray)->toHaveKey('maximum', 5);

    // Validation tests
    expect(fn() => $schema->validate(3))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $schema->validate(0))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 1',
    );

    expect(fn() => $schema->validate(6))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 5',
    );

    // Test invalid types
    expect(fn() => $schema->validate(3.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer, null',
    );

    expect(fn() => $schema->validate('3'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: integer, null',
    );
});

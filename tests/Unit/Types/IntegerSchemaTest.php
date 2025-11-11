<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(IntegerSchema::class);

it('can create a basic integer schema', function (): void {
    $integerSchema = Schema::integer('age')
        ->description('User age')
        ->minimum(0)
        ->maximum(120);

    $schemaArray = $integerSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('title', 'age');
    expect($schemaArray)->toHaveKey('description', 'User age');
    expect($schemaArray)->toHaveKey('minimum', 0);
    expect($schemaArray)->toHaveKey('maximum', 120);

    // Validation tests
    expect(fn() => $integerSchema->validate(25))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(0))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(120))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $integerSchema->validate(-1))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 0',
    );

    expect(fn() => $integerSchema->validate(121))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 120',
    );

    // Test invalid types
    expect(fn() => $integerSchema->validate(25.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer',
    );

    expect(fn() => $integerSchema->validate('25'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: integer',
    );

    expect(fn() => $integerSchema->validate(null))->toThrow(
        SchemaException::class,
        'The data (null) must match the type: integer',
    );
});

it('can create an integer schema with exclusive range', function (): void {
    $integerSchema = Schema::integer('level')
        ->description('Experience level')
        ->exclusiveMinimum(0)
        ->exclusiveMaximum(100);

    $schemaArray = $integerSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('exclusiveMinimum', 0);
    expect($schemaArray)->toHaveKey('exclusiveMaximum', 100);

    // Validation tests
    expect(fn() => $integerSchema->validate(50))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(1))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(99))->not->toThrow(SchemaException::class);

    // Test boundary values
    expect(fn() => $integerSchema->validate(0))->toThrow(
        SchemaException::class,
        'Number must be greater than 0',
    );

    expect(fn() => $integerSchema->validate(100))->toThrow(
        SchemaException::class,
        'Number must be lower than 100',
    );
});

it('can create an integer schema with multiple of constraint', function (): void {
    $integerSchema = Schema::integer('quantity')
        ->description('Product quantity')
        ->multipleOf(5); // Must be in packs of 5

    $schemaArray = $integerSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'integer');
    expect($schemaArray)->toHaveKey('multipleOf', 5);

    // Validation tests
    expect(fn() => $integerSchema->validate(5))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(10))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(100))->not->toThrow(SchemaException::class);

    // Test invalid multiples
    expect(fn() => $integerSchema->validate(7))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 5',
    );

    expect(fn() => $integerSchema->validate(3))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 5',
    );

    // Test non-integer multiples
    expect(fn() => $integerSchema->validate(7.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer',
    );
});

it('can create a nullable integer schema', function (): void {
    $integerSchema = Schema::integer('priority')
        ->description('Task priority (1-5)')
        ->minimum(1)
        ->maximum(5)
        ->nullable();

    $schemaArray = $integerSchema->toArray();

    expect($schemaArray)->toHaveKey('type', ['integer', 'null']);
    expect($schemaArray)->toHaveKey('minimum', 1);
    expect($schemaArray)->toHaveKey('maximum', 5);

    // Validation tests
    expect(fn() => $integerSchema->validate(3))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(null))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $integerSchema->validate(0))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 1',
    );

    expect(fn() => $integerSchema->validate(6))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 5',
    );

    // Test invalid types
    expect(fn() => $integerSchema->validate(3.5))->toThrow(
        SchemaException::class,
        'The data (number) must match the type: integer, null',
    );

    expect(fn() => $integerSchema->validate('3'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: integer, null',
    );
});

it('throws an exception if the multipleOf is less than 0', function (): void {
    Schema::integer('age')
        ->description('User age')
        ->multipleOf(-1);
})->throws(SchemaException::class, 'multipleOf must be greater than 0');

it('throws an exception if the multipleOf is zero', function (): void {
    // Test the edge case where multipleOf is 0 - should throw exception
    // This kills the SmallerOrEqualToSmaller and DecrementInteger mutations
    Schema::integer('value')
        ->multipleOf(0);
})->throws(SchemaException::class, 'multipleOf must be greater than 0');

it('accepts valid multipleOf values including 1', function (): void {
    // Test that multipleOf = 1 is valid (kills the IncrementInteger mutation)
    expect(
        fn(): IntegerSchema => Schema::integer('any')
            ->multipleOf(1),
    )->not->toThrow(SchemaException::class);

    // Test other valid values
    expect(
        fn(): IntegerSchema => Schema::integer('even')
            ->multipleOf(2),
    )->not->toThrow(SchemaException::class);

    expect(
        fn(): IntegerSchema => Schema::integer('tens')
            ->multipleOf(10),
    )->not->toThrow(SchemaException::class);

    // Verify multipleOf = 1 actually works in validation
    $integerSchema = Schema::integer('test')->multipleOf(1);
    expect($integerSchema->toArray())->toHaveKey('multipleOf', 1);

    // Any integer should be valid with multipleOf = 1
    expect(fn() => $integerSchema->validate(5))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(100))->not->toThrow(SchemaException::class);
    expect(fn() => $integerSchema->validate(-5))->not->toThrow(SchemaException::class);
});

it('validates multipleOf boundary conditions', function (): void {
    // Negative values should throw exception
    expect(fn(): IntegerSchema => Schema::integer('test')->multipleOf(-1))
        ->toThrow(SchemaException::class, 'multipleOf must be greater than 0');

    // Zero should throw exception (this is the key test for the mutations)
    expect(fn(): IntegerSchema => Schema::integer('test')->multipleOf(0))
        ->toThrow(SchemaException::class, 'multipleOf must be greater than 0');

    // Positive values should be accepted
    expect(fn(): IntegerSchema => Schema::integer('test')->multipleOf(1))
        ->not->toThrow(SchemaException::class);
});

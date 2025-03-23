<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(NumberSchema::class);

it('can create a basic number schema', function (): void {
    $schema = Schema::number('price')
        ->description('Product price')
        ->minimum(0)
        ->maximum(1000);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'number');
    expect($schemaArray)->toHaveKey('title', 'price');
    expect($schemaArray)->toHaveKey('description', 'Product price');
    expect($schemaArray)->toHaveKey('minimum', 0);
    expect($schemaArray)->toHaveKey('maximum', 1000);

    // Validation tests
    expect(fn() => $schema->validate(99.99))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(1000))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $schema->validate(-1))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 0',
    );

    expect(fn() => $schema->validate(1000.01))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 1000',
    );

    // Test invalid types
    expect(fn() => $schema->validate('100'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: number',
    );

    expect(fn() => $schema->validate(null))->toThrow(
        SchemaException::class,
        'The data (null) must match the type: number',
    );
});

it('can create a number schema with exclusive range', function (): void {
    $schema = Schema::number('temperature')
        ->description('Temperature in Celsius')
        ->exclusiveMinimum(0)
        ->exclusiveMaximum(100);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'number');
    expect($schemaArray)->toHaveKey('exclusiveMinimum', 0);
    expect($schemaArray)->toHaveKey('exclusiveMaximum', 100);

    // Validation tests
    expect(fn() => $schema->validate(50))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0.1))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(99.9))->not->toThrow(SchemaException::class);

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

it('can create a number schema with multiple of constraint', function (): void {
    $schema = Schema::number('amount')
        ->description('Amount in dollars')
        ->multipleOf(0.01); // Must be a valid dollar amount

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'number');
    expect($schemaArray)->toHaveKey('multipleOf', 0.01);

    // Validation tests
    expect(fn() => $schema->validate(10.50))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0.01))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(100.00))->not->toThrow(SchemaException::class);

    // Test invalid multiples
    expect(fn() => $schema->validate(10.505))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 0.01',
    );

    expect(fn() => $schema->validate(0.001))->toThrow(
        SchemaException::class,
        'Number must be a multiple of 0.01',
    );
});

it('can create a nullable number schema', function (): void {
    $schema = Schema::number('discount')
        ->description('Discount percentage')
        ->minimum(0)
        ->maximum(100)
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['number', 'null']);
    expect($schemaArray)->toHaveKey('minimum', 0);
    expect($schemaArray)->toHaveKey('maximum', 100);

    // Validation tests
    expect(fn() => $schema->validate(50))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    // Test out of range values
    expect(fn() => $schema->validate(-1))->toThrow(
        SchemaException::class,
        'Number must be greater than or equal to 0',
    );

    expect(fn() => $schema->validate(101))->toThrow(
        SchemaException::class,
        'Number must be lower than or equal to 100',
    );

    // Test invalid types
    expect(fn() => $schema->validate('50'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: number, null',
    );
});

it('can create a number schema with enum values', function (): void {
    $schema = Schema::number('rating')
        ->description('Product rating')
        ->enum([1.0, 2.5, 3.0, 4.5, 5.0]);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'number');
    expect($schemaArray)->toHaveKey('title', 'rating');
    expect($schemaArray)->toHaveKey('description', 'Product rating');
    expect($schemaArray)->toHaveKey('enum', [1.0, 2.5, 3.0, 4.5, 5.0]);

    // Validation tests
    expect(fn() => $schema->validate(3.5))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $schema->validate(1.0))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(2.5))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(5.0))->not->toThrow(SchemaException::class);
});

it('can create a nullable number schema with enum values', function (): void {
    $schema = Schema::number('discount')
        ->description('Product discount percentage')
        ->enum([0.1, 0.25, 0.5, null])
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['number', 'null']);
    expect($schemaArray)->toHaveKey('title', 'discount');
    expect($schemaArray)->toHaveKey('description', 'Product discount percentage');
    expect($schemaArray)->toHaveKey('enum', [0.1, 0.25, 0.5, null]);

    // Validation tests
    expect(fn() => $schema->validate(0.75))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $schema->validate(0.1))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0.25))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(0.5))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);
});

it('can create a number schema with const value', function (): void {
    $schema = Schema::number('tax_rate')
        ->description('Fixed tax rate percentage')
        ->const(0.21); // 21% VAT

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'number');
    expect($schemaArray)->toHaveKey('title', 'tax_rate');
    expect($schemaArray)->toHaveKey('description', 'Fixed tax rate percentage');
    expect($schemaArray)->toHaveKey('const', 0.21);

    // Validation tests
    expect(fn() => $schema->validate(0.20))->toThrow(
        SchemaException::class,
        'The data must match the const value',
    );

    expect(fn() => $schema->validate(0.21))->not->toThrow(SchemaException::class);
});

it('can create a nullable number schema with const value', function (): void {
    $schema = Schema::number('standard_fee')
        ->description('Standard processing fee')
        ->nullable()
        ->const(null);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['number', 'null']);
    expect($schemaArray)->toHaveKey('title', 'standard_fee');
    expect($schemaArray)->toHaveKey('description', 'Standard processing fee');
    expect($schemaArray)->toHaveKey('const', null);

    // Validation tests
    expect(fn() => $schema->validate(0.0))->toThrow(
        SchemaException::class,
        'The data must match the const value',
    );

    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);
});

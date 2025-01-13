<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

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

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(UnionSchema::class);

it('can create a union schema with multiple types', function (): void {
    $schema = Schema::union([SchemaType::String, SchemaType::Integer], 'id')
        ->description('ID can be either a string or an integer');

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'integer']);
    expect($schemaArray)->toHaveKey('title', 'id');
    expect($schemaArray)->toHaveKey('description', 'ID can be either a string or an integer');

    // Test validation
    expect(fn() => $schema->validate('abc123'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(123))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate(true))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->toThrow(SchemaException::class);
});

it('can create a nullable union schema', function (): void {
    $schema = Schema::union([SchemaType::String, SchemaType::Number], 'value')
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'number', 'null']);

    // Test validation
    expect(fn() => $schema->validate('abc'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(123.45))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate(true))->toThrow(SchemaException::class);
});

it('can create a union schema with enum values', function (): void {
    $schema = Schema::union([SchemaType::String, SchemaType::Integer], 'status')
        ->enum(['pending', 'active', 1, 2]);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'integer']);
    expect($schemaArray)->toHaveKey('enum', ['pending', 'active', 1, 2]);

    // Test validation
    expect(fn() => $schema->validate('pending'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('active'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(1))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(2))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate('invalid'))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(3))->toThrow(SchemaException::class);
});

it('can create a union schema with const value', function (): void {
    $schema = Schema::union([SchemaType::String, SchemaType::Integer], 'fixed')
        ->const('test');

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'integer']);
    expect($schemaArray)->toHaveKey('const', 'test');

    // Test validation
    expect(fn() => $schema->validate('test'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('other'))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(123))->toThrow(SchemaException::class);
});

it('can create a union schema with metadata', function (): void {
    $schema = Schema::union([SchemaType::String, SchemaType::Number], 'mixed')
        ->description('A mixed type field')
        ->default('test')
        ->readOnly();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'number']);
    expect($schemaArray)->toHaveKey('description', 'A mixed type field');
    expect($schemaArray)->toHaveKey('default', 'test');
    expect($schemaArray)->toHaveKey('readOnly', true);
});

it('throws exception when creating union schema with no types', function (): void {
    expect(fn(): UnionSchema => Schema::union([], 'empty'))->toThrow(
        SchemaException::class,
        'Union schema must have at least one type',
    );
});

it('throws exception when creating union schema with duplicate types', function (): void {
    expect(fn(): UnionSchema => Schema::union([SchemaType::String, SchemaType::String], 'duplicate'))->toThrow(
        SchemaException::class,
        'Union schema types must be unique',
    );
});

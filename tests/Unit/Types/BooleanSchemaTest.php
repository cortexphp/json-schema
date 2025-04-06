<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(BooleanSchema::class);

it('can create a boolean schema', function (): void {
    $schema = Schema::boolean('is_active')
        ->description('User active status');

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'boolean');
    expect($schemaArray)->toHaveKey('title', 'is_active');
    expect($schemaArray)->toHaveKey('description', 'User active status');

    // Validation tests
    expect(fn() => $schema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(false))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $schema->validate(1))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: boolean',
    );

    expect(fn() => $schema->validate('true'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean',
    );

    expect(fn() => $schema->validate(null))->toThrow(
        SchemaException::class,
        'The data (null) must match the type: boolean',
    );
});

it('can create a boolean schema with read-only property', function (): void {
    $schema = Schema::boolean('is_verified')
        ->description('Email verification status')
        ->readOnly();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'boolean');
    expect($schemaArray)->toHaveKey('title', 'is_verified');
    expect($schemaArray)->toHaveKey('description', 'Email verification status');
    expect($schemaArray)->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $schema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(false))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $schema->validate('yes'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean',
    );
});

it('can create a nullable boolean schema', function (): void {
    $schema = Schema::boolean('is_subscribed')
        ->description('Newsletter subscription status')
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['boolean', 'null']);
    expect($schemaArray)->toHaveKey('title', 'is_subscribed');
    expect($schemaArray)->toHaveKey('description', 'Newsletter subscription status');

    // Validation tests
    expect(fn() => $schema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(false))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $schema->validate(0))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: boolean, null',
    );

    expect(fn() => $schema->validate('false'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean, null',
    );
});

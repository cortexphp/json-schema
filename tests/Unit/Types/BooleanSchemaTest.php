<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(BooleanSchema::class);

it('can create a boolean schema', function (): void {
    $booleanSchema = Schema::boolean('is_active')
        ->description('User active status');

    $schemaArray = $booleanSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema')
        ->toHaveKey('type', 'boolean')
        ->toHaveKey('title', 'is_active')
        ->toHaveKey('description', 'User active status');

    // Validation tests
    expect(fn() => $booleanSchema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $booleanSchema->validate(false))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $booleanSchema->validate(1))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: boolean',
    );

    expect(fn() => $booleanSchema->validate('true'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean',
    )
        ->and(fn() => $booleanSchema->validate(null))
        ->toThrow(SchemaException::class, 'The data (null) must match the type: boolean');
});

it('can create a boolean schema with read-only property', function (): void {
    $booleanSchema = Schema::boolean('is_verified')
        ->description('Email verification status')
        ->readOnly();

    $schemaArray = $booleanSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'boolean')
        ->toHaveKey('title', 'is_verified')
        ->toHaveKey('description', 'Email verification status')
        ->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $booleanSchema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $booleanSchema->validate(false))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $booleanSchema->validate('yes'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean',
    );
});

it('can create a nullable boolean schema', function (): void {
    $booleanSchema = Schema::boolean('is_subscribed')
        ->description('Newsletter subscription status')
        ->nullable();

    $schemaArray = $booleanSchema->toArray();

    expect($schemaArray)->toHaveKey('type', ['boolean', 'null'])
        ->toHaveKey('title', 'is_subscribed')
        ->toHaveKey('description', 'Newsletter subscription status');

    // Validation tests
    expect(fn() => $booleanSchema->validate(true))->not->toThrow(SchemaException::class);
    expect(fn() => $booleanSchema->validate(false))->not->toThrow(SchemaException::class)
        ->and(fn() => $booleanSchema->validate(null))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $booleanSchema->validate(0))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: boolean, null',
    );

    expect(fn() => $booleanSchema->validate('false'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: boolean, null',
    );
});

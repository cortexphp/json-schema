<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(ArraySchema::class);

it('can create an array schema', function (): void {
    $arraySchema = Schema::array('tags')
        ->description('List of tags')
        ->items(
            Schema::string()
                ->minLength(2)
                ->maxLength(50),
        )
        ->minItems(1)
        ->maxItems(10)
        ->uniqueItems();

    $schemaArray = $arraySchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'array');
    expect($schemaArray)->toHaveKey('title', 'tags');
    expect($schemaArray)->toHaveKey('description', 'List of tags');
    expect($schemaArray)->toHaveKey('minItems', 1);
    expect($schemaArray)->toHaveKey('maxItems', 10);
    expect($schemaArray)->toHaveKey('uniqueItems', true);
    expect($schemaArray)->toHaveKey('items.type', 'string');
    expect($schemaArray)->toHaveKey('items.minLength', 2);
    expect($schemaArray)->toHaveKey('items.maxLength', 50);

    // Validation tests
    expect(fn() => $arraySchema->validate([
        'php', 'javascript', 'python',
    ]))->not->toThrow(SchemaException::class);

    // Test minimum items
    expect(fn() => $arraySchema->validate([]))->toThrow(
        SchemaException::class,
        'Array should have at least 1 items, 0 found',
    );

    // Test maximum items
    expect(fn() => $arraySchema->validate([
        'tag1', 'tag2', 'tag3', 'tag4', 'tag5',
        'tag6', 'tag7', 'tag8', 'tag9', 'tag10', 'tag11',
    ]))->toThrow(
        SchemaException::class,
        'Array should have at most 10 items, 11 found',
    );

    // Test item string length
    expect(fn() => $arraySchema->validate(['a']))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );

    expect(fn() => $arraySchema->validate([str_repeat('a', 51)]))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );

    // Test unique items
    expect(fn() => $arraySchema->validate(['php', 'php']))->toThrow(
        SchemaException::class,
        'Array must have unique items',
    );

    // Test invalid item type
    expect(fn() => $arraySchema->validate(['php', 123, 'python']))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );
});

it('can validate array contains', function (): void {
    // First test just contains without min/max
    $arraySchema = Schema::array('numbers')
        ->description('List of numbers')
        // Must contain at least one number between 10 and 20
        ->contains(Schema::number()->minimum(10)->maximum(20));

    // Test basic contains validation
    expect(fn() => $arraySchema->validate([15, 5, 6]))->not->toThrow(SchemaException::class);
    expect(fn() => $arraySchema->validate([1, 2, 3]))->toThrow(
        SchemaException::class,
        'At least one array item must match schema',
    );

    // Now test with minContains and maxContains (requires Draft 2019-09+)
    $schema = Schema::array('numbers', SchemaVersion::Draft201909)
        ->description('List of numbers')
        ->contains(
            Schema::number()
                ->minimum(10)
                ->maximum(20),
        )
        ->minContains(2)
        ->maxContains(3);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2019-09/schema');
    expect($schemaArray)->toHaveKey('type', 'array');
    expect($schemaArray)->toHaveKey('title', 'numbers');
    expect($schemaArray)->toHaveKey('description', 'List of numbers');
    expect($schemaArray)->toHaveKey('contains.type', 'number');
    expect($schemaArray)->toHaveKey('contains.minimum', 10);
    expect($schemaArray)->toHaveKey('contains.maximum', 20);
    expect($schemaArray)->toHaveKey('minContains', 2);
    expect($schemaArray)->toHaveKey('maxContains', 3);

    // Valid cases - arrays with 2-3 numbers between 10-20
    expect(fn() => $schema->validate([15, 12, 5]))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate([15, 12, 18, 5]))->not->toThrow(SchemaException::class);

    // Test no matching items
    expect(fn() => $schema->validate([1, 2, 3]))->toThrow(
        SchemaException::class,
        'At least 2 array items must match schema',
    );
});

it('throws an exception if the minContains is less than 0', function (): void {
    Schema::array('numbers', SchemaVersion::Draft201909)
        ->description('List of numbers')
        ->minContains(-1);
})->throws(SchemaException::class, 'minContains must be greater than or equal to 0');

it('throws an exception if the maxContains is less than 0', function (): void {
    Schema::array('numbers', SchemaVersion::Draft201909)
        ->description('List of numbers')
        ->maxContains(-1);
})->throws(SchemaException::class, 'maxContains must be greater than or equal to 0');

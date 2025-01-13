<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create an array schema', function (): void {
    $schema = Schema::array('tags')
        ->description('List of tags')
        ->items(
            Schema::string()
                ->minLength(2)
                ->maxLength(50),
        )
        ->minItems(1)
        ->maxItems(10)
        ->uniqueItems();

    $schemaArray = $schema->toArray();

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
    expect(fn() => $schema->validate([
        'php', 'javascript', 'python',
    ]))->not->toThrow(SchemaException::class);

    // Test minimum items
    expect(fn() => $schema->validate([]))->toThrow(
        SchemaException::class,
        'Array should have at least 1 items, 0 found',
    );

    // Test maximum items
    expect(fn() => $schema->validate([
        'tag1', 'tag2', 'tag3', 'tag4', 'tag5',
        'tag6', 'tag7', 'tag8', 'tag9', 'tag10', 'tag11',
    ]))->toThrow(
        SchemaException::class,
        'Array should have at most 10 items, 11 found',
    );

    // Test item string length
    expect(fn() => $schema->validate(['a']))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );

    expect(fn() => $schema->validate([str_repeat('a', 51)]))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );

    // Test unique items
    expect(fn() => $schema->validate(['php', 'php']))->toThrow(
        SchemaException::class,
        'Array must have unique items',
    );

    // Test invalid item type
    expect(fn() => $schema->validate(['php', 123, 'python']))->toThrow(
        SchemaException::class,
        'All array items must match schema',
    );
});

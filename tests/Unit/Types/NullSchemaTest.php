<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use stdClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(NullSchema::class);

it('can create a basic null schema', function (): void {
    $nullSchema = Schema::null('deleted_at')
        ->description('Soft delete timestamp');

    $schemaArray = $nullSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'null');
    expect($schemaArray)->toHaveKey('title', 'deleted_at');
    expect($schemaArray)->toHaveKey('description', 'Soft delete timestamp');

    // Validation tests
    expect(fn() => $nullSchema->validate(null))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $nullSchema->validate(0))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: null',
    );

    expect(fn() => $nullSchema->validate(''))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: null',
    );

    expect(fn() => $nullSchema->validate(false))->toThrow(
        SchemaException::class,
        'The data (boolean) must match the type: null',
    );

    expect(fn() => $nullSchema->validate([]))->toThrow(
        SchemaException::class,
        'The data (array) must match the type: null',
    );

    expect(fn() => $nullSchema->validate(new stdClass()))->toThrow(
        SchemaException::class,
        'The data (object) must match the type: null',
    );
});

it('can create a read-only null schema', function (): void {
    $nullSchema = Schema::null('archived_at')
        ->description('Archive timestamp')
        ->readOnly();

    $schemaArray = $nullSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'null');
    expect($schemaArray)->toHaveKey('title', 'archived_at');
    expect($schemaArray)->toHaveKey('description', 'Archive timestamp');
    expect($schemaArray)->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $nullSchema->validate(null))->not->toThrow(SchemaException::class);

    expect(fn() => $nullSchema->validate('2024-03-14'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: null',
    );
});

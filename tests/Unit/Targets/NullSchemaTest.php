<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use stdClass;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a basic null schema', function (): void {
    $schema = Schema::null('deleted_at')
        ->description('Soft delete timestamp');

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'null');
    expect($schemaArray)->toHaveKey('title', 'deleted_at');
    expect($schemaArray)->toHaveKey('description', 'Soft delete timestamp');

    // Validation tests
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    // Test invalid types
    expect(fn() => $schema->validate(0))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: null',
    );

    expect(fn() => $schema->validate(''))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: null',
    );

    expect(fn() => $schema->validate(false))->toThrow(
        SchemaException::class,
        'The data (boolean) must match the type: null',
    );

    expect(fn() => $schema->validate([]))->toThrow(
        SchemaException::class,
        'The data (array) must match the type: null',
    );

    expect(fn() => $schema->validate(new stdClass()))->toThrow(
        SchemaException::class,
        'The data (object) must match the type: null',
    );
});

it('can create a read-only null schema', function (): void {
    $schema = Schema::null('archived_at')
        ->description('Archive timestamp')
        ->readOnly();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'null');
    expect($schemaArray)->toHaveKey('title', 'archived_at');
    expect($schemaArray)->toHaveKey('description', 'Archive timestamp');
    expect($schemaArray)->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate('2024-03-14'))->toThrow(
        SchemaException::class,
        'The data (string) must match the type: null',
    );
});

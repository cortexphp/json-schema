<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a string schema with length constraints', function (): void {
    $schema = Schema::string('username')
        ->description('Username for the account')
        ->minLength(3)
        ->maxLength(50);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'username');
    expect($schemaArray)->toHaveKey('description', 'Username for the account');
    expect($schemaArray)->toHaveKey('minLength', 3);
    expect($schemaArray)->toHaveKey('maxLength', 50);

    // Validation tests
    expect(fn() => $schema->validate('ab'))->toThrow(
        SchemaException::class,
        'Minimum string length is 3, found 2',
    );

    expect(fn() => $schema->validate(str_repeat('a', 51)))->toThrow(
        SchemaException::class,
        'Maximum string length is 50, found 51',
    );

    expect(fn() => $schema->validate('valid-username'))->not->toThrow(SchemaException::class);
});

it('can create a string schema with pattern validation', function (): void {
    $schema = Schema::string('password')
        ->description('User password')
        ->pattern('^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$') // At least 8 chars, 1 letter and 1 number
        ->minLength(8);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'password');
    expect($schemaArray)->toHaveKey('description', 'User password');
    expect($schemaArray)->toHaveKey('pattern', '^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$');
    expect($schemaArray)->toHaveKey('minLength', 8);

    // Validation tests
    expect(fn() => $schema->validate('short1'))->toThrow(
        SchemaException::class,
        'Minimum string length is 8, found 6',
    );

    expect(fn() => $schema->validate('onlyletters'))->toThrow(
        SchemaException::class,
        'The string should match pattern: ^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$',
    );

    expect(fn() => $schema->validate('12345678'))->toThrow(
        SchemaException::class,
        'The string should match pattern: ^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$',
    );

    expect(fn() => $schema->validate('password123'))->not->toThrow(SchemaException::class);
});

it('can create a string schema with format', function (): void {
    $schema = Schema::string('email')
        ->description('User email address')
        ->format(SchemaFormat::Email);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'email');
    expect($schemaArray)->toHaveKey('description', 'User email address');
    expect($schemaArray)->toHaveKey('format', 'email');

    // Validation tests
    expect(fn() => $schema->validate('not-an-email'))->toThrow(
        SchemaException::class,
        "The data must match the 'email' format",
    );

    expect(fn() => $schema->validate('test@example.com'))->not->toThrow(SchemaException::class);
});

it('can create a nullable string schema', function (): void {
    $schema = Schema::string('middle_name')
        ->description('User middle name')
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'null']);
    expect($schemaArray)->toHaveKey('title', 'middle_name');
    expect($schemaArray)->toHaveKey('description', 'User middle name');

    // Validation tests
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('John'))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate(123))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: string, null',
    );
});

it('can create a read-only string schema', function (): void {
    $schema = Schema::string('created_at')
        ->description('Record creation timestamp')
        ->format(SchemaFormat::DateTime)
        ->readOnly();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'created_at');
    expect($schemaArray)->toHaveKey('description', 'Record creation timestamp');
    expect($schemaArray)->toHaveKey('format', 'date-time');
    expect($schemaArray)->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $schema->validate('not-a-date'))->toThrow(
        SchemaException::class,
        "The data must match the 'date-time' format",
    );

    expect(fn() => $schema->validate('2024-03-14T12:00:00Z'))->not->toThrow(SchemaException::class);
});

it('can create a string schema with enum values', function (): void {
    $schema = Schema::string('status')
        ->description('Current status of the record')
        ->enum(['draft', 'published', 'archived']);

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'status');
    expect($schemaArray)->toHaveKey('description', 'Current status of the record');
    expect($schemaArray)->toHaveKey('enum', ['draft', 'published', 'archived']);

    // Validation tests
    expect(fn() => $schema->validate('pending'))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $schema->validate('draft'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('published'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('archived'))->not->toThrow(SchemaException::class);
});

it('can create a nullable string schema with enum values', function (): void {
    $schema = Schema::string('priority')
        ->description('Task priority level')
        ->enum(['low', 'medium', 'high', null])
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'null']);
    expect($schemaArray)->toHaveKey('title', 'priority');
    expect($schemaArray)->toHaveKey('description', 'Task priority level');
    expect($schemaArray)->toHaveKey('enum', ['low', 'medium', 'high', null]);

    // Validation tests
    expect(fn() => $schema->validate('critical'))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $schema->validate('low'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('medium'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('high'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate(null))->not->toThrow(SchemaException::class);
});

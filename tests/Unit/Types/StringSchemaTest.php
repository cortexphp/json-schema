<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use ReflectionClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(StringSchema::class);

it('can create a string schema with length constraints', function (): void {
    $stringSchema = Schema::string('username')
        ->description('Username for the account')
        ->minLength(3)
        ->maxLength(50);

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'username');
    expect($schemaArray)->toHaveKey('description', 'Username for the account');
    expect($schemaArray)->toHaveKey('minLength', 3);
    expect($schemaArray)->toHaveKey('maxLength', 50);

    // Validation tests
    expect(fn() => $stringSchema->validate('ab'))->toThrow(
        SchemaException::class,
        'Minimum string length is 3, found 2',
    );

    expect(fn() => $stringSchema->validate(str_repeat('a', 51)))->toThrow(
        SchemaException::class,
        'Maximum string length is 50, found 51',
    );

    expect(fn() => $stringSchema->validate('valid-username'))->not->toThrow(SchemaException::class);
});

it('throws an exception if the minLength is greater than the maxLength', function (): void {
    Schema::string('username')
        ->minLength(51)
        ->maxLength(50);
})->throws(SchemaException::class, 'Maximum length must be greater than or equal to minimum length');

it('throws an exception if the minLength is less than 0', function (): void {
    Schema::string('username')
        ->minLength(-1);
})->throws(SchemaException::class, 'Minimum length must be greater than or equal to 0');

it('throws an exception if the maxLength is less than 0', function (): void {
    Schema::string('username')
        ->maxLength(-1);
})->throws(SchemaException::class, 'Maximum length must be greater than or equal to 0');

it('can create a string schema with pattern validation', function (): void {
    $stringSchema = Schema::string('password')
        ->description('User password')
        ->pattern('^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$') // At least 8 chars, 1 letter and 1 number
        ->minLength(8);

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'password');
    expect($schemaArray)->toHaveKey('description', 'User password');
    expect($schemaArray)->toHaveKey('pattern', '^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$');
    expect($schemaArray)->toHaveKey('minLength', 8);

    // Validation tests
    expect(fn() => $stringSchema->validate('short1'))->toThrow(
        SchemaException::class,
        'Minimum string length is 8, found 6',
    );

    expect(fn() => $stringSchema->validate('onlyletters'))->toThrow(
        SchemaException::class,
        'The string should match pattern: ^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$',
    );

    expect(fn() => $stringSchema->validate('12345678'))->toThrow(
        SchemaException::class,
        'The string should match pattern: ^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$',
    );

    expect(fn() => $stringSchema->validate('password123'))->not->toThrow(SchemaException::class);
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

    $schema = Schema::string('email')
        ->description('User email address')
        ->format('custom');

    expect($schema->toArray())->toHaveKey('format', 'custom');
});

it('can create a nullable string schema', function (): void {
    $stringSchema = Schema::string('middle_name')
        ->description('User middle name')
        ->nullable();

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'null']);
    expect($schemaArray)->toHaveKey('title', 'middle_name');
    expect($schemaArray)->toHaveKey('description', 'User middle name');

    // Validation tests
    expect(fn() => $stringSchema->validate(null))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate('John'))->not->toThrow(SchemaException::class);

    expect($stringSchema->isValid(null))->toBeTrue();
    expect($stringSchema->isValid('John'))->toBeTrue();

    expect(fn() => $stringSchema->validate(123))->toThrow(
        SchemaException::class,
        'The data (integer) must match the type: string, null',
    );
    expect($stringSchema->isValid(123))->toBeFalse();
});

it('can create a read-only string schema', function (): void {
    $stringSchema = Schema::string('created_at')
        ->description('Record creation timestamp')
        ->format(SchemaFormat::DateTime)
        ->readOnly();

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'created_at');
    expect($schemaArray)->toHaveKey('description', 'Record creation timestamp');
    expect($schemaArray)->toHaveKey('format', 'date-time');
    expect($schemaArray)->toHaveKey('readOnly', true);

    // Validation tests
    expect(fn() => $stringSchema->validate('not-a-date'))->toThrow(
        SchemaException::class,
        "The data must match the 'date-time' format",
    );

    expect(fn() => $stringSchema->validate('2024-03-14T12:00:00Z'))->not->toThrow(SchemaException::class);
});

it('can create a string schema with enum values', function (): void {
    $stringSchema = Schema::string('status')
        ->description('Current status of the record')
        ->enum(['draft', 'published', 'archived']);

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('type', 'string');
    expect($schemaArray)->toHaveKey('title', 'status');
    expect($schemaArray)->toHaveKey('description', 'Current status of the record');
    expect($schemaArray)->toHaveKey('enum', ['draft', 'published', 'archived']);

    // Validation tests
    expect(fn() => $stringSchema->validate('pending'))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $stringSchema->validate('draft'))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate('published'))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate('archived'))->not->toThrow(SchemaException::class);
});

it('can create a nullable string schema with enum values', function (): void {
    $stringSchema = Schema::string('priority')
        ->description('Task priority level')
        ->enum(['low', 'medium', 'high', null])
        ->nullable();

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('type', ['string', 'null']);
    expect($schemaArray)->toHaveKey('title', 'priority');
    expect($schemaArray)->toHaveKey('description', 'Task priority level');
    expect($schemaArray)->toHaveKey('enum', ['low', 'medium', 'high', null]);

    // Validation tests
    expect(fn() => $stringSchema->validate('critical'))->toThrow(
        SchemaException::class,
        'The data should match one item from enum',
    );

    expect(fn() => $stringSchema->validate('low'))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate('medium'))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate('high'))->not->toThrow(SchemaException::class);
    expect(fn() => $stringSchema->validate(null))->not->toThrow(SchemaException::class);
});

it('can mark a string schema as deprecated', function (): void {
    $stringSchema = Schema::string('foo', SchemaVersion::Draft_2019_09)
        ->comment("Don't use this")
        ->deprecated();

    expect($stringSchema->toArray())
        ->toHaveKey('deprecated', true)
        ->toHaveKey('$comment', "Don't use this");
});

it('can create a string schema with examples', function (): void {
    $stringSchema = Schema::string('foo')->examples(['foo', 'bar']);

    expect($stringSchema->toArray())->toHaveKey('examples', ['foo', 'bar']);
});

it('can create a string schema with content annotations', function (): void {
    $stringSchema = Schema::string('payload', SchemaVersion::Draft_2019_09)
        ->contentEncoding('base64')
        ->contentMediaType('application/json')
        ->contentSchema(
            Schema::object()
                ->properties(
                    Schema::string('name')->required(),
                ),
        );

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('contentEncoding', 'base64');
    expect($schemaArray)->toHaveKey('contentMediaType', 'application/json');
    expect($schemaArray)->toHaveKey('contentSchema.type', 'object');
    expect($schemaArray)->toHaveKey('contentSchema.properties.name.type', 'string');

    // Valid base64 encoded string that matches the content schema
    expect(fn() => $stringSchema->validate(base64_encode('{"name":"Ada"}')))
        ->not->toThrow(SchemaException::class);

    // Invalid base64 encoded string
    expect(fn() => $stringSchema->validate('not-a-base64-encoded-string'))
        ->toThrow(SchemaException::class, "The value must be encoded as 'base64'");

    // Does not match the content schema
    expect(fn() => $stringSchema->validate(base64_encode('{"foo":"bar"}')))
        ->toThrow(SchemaException::class, 'The JSON content must match schema');
});

it('can create a string schema with boolean contentSchema', function (): void {
    $stringSchema = Schema::string('payload', SchemaVersion::Draft_2019_09)
        ->contentSchema(false);

    expect($stringSchema->toArray())->toHaveKey('contentSchema', false);
});

it('validates contentSchema feature support', function (): void {
    $stringSchema = Schema::string('payload', SchemaVersion::Draft_07);

    expect(fn(): StringSchema => $stringSchema->contentSchema(Schema::object()))->toThrow(
        SchemaException::class,
        'Feature "Schema for decoded content" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );
});

it('detects content features correctly', function (): void {
    $stringSchema = Schema::string('payload', SchemaVersion::Draft_2019_09);
    $stringSchema->contentEncoding('base64')
        ->contentMediaType('application/json')
        ->contentSchema(Schema::object());

    $reflection = new ReflectionClass($stringSchema);
    $contentFeaturesMethod = $reflection->getMethod('getContentFeatures');

    $contentFeatures = $contentFeaturesMethod->invoke($stringSchema);

    expect($contentFeatures)->toContain(SchemaFeature::ContentEncoding);
    expect($contentFeatures)->toContain(SchemaFeature::ContentMediaType);
    expect($contentFeatures)->toContain(SchemaFeature::ContentSchema);

    // Test that features are included in overall feature detection
    $getUsedMethod = $reflection->getMethod('getUsedFeatures');

    $allFeatures = $getUsedMethod->invoke($stringSchema);
    expect($allFeatures)->toContain(SchemaFeature::ContentEncoding);
    expect($allFeatures)->toContain(SchemaFeature::ContentMediaType);
    expect($allFeatures)->toContain(SchemaFeature::ContentSchema);
});

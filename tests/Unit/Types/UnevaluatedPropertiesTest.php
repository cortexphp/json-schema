<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use ReflectionClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can set unevaluatedProperties to false', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
        )
        ->unevaluatedProperties(false);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedProperties', false);
    expect($schemaArray)->toHaveKey('properties.name');
    expect($schemaArray)->toHaveKey('properties.email');

    // Test basic validation (unevaluatedProperties validation requires a full JSON Schema validator)
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
    ]))->toBeTrue();

    // Note: Full unevaluatedProperties validation requires a complete JSON Schema validator
    // This package generates schemas but doesn't implement the complex unevaluated logic
});

it('can set unevaluatedProperties to true', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(true);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedProperties', true);

    // Test basic validation
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeTrue();

    // Note: unevaluatedProperties: true generates correct schema structure
    // Actual unevaluated property validation would be handled by a JSON Schema validator
});

it('can set unevaluatedProperties to a schema', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(
            Schema::string()->minLength(3),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedProperties');
    expect($schemaArray['unevaluatedProperties'])->toBe([
        'type' => 'string',
        'minLength' => 3,
    ]);

    // Test basic validation
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeTrue();

    // Note: unevaluatedProperties with schema generates correct structure
    // Complex validation logic would be implemented by a full JSON Schema validator
});

it('throws exception when using unevaluatedProperties with Draft 07', function (): void {
    expect(
        fn(): ObjectSchema => Schema::object('user', SchemaVersion::Draft_07)
            ->unevaluatedProperties(false),
    )->toThrow(
        SchemaException::class,
        'Feature "Unevaluated properties validation" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );
});

it('works with Draft 2019-09', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(false);

    expect($objectSchema->toArray())->toHaveKey('unevaluatedProperties', false);
    expect($objectSchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2019-09/schema');
});

it('works with Draft 2020-12', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2020_12)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(false);

    expect($objectSchema->toArray())->toHaveKey('unevaluatedProperties', false);
    expect($objectSchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
});

it('detects unevaluatedProperties feature correctly', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(false);

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->toContain('unevaluatedProperties');
});

it('does not include unevaluatedProperties feature when not used', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        );

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->not->toContain('unevaluatedProperties');
});

it('can combine with other object properties', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
        )
        ->additionalProperties(true)
        ->unevaluatedProperties(false)
        ->minProperties(2)
        ->maxProperties(10);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('required', ['name', 'email']);
    expect($schemaArray)->toHaveKey('additionalProperties', true);
    expect($schemaArray)->toHaveKey('unevaluatedProperties', false);
    expect($schemaArray)->toHaveKey('minProperties', 2);
    expect($schemaArray)->toHaveKey('maxProperties', 10);
});

it('validates version during schema output', function (): void {
    // Create schema with Draft 2019-09
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->unevaluatedProperties(false);

    // Change version to Draft 07 after setting unevaluatedProperties
    $objectSchema->version(SchemaVersion::Draft_07);

    // Should throw when trying to output
    expect(fn(): array => $objectSchema->toArray())
        ->toThrow(
            SchemaException::class,
            'Feature "Unevaluated properties validation" is not supported in Draft 7',
        );
});

it('generates correct schema structure for complex scenarios', function (): void {
    // Schema with both regular properties and unevaluatedProperties
    $objectSchema = Schema::object('complex', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::integer('age')->minimum(0),
        )
        ->additionalProperties(true)
        ->unevaluatedProperties(Schema::string()->minLength(2));

    $schemaArray = $objectSchema->toArray();

    // Verify schema structure
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('required', ['name']);
    expect($schemaArray)->toHaveKey('additionalProperties', true);
    expect($schemaArray)->toHaveKey('unevaluatedProperties');
    expect($schemaArray['unevaluatedProperties'])->toBe([
        'type' => 'string',
        'minLength' => 2,
    ]);

    // Test basic validation (defined properties)
    expect($objectSchema->isValid([
        'name' => 'John',
        'age' => 30,
    ]))->toBeTrue();

    // Required property validation still works
    expect($objectSchema->isValid([
        'age' => 30,
    ]))->toBeFalse();

    expect(fn() => $objectSchema->validate([
        'age' => 30,
    ]))->toThrow(SchemaException::class);
});

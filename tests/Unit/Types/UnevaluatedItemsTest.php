<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use ReflectionClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can set unevaluatedItems to false', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    $schemaArray = $arraySchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedItems', false);
    expect($schemaArray)->toHaveKey('items');

    // Test basic validation (unevaluatedItems validation requires a full JSON Schema validator)
    expect($arraySchema->isValid(['hello', 'world']))->toBeTrue();

    // Note: Full unevaluatedItems validation requires a complete JSON Schema validator
    // This package generates schemas but doesn't implement the complex unevaluated logic
});

it('can set unevaluatedItems to true', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(true);

    $schemaArray = $arraySchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedItems', true);

    // Test basic validation
    expect($arraySchema->isValid(['hello']))->toBeTrue();

    // Note: unevaluatedItems: true generates correct schema structure
    // Actual unevaluated item validation would be handled by a JSON Schema validator
});

it('can set unevaluatedItems to a schema', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(
            Schema::integer()->minimum(0),
        );

    $schemaArray = $arraySchema->toArray();

    expect($schemaArray)->toHaveKey('unevaluatedItems');
    expect($schemaArray['unevaluatedItems'])->toBe([
        'type' => 'integer',
        'minimum' => 0,
    ]);

    // Test basic validation
    expect($arraySchema->isValid(['hello']))->toBeTrue();

    // Note: unevaluatedItems with schema generates correct structure
    // Complex validation logic would be implemented by a full JSON Schema validator
});

it('throws exception when using unevaluatedItems with Draft 07', function (): void {
    expect(
        fn(): ArraySchema => Schema::array('items', SchemaVersion::Draft_07)
            ->unevaluatedItems(false),
    )->toThrow(
        SchemaException::class,
        'Feature "Unevaluated items validation" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );
});

it('works with Draft 2019-09', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    expect($arraySchema->toArray())->toHaveKey('unevaluatedItems', false);
    expect($arraySchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2019-09/schema');
});

it('works with Draft 2020-12', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2020_12)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    expect($arraySchema->toArray())->toHaveKey('unevaluatedItems', false);
    expect($arraySchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
});

it('detects unevaluatedItems feature correctly', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($arraySchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->toContain('unevaluatedItems');
});

it('does not include unevaluatedItems feature when not used', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string());

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($arraySchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->not->toContain('unevaluatedItems');
});

it('can combine with other array properties', function (): void {
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->contains(Schema::string()->minLength(3))
        ->minContains(1)
        ->maxContains(5)
        ->unevaluatedItems(false)
        ->minItems(2)
        ->maxItems(10);

    $schemaArray = $arraySchema->toArray();

    expect($schemaArray)->toHaveKey('items');
    expect($schemaArray)->toHaveKey('contains');
    expect($schemaArray)->toHaveKey('minContains', 1);
    expect($schemaArray)->toHaveKey('maxContains', 5);
    expect($schemaArray)->toHaveKey('unevaluatedItems', false);
    expect($schemaArray)->toHaveKey('minItems', 2);
    expect($schemaArray)->toHaveKey('maxItems', 10);
});

it('validates version during schema output', function (): void {
    // Create schema with Draft 2019-09
    $arraySchema = Schema::array('items', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    // Change version to Draft 07 after setting unevaluatedItems
    $arraySchema->version(SchemaVersion::Draft_07);

    // Should throw when trying to output
    expect(fn(): array => $arraySchema->toArray())
        ->toThrow(
            SchemaException::class,
            'Feature "Unevaluated items validation" is not supported in Draft 7',
        );
});

it('generates correct schema structure for complex scenarios', function (): void {
    // Schema with items, contains, and unevaluatedItems
    $arraySchema = Schema::array('complex', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->contains(Schema::string()->minLength(3))
        ->minContains(1)
        ->unevaluatedItems(Schema::integer()->minimum(0));

    $schemaArray = $arraySchema->toArray();

    // Verify schema structure
    expect($schemaArray)->toHaveKey('items');
    expect($schemaArray)->toHaveKey('contains');
    expect($schemaArray)->toHaveKey('minContains', 1);
    expect($schemaArray)->toHaveKey('unevaluatedItems');
    expect($schemaArray['unevaluatedItems'])->toBe([
        'type' => 'integer',
        'minimum' => 0,
    ]);

    // Test basic validation (items validation still works)
    expect($arraySchema->isValid(['hello', 'world']))->toBeTrue();

    // Contains validation still works
    expect($arraySchema->isValid(['hi', 'no']))->toBeFalse(); // No string >= 3 chars

    expect(fn() => $arraySchema->validate(['hi', 'no']))
        ->toThrow(SchemaException::class);
});

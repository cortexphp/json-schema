<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use ReflectionClass;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Enums\SchemaFeature;
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
    $schema = Schema::array('numbers', SchemaVersion::Draft_2019_09)
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
    Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->description('List of numbers')
        ->minContains(-1);
})->throws(SchemaException::class, 'minContains must be greater than or equal to 0');

it('throws an exception if the maxContains is less than 0', function (): void {
    Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->description('List of numbers')
        ->maxContains(-1);
})->throws(SchemaException::class, 'maxContains must be greater than or equal to 0');

it('throws exception when minContains is greater than maxContains', function (): void {
    Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->description('List of numbers')
        ->maxContains(2)
        ->minContains(5);
})->throws(SchemaException::class, 'minContains cannot be greater than maxContains');

it('throws exception when maxContains is less than minContains', function (): void {
    Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->description('List of numbers')
        ->minContains(5)
        ->maxContains(2);
})->throws(SchemaException::class, 'maxContains cannot be less than minContains');

it('accepts boundary values for minContains and maxContains', function (): void {
    // Test that 0 is acceptable for both minContains and maxContains
    $arraySchema = Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->minContains(0)
        ->maxContains(0)
        ->contains(Schema::number());

    expect($arraySchema->toArray())->toHaveKey('minContains', 0);
    expect($arraySchema->toArray())->toHaveKey('maxContains', 0);

    // Test that equal values are acceptable
    $schema2 = Schema::array('numbers', SchemaVersion::Draft_2019_09)
        ->minContains(3)
        ->maxContains(3)
        ->contains(Schema::number());

    expect($schema2->toArray())->toHaveKey('minContains', 3);
    expect($schema2->toArray())->toHaveKey('maxContains', 3);
});

it('allows equal values for minContains and maxContains', function (): void {
    // Test that setting minContains equal to maxContains is allowed
    // This kills the GreaterToGreaterOrEqual mutation on line 56
    expect(
        fn(): ArraySchema => Schema::array('test', SchemaVersion::Draft_2019_09)
            ->maxContains(5)
            ->minContains(5),  // Equal to maxContains - should be allowed
    )->not->toThrow(SchemaException::class);

    // Test the reverse order too
    expect(
        fn(): ArraySchema => Schema::array('test', SchemaVersion::Draft_2019_09)
            ->minContains(3)
            ->maxContains(3),  // Equal to minContains - should be allowed
    )->not->toThrow(SchemaException::class);

    // Test with 0 values
    expect(
        fn(): ArraySchema => Schema::array('test', SchemaVersion::Draft_2019_09)
            ->minContains(0)
            ->maxContains(0),
    )->not->toThrow(SchemaException::class);
});

it('validates minContains and maxContains feature support', function (): void {
    // Test that minContains/maxContains require Draft 2019-09
    expect(
        fn(): ArraySchema => Schema::array('test')
            ->minContains(1),
    )->toThrow(SchemaException::class, 'not supported in Draft 7');

    expect(
        fn(): ArraySchema => Schema::array('test')
            ->maxContains(1),
    )->toThrow(SchemaException::class, 'not supported in Draft 7');

    // Should work with Draft 2019-09
    expect(
        fn(): ArraySchema => Schema::array('test', SchemaVersion::Draft_2019_09)
            ->minContains(1)
            ->maxContains(2),
    )->not->toThrow(SchemaException::class);
});

it('can handle unevaluated items feature', function (): void {
    // Test with boolean value
    $schemaWithBoolean = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(false);

    $arraySchema = $schemaWithBoolean->toArray();
    expect($arraySchema)->toHaveKey('unevaluatedItems', false);

    // Test with schema value
    $schemaWithSchema = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->unevaluatedItems(Schema::number());

    $arrayWithSchema = $schemaWithSchema->toArray();
    expect($arrayWithSchema)->toHaveKey('unevaluatedItems');
    expect($arrayWithSchema['unevaluatedItems'])->toHaveKey('type', 'number');
    expect($arrayWithSchema['unevaluatedItems'])->not->toHaveKey('$schema');  // Should not include schema ref
    expect($arrayWithSchema['unevaluatedItems'])->not->toHaveKey('title');  // Should not include title
});

it('validates unevaluated items feature support', function (): void {
    // Test that unevaluatedItems requires Draft 2019-09
    expect(
        fn(): ArraySchema => Schema::array('test')
            ->unevaluatedItems(false),
    )->toThrow(SchemaException::class, 'not supported in Draft 7');

    // Should work with Draft 2019-09
    expect(
        fn(): ArraySchema => Schema::array('test', SchemaVersion::Draft_2019_09)
            ->unevaluatedItems(true),
    )->not->toThrow(SchemaException::class);
});

it('correctly collects array-specific features', function (): void {
    $arraySchema = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->minContains(1)
        ->maxContains(3)
        ->unevaluatedItems(false);

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getArrayFeatures');
    $reflectionMethod->setAccessible(true);

    $arrayFeatures = $reflectionMethod->invoke($arraySchema);
    $featureValues = array_map(fn($feature) => $feature->value, $arrayFeatures);

    // Should contain all array-specific features
    expect($featureValues)->toContain('minContains');
    expect($featureValues)->toContain('maxContains');
    expect($featureValues)->toContain('unevaluatedItems');

    // Test schema without array features returns empty array
    $simpleSchema = Schema::array('simple')->items(Schema::string());
    $simpleFeatures = $reflectionMethod->invoke($simpleSchema);
    expect($simpleFeatures)->toBeEmpty();
});

it('properly merges parent and array features in getUsedFeatures', function (): void {
    // Create schema with both parent features and array features
    $arraySchema = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->items(Schema::string())
        ->minContains(1)
        ->maxContains(3)
        ->unevaluatedItems(false)
        ->if(Schema::array()->minItems(1))
        ->then(Schema::array()->maxItems(10));

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');
    $reflectionMethod->setAccessible(true);

    $allFeatures = $reflectionMethod->invoke($arraySchema);
    $featureValues = array_map(fn($feature) => $feature->value, $allFeatures);

    // Should contain array-specific features
    expect($featureValues)->toContain('minContains');
    expect($featureValues)->toContain('maxContains');
    expect($featureValues)->toContain('unevaluatedItems');

    // Should also contain parent features from conditionals
    expect($featureValues)->toContain('if');
    expect($featureValues)->toContain('then');

    // Test that array_merge is working correctly (testing the UnwrapArrayMerge mutation)
    // Compare with direct call to parent method to ensure merger is happening
    $getParentFeaturesMethod = $reflection->getParentClass()->getMethod('getUsedFeatures');
    $getParentFeaturesMethod->setAccessible(true);

    $parentFeatures = $getParentFeaturesMethod->invoke($arraySchema);

    $getArrayFeaturesMethod = $reflection->getMethod('getArrayFeatures');
    $getArrayFeaturesMethod->setAccessible(true);

    $arrayOnlyFeatures = $getArrayFeaturesMethod->invoke($arraySchema);

    // Total features should be more than just array features or just parent features
    expect(count($allFeatures))->toBeGreaterThan(count($arrayOnlyFeatures));
    expect(count($allFeatures))->toBeGreaterThan(count($parentFeatures));
});

it('returns correct feature collection structure', function (): void {
    $arraySchema = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->minContains(1)
        ->unevaluatedItems(true);

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getArrayFeatures');
    $reflectionMethod->setAccessible(true);

    $features = $reflectionMethod->invoke($arraySchema);

    // Should return an array (not empty as per AlwaysReturnEmptyArray mutation)
    expect($features)->toBeArray();
    expect($features)->not->toBeEmpty();

    // Each item should be a SchemaFeature enum
    foreach ($features as $feature) {
        expect($feature)->toBeInstanceOf(SchemaFeature::class);
    }

    // Should contain the expected features
    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->toContain('minContains');
    expect($featureValues)->toContain('unevaluatedItems');
});

it('handles toArray parameters correctly for unevaluated items', function (): void {
    $arraySchema = Schema::array('test', SchemaVersion::Draft_2019_09)
        ->items(Schema::string('item-title'))
        ->unevaluatedItems(Schema::number('number-title'));

    $schemaArray = $arraySchema->toArray();

    // UnevaluatedItems schema should not include $schema or title (testing the FalseToTrue mutations)
    expect($schemaArray['unevaluatedItems'])->not->toHaveKey('$schema');
    expect($schemaArray['unevaluatedItems'])->not->toHaveKey('title');
    expect($schemaArray['unevaluatedItems'])->toHaveKey('type', 'number');
});

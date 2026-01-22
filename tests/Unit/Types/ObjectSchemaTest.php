<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use ReflectionClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\IntegerSchema;
use Opis\JsonSchema\Errors\ValidationError;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(ObjectSchema::class);

it('can create a basic object schema', function (): void {
    $objectSchema = Schema::object('user')
        ->description('User schema')
        ->properties(
            Schema::string('name')
                ->description('Name of the user')
                ->minLength(3)
                ->maxLength(255)
                ->required(),
            Schema::string('email')
                ->format(SchemaFormat::Email)
                ->required(),
            Schema::integer('age')
                ->minimum(18)
                ->maximum(150),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
    expect($schemaArray)->toHaveKey('type', 'object');
    expect($schemaArray)->toHaveKey('title', 'user');
    expect($schemaArray)->toHaveKey('description', 'User schema');
    expect($schemaArray)->toHaveKey('properties.name.type', 'string');
    expect($schemaArray)->toHaveKey('properties.name.minLength', 3);
    expect($schemaArray)->toHaveKey('properties.name.maxLength', 255);
    expect($schemaArray)->toHaveKey('properties.email.type', 'string');
    expect($schemaArray)->toHaveKey('properties.email.format', 'email');
    expect($schemaArray)->toHaveKey('properties.age.type', 'integer');
    expect($schemaArray)->toHaveKey('properties.age.minimum', 18);
    expect($schemaArray)->toHaveKey('properties.age.maximum', 150);
    expect($schemaArray)->toHaveKey('required', ['name', 'email']);
    expect($objectSchema->getPropertyKeys())->toBe(['name', 'email', 'age']);

    // Validation tests
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30,
    ]))->not->toThrow(SchemaException::class);

    // Missing required property
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'age' => 30,
    ]))->toThrow(
        SchemaException::class,
        'The required properties (email) are missing',
    );

    // Invalid property type
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => '30', // string instead of integer
    ]))->toThrow(
        SchemaException::class,
        'The properties must match schema: age',
    );

    // Invalid property value
    expect(fn() => $objectSchema->validate([
        'name' => 'Jo', // too short
        'email' => 'john@example.com',
        'age' => 30,
    ]))->toThrow(
        SchemaException::class,
        'The properties must match schema: name',
    );
});

it('can get the underlying errors', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->format(SchemaFormat::Email),
        );

    try {
        $objectSchema->validate([
            'name' => 'John Doe',
            'email' => 'foo',
        ]);
    } catch (SchemaException $schemaException) {
        expect($schemaException->getMessage())->toBe('The properties must match schema: email');
        expect($schemaException->getErrors())->toBe([
            '/email' => [
                "The data must match the 'email' format",
            ],
        ]);
        expect($schemaException->getError())->toBeInstanceOf(ValidationError::class);

        throw $schemaException;
    }
})->throws(SchemaException::class);

it('can create an object schema with additional properties control', function (): void {
    $objectSchema = Schema::object('config')
        ->description('Configuration object')
        ->properties(
            Schema::string('name')->required(),
            Schema::string('type')->required(),
        )
        ->additionalProperties(false);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('additionalProperties', false);
    expect($schemaArray)->toHaveKey('required', ['name', 'type']);

    // Validation tests
    expect(fn() => $objectSchema->validate([
        'name' => 'config1',
        'type' => 'test',
        'extra' => 'not allowed', // additional property
    ]))->toThrow(
        SchemaException::class,
        'Additional object properties are not allowed: extra',
    );

    expect(fn() => $objectSchema->validate([
        'name' => 'config1',
        'type' => 'test',
    ]))->not->toThrow(SchemaException::class);
});

it('can create an object schema with additional properties schema', function (): void {
    $objectSchema = Schema::object('config')
        ->description('Configuration object')
        ->properties(
            Schema::string('name')->required(),
            Schema::string('type')->required(),
        )
        ->additionalProperties(Schema::string()->minLength(3));

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray['additionalProperties'])->toHaveKey('type', 'string');
    expect($schemaArray['additionalProperties'])->toHaveKey('minLength', 3);

    // Validation tests - valid additional property
    expect(fn() => $objectSchema->validate([
        'name' => 'config1',
        'type' => 'test',
        'extra' => 'valid', // additional property matching schema
    ]))->not->toThrow(SchemaException::class);

    // Validation tests - invalid additional property (too short)
    expect(fn() => $objectSchema->validate([
        'name' => 'config1',
        'type' => 'test',
        'extra' => 'no', // additional property not matching schema
    ]))->toThrow(
        SchemaException::class,
        'All additional object properties must match schema: extra',
    );

    // Validation tests - invalid additional property (wrong type)
    expect(fn() => $objectSchema->validate([
        'name' => 'config1',
        'type' => 'test',
        'extra' => 123, // additional property not matching schema
    ]))->toThrow(
        SchemaException::class,
        'All additional object properties must match schema: extra',
    );
});

it('can create an object schema with property count constraints', function (): void {
    $objectSchema = Schema::object('metadata')
        ->description('Metadata object')
        ->properties(
            Schema::string('key1'),
            Schema::string('key2'),
            Schema::string('key3'),
        )
        ->minProperties(2)
        ->maxProperties(3);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('minProperties', 2);
    expect($schemaArray)->toHaveKey('maxProperties', 3);

    // Validation tests
    expect(fn() => $objectSchema->validate([
        'key1' => 'value1',
    ]))->toThrow(
        SchemaException::class,
        'Object must have at least 2 properties, 1 found',
    );

    expect(fn() => $objectSchema->validate([
        'key1' => 'value1',
        'key2' => 'value2',
        'key3' => 'value3',
        'key4' => 'value4',
    ]))->toThrow(
        SchemaException::class,
        'Object must have at most 3 properties, 4 found',
    );

    expect(fn() => $objectSchema->validate([
        'key1' => 'value1',
        'key2' => 'value2',
    ]))->not->toThrow(SchemaException::class);
});

it('can specify a propertyNames schema', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
        )
        // ->additionalProperties(true)
        ->propertyNames(Schema::string()->pattern('^[a-zA-Z]+$'));

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('propertyNames.pattern', '^[a-zA-Z]+$');

    // Validation tests
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $objectSchema->validate([
        'name' => 123, // invalid property name pattern
    ]))->toThrow(SchemaException::class, 'The properties must match schema: name');
});

it('can create an object schema with pattern properties', function (): void {
    $objectSchema = Schema::object('config')
        ->patternProperty('^prefix_', Schema::string()->minLength(5))
        ->patternProperties([
            '^[A-Z][a-z]+$' => Schema::string(),
            '^\d+$' => Schema::number(),
        ]);

    $schemaArray = $objectSchema->toArray();

    // Check schema structure
    expect($schemaArray)->toHaveKey('patternProperties');
    expect($schemaArray['patternProperties'])->toHaveKey('^prefix_');
    expect($schemaArray['patternProperties'])->toHaveKey('^[A-Z][a-z]+$');
    expect($schemaArray['patternProperties'])->toHaveKey('^\d+$');

    // Valid data tests
    expect(fn() => $objectSchema->validate([
        'prefix_hello' => 'world123',  // Matches ^prefix_ and meets minLength
        'Name' => 'John',              // Matches ^[A-Z][a-z]+$
        '123' => 42,                   // Matches ^\d+$
    ]))->not->toThrow(SchemaException::class);

    // Invalid pattern property value (too short)
    expect(fn() => $objectSchema->validate([
        'prefix_hello' => 'hi',  // Matches pattern but fails minLength
    ]))->toThrow(SchemaException::class);

    // Invalid pattern property type
    expect(fn() => $objectSchema->validate([
        '123' => 'not a number',  // Matches pattern but wrong type
    ]))->toThrow(SchemaException::class);
});

it('can combine pattern properties with regular properties', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::integer('age')->required(),
        )
        ->patternProperty('^custom_', Schema::string())
        ->additionalProperties(false);

    $schemaArray = $objectSchema->toArray();

    // Check schema structure
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('patternProperties');
    expect($schemaArray)->toHaveKey('additionalProperties', false);

    // Valid data
    expect(fn() => $objectSchema->validate([
        'name' => 'John',
        'age' => 30,
        'custom_field' => 'value',
    ]))->not->toThrow(SchemaException::class);

    // Missing required property
    expect(fn() => $objectSchema->validate([
        'name' => 'John',
        'custom_field' => 'value',
    ]))->toThrow(SchemaException::class);

    // Invalid additional property (doesn't match pattern)
    expect(fn() => $objectSchema->validate([
        'name' => 'John',
        'age' => 30,
        'invalid_field' => 'value',
    ]))->toThrow(SchemaException::class);
});

it('correctly collects used features from all sources', function (): void {
    // Test that getUsedFeatures properly collects features from parent, unevaluated properties, and dependent schemas
    // Use Draft 2019-09 to support these features
    $objectSchema = Schema::object('complex', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name')->required())
        ->unevaluatedProperties(false)
        ->dependentSchema('name', Schema::string('dependent')->required());

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    // Should contain UnevaluatedProperties and DependentSchemas features
    $featureValues = array_map(fn($feature) => $feature->value, $features);

    expect($featureValues)->toContain('unevaluatedProperties');
    expect($featureValues)->toContain('dependentSchemas');

    // Verify features are properly deduplicated (no duplicates in array)
    expect($featureValues)->toBe(array_unique($featureValues));
});

it('collects unevaluated properties features when set', function (): void {
    // Use Draft 2019-09 to support unevaluated properties
    $objectSchema = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'))
        ->unevaluatedProperties(Schema::string());

    $objectWithoutUnevaluated = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'));

    // Use reflection to access protected methods
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUnevaluatedPropertiesFeatures');

    $featuresWithUnevaluated = $reflectionMethod->invoke($objectSchema);
    $featuresWithoutUnevaluated = $reflectionMethod->invoke($objectWithoutUnevaluated);

    // Object with unevaluated properties should return the feature
    expect($featuresWithUnevaluated)->toHaveCount(1);
    expect($featuresWithUnevaluated[0]->value)->toBe('unevaluatedProperties');

    // Object without unevaluated properties should return empty array
    expect($featuresWithoutUnevaluated)->toBeEmpty();
});

it('collects dependent schemas features when set', function (): void {
    // Use Draft 2019-09 to support dependent schemas
    $objectSchema = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'))
        ->dependentSchema('name', Schema::string('dependent'));

    $objectWithoutDependent = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'));

    // Use reflection to access protected methods
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getDependentSchemasFeatures');

    $featuresWithDependent = $reflectionMethod->invoke($objectSchema);
    $featuresWithoutDependent = $reflectionMethod->invoke($objectWithoutDependent);

    // Object with dependent schemas should return the feature
    expect($featuresWithDependent)->toHaveCount(1);
    expect($featuresWithDependent[0]->value)->toBe('dependentSchemas');

    // Object without dependent schemas should return empty array
    expect($featuresWithoutDependent)->toBeEmpty();
});

it('properly deduplicates features in getUsedFeatures', function (): void {
    // Create a schema that might have duplicate features from different sources
    // Use Draft 2019-09 to support these features
    $objectSchema = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'))
        ->unevaluatedProperties(true)
        ->dependentSchema('name', Schema::string('dependent'));

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    // Get feature values for comparison
    $featureValues = array_map(fn($feature) => $feature->value, $features);

    // Should not contain duplicates
    expect($featureValues)->toBe(array_unique($featureValues));

    // Should return indexed array (not associative)
    expect(array_keys($featureValues))->toBe(range(0, count($featureValues) - 1));
});

it('returns correct array structure from getUsedFeatures', function (): void {
    // Use Draft 2019-09 to support unevaluated properties
    $objectSchema = Schema::object('test', SchemaVersion::Draft_2019_09)
        ->properties(Schema::string('name'))
        ->unevaluatedProperties(false);

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    // Should return an array
    expect($features)->toBeArray();

    // Should not be empty (has at least unevaluated properties feature)
    expect($features)->not->toBeEmpty();

    // Each item should be a SchemaFeature enum
    foreach ($features as $feature) {
        expect($feature)->toBeInstanceOf(SchemaFeature::class);
    }

    // Should be a regular indexed array, not associative
    expect(array_keys($features))->toBe(range(0, count($features) - 1));
});

it('includes parent class features in getUsedFeatures', function (): void {
    // Create an ObjectSchema that uses parent features directly on the object itself
    // Use if-then-else conditional which should trigger parent features
    $objectSchema = Schema::object('test')
        ->properties(Schema::string('name')->required())
        ->if(Schema::object()->properties(Schema::string('name')))
        ->then(Schema::object()->properties(Schema::string('type')->required()))
        ->else(Schema::object()->properties(Schema::string('other')));

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);
    $featureValues = array_map(fn($feature) => $feature->value, $features);

    // Should contain parent conditional features
    expect($featureValues)->toContain('if');
    expect($featureValues)->toContain('then');
    expect($featureValues)->toContain('else');

    // Test that an ObjectSchema without parent features has fewer features
    $simpleObjectSchema = Schema::object('simple')
        ->properties(Schema::string('name'));

    $simpleFeaturesResult = $reflectionMethod->invoke($simpleObjectSchema);
    $simpleFeatureValues = array_map(fn($feature) => $feature->value, $simpleFeaturesResult);

    // Simple object should not have conditional features
    expect($simpleFeatureValues)->not->toContain('if');
    expect($simpleFeatureValues)->not->toContain('then');
    expect($simpleFeatureValues)->not->toContain('else');

    // This proves that parent::getUsedFeatures() is necessary to collect these parent features
});

it('returns correct properties with getProperties method', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::integer('age'),
            Schema::string('email')->required(),
        );

    $properties = $objectSchema->getProperties();

    expect($properties)->toBeArray()
        ->and($properties)->toHaveCount(3)
        ->and($properties)->toHaveKey('name')
        ->and($properties)->toHaveKey('age')
        ->and($properties)->toHaveKey('email');

    // Verify the returned schemas are correct types
    expect($properties['name'])->toBeInstanceOf(StringSchema::class)
        ->and($properties['age'])->toBeInstanceOf(IntegerSchema::class)
        ->and($properties['email'])->toBeInstanceOf(StringSchema::class);
});

it('returns correct required properties with getRequiredProperties method', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::integer('age'),
            Schema::string('email')->required(),
        );

    $requiredProperties = $objectSchema->getRequiredProperties();

    expect($requiredProperties)->toBeArray()
        ->and($requiredProperties)->toHaveCount(2)
        ->and($requiredProperties)->toContain('name')
        ->and($requiredProperties)->toContain('email')
        ->and($requiredProperties)->not->toContain('age');
});

it('correctly identifies when schema has properties with hasProperties method', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name'),
        );

    $objectWithoutProperties = Schema::object('empty');

    expect($objectSchema->hasProperties())->toBeTrue()
        ->and($objectWithoutProperties->hasProperties())->toBeFalse();
});

it('correctly identifies when schema has required properties with hasRequiredProperties method', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name')->required(),
            Schema::integer('age'),
        );

    $objectWithoutRequired = Schema::object('user')
        ->properties(
            Schema::string('name'),
            Schema::integer('age'),
        );

    $emptyObject = Schema::object('empty');

    expect($objectSchema->hasRequiredProperties())->toBeTrue()
        ->and($objectWithoutRequired->hasRequiredProperties())->toBeFalse()
        ->and($emptyObject->hasRequiredProperties())->toBeFalse();
});

it('can mark all properties as required with requireAll method', function (): void {
    $objectSchema = Schema::object('user')
        ->properties(
            Schema::string('name'),
            Schema::integer('age'),
            Schema::string('email'),
        )
        ->requireAll();

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)//->toHaveKey('required', ['name', 'age', 'email'])
        ->and($objectSchema->getRequiredProperties())->toHaveCount(3)
        ->and($objectSchema->getRequiredProperties())->toContain('name')
        ->and($objectSchema->getRequiredProperties())->toContain('age')
        ->and($objectSchema->getRequiredProperties())->toContain('email');

    // Validation test - all properties are now required
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'age' => 30,
    ]))->toThrow(
        SchemaException::class,
        'The required properties (email) are missing',
    );

    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]))->toThrow(
        SchemaException::class,
        'The required properties (age) are missing',
    );

    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'age' => 30,
        'email' => 'john@example.com',
    ]))->not->toThrow(SchemaException::class);
});

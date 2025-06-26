<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;

afterEach(function (): void {
    // Always reset to default after each test
    SchemaFactory::resetDefaultVersion();
});

it('has correct schema version enum values', function (): void {
    expect(SchemaVersion::Draft_07->value)->toBe('http://json-schema.org/draft-07/schema#');
    expect(SchemaVersion::Draft_2019_09->value)->toBe('https://json-schema.org/draft/2019-09/schema');
    expect(SchemaVersion::Draft_2020_12->value)->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('has correct schema version names', function (): void {
    expect(SchemaVersion::Draft_07->getName())->toBe('Draft 7');
    expect(SchemaVersion::Draft_2019_09->getName())->toBe('Draft 2019-09');
    expect(SchemaVersion::Draft_2020_12->getName())->toBe('Draft 2020-12');
});

it('has correct schema version years', function (): void {
    expect(SchemaVersion::Draft_07->getYear())->toBe(2018);
    expect(SchemaVersion::Draft_2019_09->getYear())->toBe(2019);
    expect(SchemaVersion::Draft_2020_12->getYear())->toBe(2020);
});

it('has correct schema version feature support', function (): void {
    $draft07 = SchemaVersion::Draft_07;
    $draft201909 = SchemaVersion::Draft_2019_09;
    $draft202012 = SchemaVersion::Draft_2020_12;

    // Draft 07 features (available in all versions)
    expect($draft07->supports(SchemaFeature::IfThenElse))->toBeTrue();
    expect($draft201909->supports(SchemaFeature::IfThenElse))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::IfThenElse))->toBeTrue();

    expect($draft07->supports(SchemaFeature::ContentMediaType))->toBeTrue();
    expect($draft201909->supports(SchemaFeature::ContentMediaType))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::ContentMediaType))->toBeTrue();

    // Draft 2019-09 new features
    expect($draft07->supports(SchemaFeature::Anchor))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::Anchor))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::Anchor))->toBeTrue();

    expect($draft07->supports(SchemaFeature::Defs))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::Defs))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::Defs))->toBeTrue();

    expect($draft07->supports(SchemaFeature::UnevaluatedProperties))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::UnevaluatedProperties))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::UnevaluatedProperties))->toBeTrue();

    expect($draft07->supports(SchemaFeature::DependentRequired))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::DependentRequired))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::DependentRequired))->toBeTrue();

    expect($draft07->supports(SchemaFeature::ContentSchema))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::ContentSchema))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::ContentSchema))->toBeTrue();

    expect($draft07->supports(SchemaFeature::Deprecated))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::Deprecated))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::Deprecated))->toBeTrue();

    // 2019-09 only features (replaced in 2020-12)
    expect($draft07->supports(SchemaFeature::RecursiveRefLegacy))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::RecursiveRefLegacy))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::RecursiveRefLegacy))->toBeFalse();

    expect($draft07->supports(SchemaFeature::RecursiveRef))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::RecursiveRef))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::RecursiveRef))->toBeFalse(); // Replaced by $dynamicRef in 2020-12

    // Draft 2020-12 features
    expect($draft07->supports(SchemaFeature::DynamicRef))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::DynamicRef))->toBeFalse();
    expect($draft202012->supports(SchemaFeature::DynamicRef))->toBeTrue();

    expect($draft07->supports(SchemaFeature::PrefixItems))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::PrefixItems))->toBeFalse();
    expect($draft202012->supports(SchemaFeature::PrefixItems))->toBeTrue();

    // 2020-12 vocabulary and format changes
    expect($draft07->supports(SchemaFeature::FormatAnnotation))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::FormatAnnotation))->toBeFalse();
    expect($draft202012->supports(SchemaFeature::FormatAnnotation))->toBeTrue();

    expect($draft07->supports(SchemaFeature::UnevaluatedVocabulary))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::UnevaluatedVocabulary))->toBeFalse();
    expect($draft202012->supports(SchemaFeature::UnevaluatedVocabulary))->toBeTrue();

    expect($draft07->supports(SchemaFeature::UnicodeRegex))->toBeFalse();
    expect($draft201909->supports(SchemaFeature::UnicodeRegex))->toBeFalse();
    expect($draft202012->supports(SchemaFeature::UnicodeRegex))->toBeTrue();
});

it('supports enum-based feature checks', function (): void {
    $draft202012 = SchemaVersion::Draft_2020_12;

    // Test enum-based feature check
    expect($draft202012->supports(SchemaFeature::PrefixItems))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::UnicodeRegex))->toBeTrue();

    $draft07 = SchemaVersion::Draft_07;
    expect($draft07->supports(SchemaFeature::PrefixItems))->toBeFalse();
    expect($draft07->supports(SchemaFeature::IfThenElse))->toBeTrue();
});

it('provides feature metadata through enum', function (): void {
    $feature = SchemaFeature::PrefixItems;

    expect($feature->getMinimumVersion())->toBe(SchemaVersion::Draft_2020_12);
    expect($feature->getMaximumVersion())->toBeNull();
    expect($feature->getDescription())->toContain('tuple');
    expect($feature->wasIntroducedIn(SchemaVersion::Draft_2020_12))->toBeTrue();
    expect($feature->wasIntroducedIn(SchemaVersion::Draft_2019_09))->toBeFalse();
    expect($feature->wasRemovedIn(SchemaVersion::Draft_2020_12))->toBeFalse();

    // Test a feature that was removed
    $recursiveFeature = SchemaFeature::RecursiveRef;
    expect($recursiveFeature->getMaximumVersion())->toBe(SchemaVersion::Draft_2019_09);
    expect($recursiveFeature->wasRemovedIn(SchemaVersion::Draft_2020_12))->toBeTrue();
});

it('has correct default and latest versions', function (): void {
    expect(SchemaVersion::default())->toBe(SchemaVersion::Draft_07);
    expect(SchemaVersion::latest())->toBe(SchemaVersion::Draft_2020_12);
});

it('can create schema factory with version parameter', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft_2020_12);

    expect($stringSchema)->toBeInstanceOf(StringSchema::class);
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_2020_12);
});

it('can manage schema factory default version', function (): void {
    // Test default version
    $schema = SchemaFactory::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_07);

    // Test setting global default
    SchemaFactory::setDefaultVersion(SchemaVersion::Draft_2020_12);
    $schema = SchemaFactory::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_2020_12);

    // Test reset to default
    SchemaFactory::resetDefaultVersion();
    $schema = SchemaFactory::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_07);
});

it('includes correct schema version in output', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft_07);
    $draft202012Schema = SchemaFactory::string('test', SchemaVersion::Draft_2020_12);

    $draft07Array = $stringSchema->toArray();
    $draft202012Array = $draft202012Schema->toArray();

    expect($draft07Array['$schema'])->toBe('http://json-schema.org/draft-07/schema#');
    expect($draft202012Array['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('can change schema version on existing schema', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft_07);
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_07);

    $stringSchema->version(SchemaVersion::Draft_2020_12);
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_2020_12);

    $array = $stringSchema->toArray();
    expect($array['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('supports versions for all schema types', function (): void {
    $version = SchemaVersion::Draft_2020_12;

    $stringSchema = SchemaFactory::string('test', $version);
    $numberSchema = SchemaFactory::number('test', $version);
    $integerSchema = SchemaFactory::integer('test', $version);
    $booleanSchema = SchemaFactory::boolean('test', $version);
    $arraySchema = SchemaFactory::array('test', $version);
    $objectSchema = SchemaFactory::object('test', $version);
    $nullSchema = SchemaFactory::null('test', $version);
    $unionSchema = SchemaFactory::union([SchemaType::String, SchemaType::Number], 'test', $version);
    $mixedSchema = SchemaFactory::mixed('test', $version);

    $schemas = [
        $stringSchema, $numberSchema, $integerSchema, $booleanSchema,
        $arraySchema, $objectSchema, $nullSchema, $unionSchema, $mixedSchema,
    ];

    foreach ($schemas as $schema) {
        expect($schema->getVersion())->toBe($version);
        $array = $schema->toArray();
        expect($array['$schema'])->toBe($version->value);
    }
});

it('supports versions for from methods', function (): void {
    $version = SchemaVersion::Draft_2020_12;

    // Test fromClass
    $objectSchema = SchemaFactory::fromClass(new class () {
        public string $name = 'test';
    }, true, $version);
    expect($objectSchema->getVersion())->toBe($version);

    // Test fromClosure
    $closureSchema = SchemaFactory::fromClosure(fn(string $name): string => $name, $version);
    expect($closureSchema->getVersion())->toBe($version);

    // Test fromEnum
    $enumSchema = SchemaFactory::fromEnum(SchemaType::class, $version);
    expect($enumSchema->getVersion())->toBe($version);
});

it('can exclude schema version from output', function (): void {
    $stringSchema = SchemaFactory::string('test');
    $arrayWithoutRef = $stringSchema->toArray(false);
    $arrayWithRef = $stringSchema->toArray(true);

    expect($arrayWithoutRef)->not->toHaveKey('$schema');
    expect($arrayWithRef)->toHaveKey('$schema', SchemaVersion::Draft_07->value);
});

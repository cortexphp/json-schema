<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;

afterEach(function (): void {
    // Always reset to default after each test
    Schema::resetDefaultVersion();
});

it('has correct schema version enum values', function (): void {
    expect(SchemaVersion::Draft_06->value)->toBe('http://json-schema.org/draft-06/schema#')
        ->and(SchemaVersion::Draft_07->value)
        ->toBe('http://json-schema.org/draft-07/schema#')
        ->and(SchemaVersion::Draft_2019_09->value)
        ->toBe('https://json-schema.org/draft/2019-09/schema')
        ->and(SchemaVersion::Draft_2020_12->value)
        ->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('has correct schema version names', function (): void {
    expect(SchemaVersion::Draft_06->getName())->toBe('Draft 6')
        ->and(SchemaVersion::Draft_07->getName())
        ->toBe('Draft 7')
        ->and(SchemaVersion::Draft_2019_09->getName())
        ->toBe('Draft 2019-09')
        ->and(SchemaVersion::Draft_2020_12->getName())
        ->toBe('Draft 2020-12');
});

it('has correct schema version years', function (): void {
    expect(SchemaVersion::Draft_06->getYear())->toBe(2017)
        ->and(SchemaVersion::Draft_07->getYear())
        ->toBe(2018)
        ->and(SchemaVersion::Draft_2019_09->getYear())
        ->toBe(2019)
        ->and(SchemaVersion::Draft_2020_12->getYear())
        ->toBe(2020);
});

it('has correct schema version feature support', function (): void {
    $draft06 = SchemaVersion::Draft_06;
    $draft07 = SchemaVersion::Draft_07;
    $draft201909 = SchemaVersion::Draft_2019_09;
    $draft202012 = SchemaVersion::Draft_2020_12;

    // Draft 07 features
    expect($draft06->supports(SchemaFeature::IfThenElse))->toBeFalse();
    expect($draft07->supports(SchemaFeature::IfThenElse))->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::IfThenElse))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::IfThenElse))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::ContentMediaType))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::ContentMediaType))
        ->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::ContentMediaType))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::ContentMediaType))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::Comment))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::Comment))
        ->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::Comment))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::Comment))
        ->toBeTrue();

    // Draft 2019-09 new features
    expect($draft06->supports(SchemaFeature::Anchor))->toBeFalse();
    expect($draft07->supports(SchemaFeature::Anchor))->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::Anchor))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::Anchor))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::Defs))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::Defs))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::Defs))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::Defs))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::UnevaluatedProperties))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::UnevaluatedProperties))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::UnevaluatedProperties))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::UnevaluatedProperties))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::DependentRequired))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::DependentRequired))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::DependentRequired))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::DependentRequired))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::ContentSchema))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::ContentSchema))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::ContentSchema))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::ContentSchema))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::Deprecated))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::Deprecated))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::Deprecated))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::Deprecated))
        ->toBeTrue();

    // 2019-09 only features (replaced in 2020-12)
    expect($draft06->supports(SchemaFeature::RecursiveRefLegacy))->toBeFalse();
    expect($draft07->supports(SchemaFeature::RecursiveRefLegacy))->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::RecursiveRefLegacy))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::RecursiveRefLegacy))
        ->toBeFalse()
        ->and($draft06->supports(SchemaFeature::RecursiveRef))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::RecursiveRef))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::RecursiveRef))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::RecursiveRef))
        ->toBeFalse(); // Replaced by $dynamicRef in 2020-12

    // Draft 2020-12 features
    expect($draft06->supports(SchemaFeature::DynamicRef))->toBeFalse();
    expect($draft07->supports(SchemaFeature::DynamicRef))->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::DynamicRef))
        ->toBeFalse()
        ->and($draft202012->supports(SchemaFeature::DynamicRef))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::PrefixItems))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::PrefixItems))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::PrefixItems))
        ->toBeFalse()
        ->and($draft202012->supports(SchemaFeature::PrefixItems))
        ->toBeTrue();

    // 2020-12 vocabulary and format changes
    expect($draft06->supports(SchemaFeature::FormatAnnotation))->toBeFalse();
    expect($draft07->supports(SchemaFeature::FormatAnnotation))->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::FormatAnnotation))
        ->toBeFalse()
        ->and($draft202012->supports(SchemaFeature::FormatAnnotation))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::UnevaluatedVocabulary))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::UnevaluatedVocabulary))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::UnevaluatedVocabulary))
        ->toBeFalse()
        ->and($draft202012->supports(SchemaFeature::UnevaluatedVocabulary))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::UnicodeRegex))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::UnicodeRegex))
        ->toBeFalse()
        ->and($draft201909->supports(SchemaFeature::UnicodeRegex))
        ->toBeFalse()
        ->and($draft202012->supports(SchemaFeature::UnicodeRegex))
        ->toBeTrue();

    // Draft 07 format additions
    expect($draft06->supports(SchemaFeature::FormatDate))->toBeFalse();
    expect($draft07->supports(SchemaFeature::FormatDate))->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::FormatDate))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::FormatDate))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::FormatIdnEmail))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::FormatIdnEmail))
        ->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::FormatIdnEmail))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::FormatIdnEmail))
        ->toBeTrue()
        ->and($draft06->supports(SchemaFeature::FormatIdnHostname))
        ->toBeFalse()
        ->and($draft07->supports(SchemaFeature::FormatIdnHostname))
        ->toBeTrue()
        ->and($draft201909->supports(SchemaFeature::FormatIdnHostname))
        ->toBeTrue()
        ->and($draft202012->supports(SchemaFeature::FormatIdnHostname))
        ->toBeTrue();
});

it('supports enum-based feature checks', function (): void {
    $draft202012 = SchemaVersion::Draft_2020_12;

    // Test enum-based feature check
    expect($draft202012->supports(SchemaFeature::PrefixItems))->toBeTrue();
    expect($draft202012->supports(SchemaFeature::UnicodeRegex))->toBeTrue();

    $draft07 = SchemaVersion::Draft_07;
    expect($draft07->supports(SchemaFeature::PrefixItems))->toBeFalse()
        ->and($draft07->supports(SchemaFeature::IfThenElse))
        ->toBeTrue();
});

it('provides feature metadata through enum', function (): void {
    $feature = SchemaFeature::PrefixItems;

    expect($feature->getMinimumVersion())->toBe(SchemaVersion::Draft_2020_12)
        ->and($feature->getMaximumVersion())
        ->toBeNull()
        ->and($feature->getDescription())
        ->toContain('tuple')
        ->and($feature->wasIntroducedIn(SchemaVersion::Draft_2020_12))
        ->toBeTrue()
        ->and($feature->wasIntroducedIn(SchemaVersion::Draft_2019_09))
        ->toBeFalse()
        ->and($feature->wasRemovedIn(SchemaVersion::Draft_2020_12))
        ->toBeFalse();

    // Test a feature that was removed
    $recursiveFeature = SchemaFeature::RecursiveRef;
    expect($recursiveFeature->getMaximumVersion())->toBe(SchemaVersion::Draft_2019_09)
        ->and($recursiveFeature->wasRemovedIn(SchemaVersion::Draft_2020_12))
        ->toBeTrue();
});

it('has correct default and latest versions', function (): void {
    expect(SchemaVersion::default())->toBe(SchemaVersion::Draft_2020_12)
        ->and(SchemaVersion::latest())
        ->toBe(SchemaVersion::Draft_2020_12);
});

it('can create schema factory with version parameter', function (): void {
    $stringSchema = Schema::string('test', SchemaVersion::Draft_2020_12);

    expect($stringSchema->getVersion())
        ->toBe(SchemaVersion::Draft_2020_12);
});

it('can manage schema factory default version', function (): void {
    // Test default version
    $schema = Schema::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_2020_12);

    // Test setting global default
    Schema::setDefaultVersion(SchemaVersion::Draft_2019_09);
    $schema = Schema::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Test reset to default
    Schema::resetDefaultVersion();
    $schema = Schema::string('test');
    expect($schema->getVersion())->toBe(SchemaVersion::Draft_2020_12);
});

it('includes correct schema version in output', function (): void {
    $draft06Schema = Schema::string('test', SchemaVersion::Draft_06);
    $stringSchema = Schema::string('test', SchemaVersion::Draft_07);
    $draft202012Schema = Schema::string('test', SchemaVersion::Draft_2020_12);

    $draft06Array = $draft06Schema->toArray();
    $draft07Array = $stringSchema->toArray();
    $draft202012Array = $draft202012Schema->toArray();

    expect($draft06Array['$schema'])->toBe('http://json-schema.org/draft-06/schema#')
        ->and($draft07Array['$schema'])
        ->toBe('http://json-schema.org/draft-07/schema#')
        ->and($draft202012Array['$schema'])
        ->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('can change schema version on existing schema', function (): void {
    $stringSchema = Schema::string('test', SchemaVersion::Draft_07);
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_07);

    $stringSchema->version(SchemaVersion::Draft_2020_12);
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_2020_12);

    $array = $stringSchema->toArray();
    expect($array['$schema'])->toBe('https://json-schema.org/draft/2020-12/schema');
});

it('supports versions for all schema types', function (): void {
    $version = SchemaVersion::Draft_2020_12;

    $stringSchema = Schema::string('test', $version);
    $numberSchema = Schema::number('test', $version);
    $integerSchema = Schema::integer('test', $version);
    $booleanSchema = Schema::boolean('test', $version);
    $arraySchema = Schema::array('test', $version);
    $objectSchema = Schema::object('test', $version);
    $nullSchema = Schema::null('test', $version);
    $unionSchema = Schema::union([SchemaType::String, SchemaType::Number], 'test', $version);
    $mixedSchema = Schema::mixed('test', $version);

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
    $objectSchema = Schema::fromClass(new class () {
        public string $name = 'test';
    }, true, $version);
    expect($objectSchema->getVersion())->toBe($version);

    // Test fromClosure
    $closureSchema = Schema::fromClosure(fn(string $name): string => $name, $version);
    expect($closureSchema->getVersion())->toBe($version);

    // Test fromEnum
    $enumSchema = Schema::fromEnum(SchemaType::class, $version);
    expect($enumSchema->getVersion())->toBe($version);
});

it('can exclude schema version from output', function (): void {
    $stringSchema = Schema::string('test');
    $arrayWithoutRef = $stringSchema->toArray(false);
    $arrayWithRef = $stringSchema->toArray(true);

    expect($arrayWithoutRef)->not->toHaveKey('$schema')
        ->and($arrayWithRef)
        ->toHaveKey('$schema', SchemaVersion::Draft_2020_12->value);
});

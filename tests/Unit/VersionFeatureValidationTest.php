<?php

declare(strict_types=1);

use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('validates conditional features against schema version', function (): void {
    // Draft 07 supports if-then-else (this should work)
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);
    $conditionSchema = SchemaFactory::string('condition');

    expect(fn(): StringSchema => $stringSchema->if($conditionSchema))->not->toThrow(SchemaException::class);

    // All versions support if-then-else, so this should work for all versions
    $draft201909Schema = SchemaFactory::string('test', SchemaVersion::Draft201909);
    $draft202012Schema = SchemaFactory::string('test', SchemaVersion::Draft202012);

    expect(fn(): StringSchema => $draft201909Schema->if($conditionSchema))->not->toThrow(SchemaException::class);
    expect(fn(): StringSchema => $draft202012Schema->if($conditionSchema))->not->toThrow(SchemaException::class);
});

it('outputs version-appropriate definition keywords', function (): void {
    $stringSchema = SchemaFactory::string('definition');

    // Draft 07 should use 'definitions'
    $objectSchema = SchemaFactory::object('test', SchemaVersion::Draft07);
    $objectSchema->addDefinition('myDef', $stringSchema);

    $draft07Array = $objectSchema->toArray();

    expect($draft07Array)->toHaveKey('definitions');
    expect($draft07Array)->not->toHaveKey('$defs');

    // Draft 2019-09+ should use '$defs'
    $draft201909Schema = SchemaFactory::object('test', SchemaVersion::Draft201909);
    $draft201909Schema->addDefinition('myDef', $stringSchema);

    $draft201909Array = $draft201909Schema->toArray();

    expect($draft201909Array)->toHaveKey('$defs');
    expect($draft201909Array)->not->toHaveKey('definitions');

    // Draft 2020-12 should also use '$defs'
    $draft202012Schema = SchemaFactory::object('test', SchemaVersion::Draft202012);
    $draft202012Schema->addDefinition('myDef', $stringSchema);

    $draft202012Array = $draft202012Schema->toArray();

    expect($draft202012Array)->toHaveKey('$defs');
    expect($draft202012Array)->not->toHaveKey('definitions');
});

it('validates features during schema output', function (): void {
    // Create a schema with conditional logic
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);
    $conditionSchema = SchemaFactory::string('condition');
    $thenSchema = SchemaFactory::string('then');

    $stringSchema->if($conditionSchema)->then($thenSchema);

    // This should work since Draft 07 supports if-then-else
    expect(fn(): array => $stringSchema->toArray())->not->toThrow(SchemaException::class);

    $output = $stringSchema->toArray();
    expect($output)->toHaveKey('if');
    expect($output)->toHaveKey('then');
});

it('provides helpful error messages for unsupported features', function (): void {
    // We need to create a scenario where a feature isn't supported
    // Since all our current features are supported in Draft 07+, let's test the validation logic directly
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);

    // Test the validation method directly with a hypothetical unsupported feature
    expect(function () use ($stringSchema): void {
        $reflection = new ReflectionClass($stringSchema);
        $reflectionMethod = $reflection->getMethod('validateFeatureSupport');
        $reflectionMethod->setAccessible(true);

        // Test with a feature that's only in 2020-12
        $reflectionMethod->invoke($stringSchema, SchemaFeature::PrefixItems);
    })->toThrow(
        SchemaException::class,
        'Feature "Prefix items for tuple validation" is not supported in Draft 7. Minimum version required: Draft 2020-12.',
    );
});

it('correctly identifies version-appropriate keywords', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);
    $draft201909Schema = SchemaFactory::string('test', SchemaVersion::Draft201909);

    $reflection07 = new ReflectionClass($stringSchema);
    $reflectionMethod = $reflection07->getMethod('getVersionAppropriateKeyword');
    $reflectionMethod->setAccessible(true);

    $reflection201909 = new ReflectionClass($draft201909Schema);
    $method201909 = $reflection201909->getMethod('getVersionAppropriateKeyword');
    $method201909->setAccessible(true);

    // Draft 07 should use 'definitions'
    expect($reflectionMethod->invoke($stringSchema, '$defs', 'definitions'))->toBe('definitions');

    // Draft 2019-09+ should use '$defs'
    expect($method201909->invoke($draft201909Schema, '$defs', 'definitions'))->toBe('$defs');
});

it('collects features from all traits correctly', function (): void {
    $objectSchema = SchemaFactory::object('test', SchemaVersion::Draft201909);
    $stringSchema = SchemaFactory::string('condition');
    $definitionSchema = SchemaFactory::string('definition');

    // Add conditional and definition features
    $objectSchema->if($stringSchema);
    $objectSchema->addDefinition('myDef', $definitionSchema);

    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');
    $reflectionMethod->setAccessible(true);

    $features = $reflectionMethod->invoke($objectSchema);

    // Should include both conditional and definition features
    expect($features)->toContain(SchemaFeature::If);
    expect($features)->toContain(SchemaFeature::Defs);
});

it('includes IfThenElse feature when complete conditional construct is used', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);
    $conditionSchema = SchemaFactory::string('condition');
    $thenSchema = SchemaFactory::string('then');
    $elseSchema = SchemaFactory::string('else');

    // Test with if-then construct
    $stringSchema->if($conditionSchema)->then($thenSchema);

    $reflection = new ReflectionClass($stringSchema);
    $reflectionMethod = $reflection->getMethod('getConditionalFeatures');
    $reflectionMethod->setAccessible(true);

    $features = $reflectionMethod->invoke($stringSchema);

    expect($features)->toContain(SchemaFeature::If);
    expect($features)->toContain(SchemaFeature::Then);
    expect($features)->toContain(SchemaFeature::IfThenElse);

    // Test with if-else construct
    $schema2 = SchemaFactory::string('test2', SchemaVersion::Draft07);
    $schema2->if($conditionSchema)->else($elseSchema);

    $features2 = $reflectionMethod->invoke($schema2);

    expect($features2)->toContain(SchemaFeature::If);
    expect($features2)->toContain(SchemaFeature::Else);
    expect($features2)->toContain(SchemaFeature::IfThenElse);

    // Test with complete if-then-else construct
    $schema3 = SchemaFactory::string('test3', SchemaVersion::Draft07);
    $schema3->if($conditionSchema)->then($thenSchema)->else($elseSchema);

    $features3 = $reflectionMethod->invoke($schema3);

    expect($features3)->toContain(SchemaFeature::If);
    expect($features3)->toContain(SchemaFeature::Then);
    expect($features3)->toContain(SchemaFeature::Else);
    expect($features3)->toContain(SchemaFeature::IfThenElse);
});

it('validates deprecated feature against schema version', function (): void {
    // Draft 07 should reject deprecated feature
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);

    expect(fn(): StringSchema => $stringSchema->deprecated())->toThrow(
        SchemaException::class,
        'Feature "Property deprecation annotation" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );

    // Draft 2019-09+ should accept deprecated feature
    $draft201909Schema = SchemaFactory::string('test', SchemaVersion::Draft201909);
    $draft202012Schema = SchemaFactory::string('test', SchemaVersion::Draft202012);

    expect(fn(): StringSchema => $draft201909Schema->deprecated())->not->toThrow(SchemaException::class);
    expect(fn(): StringSchema => $draft202012Schema->deprecated())->not->toThrow(SchemaException::class);
});

it('validates array contains count features against schema version', function (): void {
    // Draft 07 should reject minContains/maxContains
    $arraySchema = SchemaFactory::array('test', SchemaVersion::Draft07);

    expect(fn(): ArraySchema => $arraySchema->minContains(1))->toThrow(
        SchemaException::class,
        'Feature "Minimum number of contains matches" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );
    expect(fn(): ArraySchema => $arraySchema->maxContains(5))->toThrow(
        SchemaException::class,
        'Feature "Maximum number of contains matches" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );

    // Draft 2019-09+ should accept contains count features
    $draft201909Array = SchemaFactory::array('test', SchemaVersion::Draft201909);
    $draft202012Array = SchemaFactory::array('test', SchemaVersion::Draft202012);

    expect(fn(): ArraySchema => $draft201909Array->minContains(1))->not->toThrow(SchemaException::class);
    expect(fn(): ArraySchema => $draft201909Array->maxContains(5))->not->toThrow(SchemaException::class);
    expect(fn(): ArraySchema => $draft202012Array->minContains(1))->not->toThrow(SchemaException::class);
    expect(fn(): ArraySchema => $draft202012Array->maxContains(5))->not->toThrow(SchemaException::class);
});

it('detects metadata and read/write features correctly', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft201909);

    // Add metadata features
    $stringSchema->deprecated()->readOnly();

    $reflection = new ReflectionClass($stringSchema);
    $reflectionMethod = $reflection->getMethod('getMetadataFeatures');
    $reflectionMethod->setAccessible(true);

    $getReadWriteMethod = $reflection->getMethod('getReadWriteFeatures');
    $getReadWriteMethod->setAccessible(true);

    $metadataFeatures = $reflectionMethod->invoke($stringSchema);
    $readWriteFeatures = $getReadWriteMethod->invoke($stringSchema);

    expect($metadataFeatures)->toContain(SchemaFeature::Deprecated);
    expect($readWriteFeatures)->toContain(SchemaFeature::ReadOnly);

    // Test that features are included in overall feature detection
    $getUsedMethod = $reflection->getMethod('getUsedFeatures');
    $getUsedMethod->setAccessible(true);

    $allFeatures = $getUsedMethod->invoke($stringSchema);
    expect($allFeatures)->toContain(SchemaFeature::Deprecated);
    expect($allFeatures)->toContain(SchemaFeature::ReadOnly);
});

it('detects array contains count features correctly', function (): void {
    $arraySchema = SchemaFactory::array('test', SchemaVersion::Draft201909);
    $arraySchema->minContains(1)->maxContains(5);

    $reflection = new ReflectionClass($arraySchema);
    $reflectionMethod = $reflection->getMethod('getArrayFeatures');
    $reflectionMethod->setAccessible(true);

    $features = $reflectionMethod->invoke($arraySchema);

    expect($features)->toContain(SchemaFeature::MinContains);
    expect($features)->toContain(SchemaFeature::MaxContains);

    // Test that features are included in overall feature detection
    $getUsedMethod = $reflection->getMethod('getUsedFeatures');
    $getUsedMethod->setAccessible(true);

    $allFeatures = $getUsedMethod->invoke($arraySchema);
    expect($allFeatures)->toContain(SchemaFeature::MinContains);
    expect($allFeatures)->toContain(SchemaFeature::MaxContains);
});

it('validates format features against schema version', function (): void {
    // Draft 07 should reject duration and uuid formats
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);

    expect(fn(): StringSchema => $stringSchema->format(SchemaFormat::Duration))->toThrow(
        SchemaException::class,
        'Feature "ISO 8601 duration format validation" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );

    expect(fn(): StringSchema => $stringSchema->format(SchemaFormat::Uuid))->toThrow(
        SchemaException::class,
        'Feature "RFC 4122 UUID format validation" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );

    // Draft 07 should accept other formats like email
    expect(fn(): StringSchema => $stringSchema->format(SchemaFormat::Email))->not->toThrow(SchemaException::class);
    expect(fn(): StringSchema => $stringSchema->format(SchemaFormat::DateTime))->not->toThrow(SchemaException::class);

    // Draft 2019-09+ should accept duration and uuid formats
    $draft201909Schema = SchemaFactory::string('test', SchemaVersion::Draft201909);
    $draft202012Schema = SchemaFactory::string('test', SchemaVersion::Draft202012);

    expect(fn(): StringSchema => $draft201909Schema->format(SchemaFormat::Duration))->not->toThrow(
        SchemaException::class,
    );
    expect(fn(): StringSchema => $draft201909Schema->format(SchemaFormat::Uuid))->not->toThrow(SchemaException::class);
    expect(fn(): StringSchema => $draft202012Schema->format(SchemaFormat::Duration))->not->toThrow(
        SchemaException::class,
    );
    expect(fn(): StringSchema => $draft202012Schema->format(SchemaFormat::Uuid))->not->toThrow(SchemaException::class);
});

it('detects format features correctly', function (): void {
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft201909);

    // Add version-specific format features
    $stringSchema->format(SchemaFormat::Duration);

    $reflection = new ReflectionClass($stringSchema);
    $reflectionMethod = $reflection->getMethod('getFormatFeatures');
    $reflectionMethod->setAccessible(true);

    $formatFeatures = $reflectionMethod->invoke($stringSchema);

    expect($formatFeatures)->toContain(SchemaFeature::FormatDuration);

    // Test UUID format
    $uuidSchema = SchemaFactory::string('uuid', SchemaVersion::Draft201909);
    $uuidSchema->format(SchemaFormat::Uuid);

    $uuidFormatFeatures = $reflectionMethod->invoke($uuidSchema);
    expect($uuidFormatFeatures)->toContain(SchemaFeature::FormatUuid);

    // Test that features are included in overall feature detection
    $getUsedMethod = $reflection->getMethod('getUsedFeatures');
    $getUsedMethod->setAccessible(true);

    $allFeatures = $getUsedMethod->invoke($stringSchema);
    expect($allFeatures)->toContain(SchemaFeature::FormatDuration);

    $allUuidFeatures = $getUsedMethod->invoke($uuidSchema);
    expect($allUuidFeatures)->toContain(SchemaFeature::FormatUuid);

    // Test that non-version-specific formats don't add features
    $emailSchema = SchemaFactory::string('email', SchemaVersion::Draft07);
    $emailSchema->format(SchemaFormat::Email);

    $emailFormatFeatures = $reflectionMethod->invoke($emailSchema);
    expect($emailFormatFeatures)->toBeEmpty();
});

it('allows string formats for custom validation', function (): void {
    // String formats should not trigger validation (for custom formats)
    $stringSchema = SchemaFactory::string('test', SchemaVersion::Draft07);

    expect(fn(): StringSchema => $stringSchema->format('custom-format'))->not->toThrow(SchemaException::class);
    expect(fn(): StringSchema => $stringSchema->format('duration'))->not->toThrow(
        SchemaException::class,
    ); // String, not enum

    // Verify that string formats don't add features
    $reflection = new ReflectionClass($stringSchema);
    $reflectionMethod = $reflection->getMethod('getFormatFeatures');
    $reflectionMethod->setAccessible(true);

    $formatFeatures = $reflectionMethod->invoke($stringSchema);
    expect($formatFeatures)->toBeEmpty();
});

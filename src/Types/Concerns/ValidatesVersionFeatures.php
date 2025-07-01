<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait ValidatesVersionFeatures
{
    /**
     * Validate that a feature is supported by the current schema version.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    protected function validateFeatureSupport(SchemaFeature $schemaFeature): void
    {
        if (! $this->getVersion()->supports($schemaFeature)) {
            throw new SchemaException(
                sprintf(
                    'Feature "%s" is not supported in %s. Minimum version required: %s.',
                    $schemaFeature->getDescription(),
                    $this->getVersion()->getName(),
                    $schemaFeature->getMinimumVersion()->getName(),
                ),
            );
        }
    }

    /**
     * Validate that multiple features are supported by the current schema version.
     *
     * @param array<\Cortex\JsonSchema\Enums\SchemaFeature> $features
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    protected function validateFeaturesSupport(array $features): void
    {
        foreach ($features as $feature) {
            $this->validateFeatureSupport($feature);
        }
    }

    /**
     * Check if a feature is supported by the current schema version without throwing.
     */
    protected function isFeatureSupported(SchemaFeature $schemaFeature): bool
    {
        return $this->getVersion()->supports($schemaFeature);
    }

    /**
     * Get version-appropriate keyword name for a feature.
     * Some keywords changed names between versions.
     */
    protected function getVersionAppropriateKeyword(string $modernKeyword, string $legacyKeyword): string
    {
        // For features that were renamed, use the appropriate keyword for the version
        return match ($modernKeyword) {
            '$defs' => $this->getVersion() === SchemaVersion::Draft_07 ? 'definitions' : '$defs',
            default => $modernKeyword,
        };
    }

    /**
     * Conditionally add a feature to the schema array if supported by the version.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addFeatureIfSupported(
        array $schema,
        SchemaFeature $schemaFeature,
        string $keyword,
        mixed $value,
    ): array {
        if ($this->isFeatureSupported($schemaFeature) && $value !== null) {
            $schema[$keyword] = $value;
        }

        return $schema;
    }

    /**
     * Get features that should be validated when building the schema output.
     * Override in specific schema types to define which features they use.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getUsedFeatures(): array
    {
        return [];
    }

    /**
     * Validate all features used by this schema type.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    protected function validateAllUsedFeatures(): void
    {
        $this->validateFeaturesSupport($this->getUsedFeatures());
    }
}

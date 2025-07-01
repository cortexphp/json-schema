<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\Concerns\HasProperties;

final class ObjectSchema extends AbstractSchema
{
    use HasProperties;

    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(SchemaType::Object, $title, $schemaVersion);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = parent::toArray($includeSchemaRef, $includeTitle);

        return $this->addPropertiesToSchema($schema);
    }

    /**
     * Get features used by this schema including properties features.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    #[Override]
    protected function getUsedFeatures(): array
    {
        $features = [
            ...parent::getUsedFeatures(),
            ...$this->getUnevaluatedPropertiesFeatures(),
            ...$this->getDependentSchemasFeatures(),
        ];

        // Remove duplicates by using feature values as keys
        $uniqueFeatures = [];

        foreach ($features as $feature) {
            $uniqueFeatures[$feature->value] = $feature;
        }

        return array_values($uniqueFeatures);
    }
}

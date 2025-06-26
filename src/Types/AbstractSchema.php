<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\Concerns\HasRef;
use Cortex\JsonSchema\Types\Concerns\HasEnum;
use Cortex\JsonSchema\Types\Concerns\HasConst;
use Cortex\JsonSchema\Types\Concerns\HasTitle;
use Cortex\JsonSchema\Types\Concerns\HasFormat;
use Cortex\JsonSchema\Types\Concerns\HasMetadata;
use Cortex\JsonSchema\Types\Concerns\HasRequired;
use Cortex\JsonSchema\Types\Concerns\HasReadWrite;
use Cortex\JsonSchema\Types\Concerns\HasValidation;
use Cortex\JsonSchema\Types\Concerns\HasDefinitions;
use Cortex\JsonSchema\Types\Concerns\HasDescription;
use Cortex\JsonSchema\Types\Concerns\HasConditionals;
use Cortex\JsonSchema\Types\Concerns\ValidatesVersionFeatures;

abstract class AbstractSchema implements Schema
{
    use HasRef;
    use HasEnum;
    use HasConst;
    use HasTitle;
    use HasFormat;
    use HasMetadata;
    use HasRequired;
    use HasReadWrite;
    use HasValidation;
    use HasDescription;
    use HasConditionals;
    use HasDefinitions;
    use ValidatesVersionFeatures;

    protected SchemaVersion $schemaVersion = SchemaVersion::Draft07;

    /**
     * @param \Cortex\JsonSchema\Enums\SchemaType|array<array-key, \Cortex\JsonSchema\Enums\SchemaType> $type
     */
    public function __construct(
        protected SchemaType|array $type,
        ?string $title = null,
        ?SchemaVersion $schemaVersion = null,
    ) {
        $this->title = $title;
        $this->schemaVersion = $schemaVersion ?? SchemaVersion::default();
    }

    /**
     * Set the JSON Schema version for this schema.
     */
    public function version(SchemaVersion $schemaVersion): static
    {
        $this->schemaVersion = $schemaVersion;

        return $this;
    }

    /**
     * Get the JSON Schema version for this schema.
     */
    public function getVersion(): SchemaVersion
    {
        return $this->schemaVersion;
    }

    /**
     * Add null type to schema.
     */
    public function nullable(): static
    {
        if ($this->isNullable()) {
            return $this;
        }

        if (is_array($this->type)) {
            $this->type[] = SchemaType::Null; // @phpstan-ignore assign.propertyType
        } else {
            $this->type = [
                $this->type,
                SchemaType::Null,
            ];
        }

        return $this;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        // Validate that all features used by this schema are supported by the version
        $this->validateAllUsedFeatures();

        $schema = [
            'type' => is_array($this->type)
                ? array_map(static fn(SchemaType $schemaType) => $schemaType->value, $this->type)
                : $this->type->value,
        ];

        if ($includeSchemaRef) {
            $schema['$schema'] = $this->schemaVersion->value;
        }

        $schema = $this->addTitleToSchema($schema, $includeTitle);
        $schema = $this->addFormatToSchema($schema);
        $schema = $this->addDescriptionToSchema($schema);
        $schema = $this->addEnumToSchema($schema);
        $schema = $this->addConstToSchema($schema);
        $schema = $this->addConditionalsToSchema($schema);
        $schema = $this->addMetadataToSchema($schema);
        $schema = $this->addRefToSchema($schema);
        $schema = $this->addDefinitionsToSchema($schema);

        return $this->addReadWriteToSchema($schema);
    }

    /**
     * Convert to JSON.
     */
    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }

    /**
     * Check if the schema allows null values.
     */
    protected function isNullable(): bool
    {
        return is_array($this->type) && in_array(SchemaType::Null, $this->type, true);
    }

    /**
     * Get features used by this schema from all traits.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getUsedFeatures(): array
    {
        $features = [
            ...$this->getConditionalFeatures(),
            ...$this->getDefinitionFeatures(),
            ...$this->getMetadataFeatures(),
            ...$this->getReadWriteFeatures(),
            ...$this->getFormatFeatures(),
        ];

        // Remove duplicates by using feature values as keys
        $uniqueFeatures = [];

        foreach ($features as $feature) {
            $uniqueFeatures[$feature->value] = $feature;
        }

        return array_values($uniqueFeatures);
    }
}

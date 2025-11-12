<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Types\Concerns\HasItems;
use Cortex\JsonSchema\Exceptions\SchemaException;

final class ArraySchema extends AbstractSchema
{
    use HasItems;

    protected ?JsonSchema $contains = null;

    protected ?int $minContains = null;

    protected ?int $maxContains = null;

    protected JsonSchema|bool|null $unevaluatedItems = null;

    /**
     * @var array<int, JsonSchema>
     */
    protected array $prefixItems = [];

    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(SchemaType::Array, $title, $schemaVersion);
    }

    /**
     * Set the schema that array items must contain
     */
    public function contains(JsonSchema $jsonSchema): static
    {
        $this->contains = $jsonSchema;

        return $this;
    }

    /**
     * Set the minimum number of items that must match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function minContains(int $min): static
    {
        if ($min < 0) {
            throw new SchemaException('minContains must be greater than or equal to 0');
        }

        if ($this->maxContains !== null && $min > $this->maxContains) {
            throw new SchemaException('minContains cannot be greater than maxContains');
        }

        $this->validateFeatureSupport(SchemaFeature::MinContains);
        $this->minContains = $min;

        return $this;
    }

    /**
     * Set the maximum number of items that can match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxContains(int $max): static
    {
        if ($max < 0) {
            throw new SchemaException('maxContains must be greater than or equal to 0');
        }

        if ($this->minContains !== null && $max < $this->minContains) {
            throw new SchemaException('maxContains cannot be less than minContains');
        }

        $this->validateFeatureSupport(SchemaFeature::MaxContains);
        $this->maxContains = $max;

        return $this;
    }

    /**
     * Set whether unevaluated items are allowed and optionally their schema.
     * This feature is only available in Draft 2019-09 and later.
     *
     * @param bool|\Cortex\JsonSchema\Contracts\JsonSchema $allowed Whether unevaluated items are allowed, or a schema they must match
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function unevaluatedItems(bool|JsonSchema $allowed): static
    {
        $this->validateFeatureSupport(SchemaFeature::UnevaluatedItems);

        $this->unevaluatedItems = $allowed;

        return $this;
    }

    /**
     * Set prefix items for tuple validation.
     * This feature is only available in Draft 2020-12 and later.
     *
     * @param array<int, \Cortex\JsonSchema\Contracts\JsonSchema> $schemas Array of schemas for tuple validation
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function prefixItems(array $schemas): static
    {
        $this->validateFeatureSupport(SchemaFeature::PrefixItems);

        $this->prefixItems = array_values($schemas);

        return $this;
    }

    /**
     * Convert the schema to an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = parent::toArray($includeSchemaRef, $includeTitle);

        $schema = $this->addItemsToSchema($schema);

        if ($this->prefixItems !== []) {
            $schema['prefixItems'] = array_map(
                static fn(JsonSchema $jsonSchema): array => $jsonSchema->toArray(
                    includeSchemaRef: false,
                    includeTitle: false,
                ),
                $this->prefixItems,
            );
        }

        if ($this->contains instanceof JsonSchema) {
            $schema['contains'] = $this->contains->toArray();
        }

        if ($this->minContains !== null) {
            $schema['minContains'] = $this->minContains;
        }

        if ($this->maxContains !== null) {
            $schema['maxContains'] = $this->maxContains;
        }

        if ($this->unevaluatedItems !== null) {
            $schema['unevaluatedItems'] = $this->unevaluatedItems instanceof JsonSchema
                ? $this->unevaluatedItems->toArray(includeSchemaRef: false, includeTitle: false)
                : $this->unevaluatedItems;
        }

        return $schema;
    }

    /**
     * Get array-specific features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getArrayFeatures(): array
    {
        $features = [];

        if ($this->prefixItems !== []) {
            $features[] = SchemaFeature::PrefixItems;
        }

        if ($this->minContains !== null) {
            $features[] = SchemaFeature::MinContains;
        }

        if ($this->maxContains !== null) {
            $features[] = SchemaFeature::MaxContains;
        }

        if ($this->unevaluatedItems !== null) {
            $features[] = SchemaFeature::UnevaluatedItems;
        }

        return $features;
    }

    /**
     * Override to include array-specific features.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    #[Override]
    protected function getUsedFeatures(): array
    {
        return array_merge(
            parent::getUsedFeatures(),
            $this->getArrayFeatures(),
        );
    }
}

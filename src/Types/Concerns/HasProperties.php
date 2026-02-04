<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasProperties
{
    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\JsonSchema>
     */
    protected array $properties = [];

    /**
     * @var array<int, string>
     */
    protected array $requiredProperties = [];

    /**
     * @var bool|\Cortex\JsonSchema\Contracts\JsonSchema|null
     */
    protected mixed $additionalProperties = null;

    protected ?int $minProperties = null;

    protected ?int $maxProperties = null;

    protected ?JsonSchema $propertyNames = null;

    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\JsonSchema>
     */
    protected array $patternProperties = [];

    /**
     * @var bool|\Cortex\JsonSchema\Contracts\JsonSchema|null
     */
    protected mixed $unevaluatedProperties = null;

    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\JsonSchema>
     */
    protected array $dependentSchemas = [];

    /**
     * Set properties.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function properties(JsonSchema ...$properties): static
    {
        foreach ($properties as $property) {
            $title = $this->resolvePropertyTitle($property);

            if ($title === null) {
                throw new SchemaException('Property must have a title');
            }

            $this->properties[$title] = $property;

            if ($property->isRequired()) {
                $this->requiredProperties[] = $title;
            }
        }

        return $this;
    }

    /**
     * Set whether additional properties are allowed and optionally their schema
     *
     * @param bool|\Cortex\JsonSchema\Contracts\JsonSchema $allowed Whether additional properties are allowed, or a schema they must match
     */
    public function additionalProperties(bool|JsonSchema $allowed): static
    {
        $this->additionalProperties = $allowed;

        return $this;
    }

    /**
     * Set whether unevaluated properties are allowed and optionally their schema.
     * This feature is only available in Draft 2019-09 and later.
     *
     * @param bool|\Cortex\JsonSchema\Contracts\JsonSchema $allowed Whether unevaluated properties are allowed, or a schema they must match
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function unevaluatedProperties(bool|JsonSchema $allowed): static
    {
        $this->validateFeatureSupport(SchemaFeature::UnevaluatedProperties);

        $this->unevaluatedProperties = $allowed;

        return $this;
    }

    /**
     * Set a dependent schema that is applied when a specific property is present.
     * This feature is only available in Draft 2019-09 and later.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function dependentSchema(string $property, JsonSchema $jsonSchema): static
    {
        $this->validateFeatureSupport(SchemaFeature::DependentSchemas);

        $this->dependentSchemas[$property] = $jsonSchema;

        return $this;
    }

    /**
     * Set multiple dependent schemas at once.
     * This feature is only available in Draft 2019-09 and later.
     *
     * @param array<string, \Cortex\JsonSchema\Contracts\JsonSchema> $schemas
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function dependentSchemas(array $schemas): static
    {
        foreach ($schemas as $property => $schema) {
            $this->dependentSchema($property, $schema);
        }

        return $this;
    }

    /**
     * Set the minimum number of properties
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function minProperties(int $min): static
    {
        if ($min < 0) {
            throw new SchemaException('minProperties must be non-negative');
        }

        if ($this->maxProperties !== null && $min > $this->maxProperties) {
            throw new SchemaException('minProperties cannot be greater than maxProperties');
        }

        $this->minProperties = $min;

        return $this;
    }

    /**
     * Set the maximum number of properties
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxProperties(int $max): static
    {
        if ($max < 0) {
            throw new SchemaException('maxProperties must be non-negative');
        }

        if ($this->minProperties !== null && $max < $this->minProperties) {
            throw new SchemaException('maxProperties cannot be less than minProperties');
        }

        $this->maxProperties = $max;

        return $this;
    }

    /**
     * Set the schema for property names
     */
    public function propertyNames(JsonSchema $jsonSchema): static
    {
        $this->propertyNames = $jsonSchema;

        return $this;
    }

    /**
     * Add a pattern property schema.
     */
    public function patternProperty(string $pattern, JsonSchema $jsonSchema): static
    {
        $this->patternProperties[$pattern] = $jsonSchema;

        return $this;
    }

    /**
     * Add multiple pattern property schemas.
     *
     * @param array<string, \Cortex\JsonSchema\Contracts\JsonSchema> $patterns
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function patternProperties(array $patterns): static
    {
        foreach ($patterns as $pattern => $schema) {
            $this->patternProperty($pattern, $schema);
        }

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getPropertyKeys(): array
    {
        return array_keys($this->properties);
    }

    /**
     * @return array<string, \Cortex\JsonSchema\Contracts\JsonSchema>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array<int, string>
     */
    public function getRequiredProperties(): array
    {
        return $this->requiredProperties;
    }

    /**
     * Determine if the schema has properties
     */
    public function hasProperties(): bool
    {
        return $this->properties !== [];
    }

    /**
     * Determine if the schema has required properties
     */
    public function hasRequiredProperties(): bool
    {
        return $this->requiredProperties !== [];
    }

    /**
     * Convenience method to mark all properties as required.
     */
    public function requireAll(): static
    {
        foreach ($this->properties as $name => $property) {
            $this->requiredProperties[] = $name;
        }

        return $this;
    }

    /**
     * Add properties to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addPropertiesToSchema(array $schema): array
    {
        if ($this->properties !== []) {
            $schema['properties'] = [];

            foreach ($this->properties as $name => $prop) {
                $propertySchema = $prop->toArray(includeSchemaRef: false, includeTitle: true);

                // If the property schema has a title and it matches the name,
                // then we don't need to include it in the schema
                if (array_key_exists('title', $propertySchema) && $propertySchema['title'] === $name) {
                    unset($propertySchema['title']);
                }

                $schema['properties'][$name] = $propertySchema;
            }
        }

        if ($this->patternProperties !== []) {
            $schema['patternProperties'] = [];

            foreach ($this->patternProperties as $pattern => $prop) {
                $schema['patternProperties'][$pattern] = $prop->toArray(includeSchemaRef: false, includeTitle: false);
            }
        }

        if ($this->requiredProperties !== []) {
            $schema['required'] = array_values(array_unique($this->requiredProperties));
        }

        if ($this->additionalProperties !== null) {
            $schema['additionalProperties'] = $this->additionalProperties instanceof JsonSchema
                ? $this->additionalProperties->toArray(includeSchemaRef: false, includeTitle: false)
                : $this->additionalProperties;
        }

        if ($this->propertyNames !== null) {
            $schema['propertyNames'] = $this->propertyNames->toArray(includeSchemaRef: false, includeTitle: false);
        }

        if ($this->minProperties !== null) {
            $schema['minProperties'] = $this->minProperties;
        }

        if ($this->maxProperties !== null) {
            $schema['maxProperties'] = $this->maxProperties;
        }

        if ($this->unevaluatedProperties !== null) {
            $schema['unevaluatedProperties'] = $this->unevaluatedProperties instanceof JsonSchema
                ? $this->unevaluatedProperties->toArray(includeSchemaRef: false, includeTitle: false)
                : $this->unevaluatedProperties;
        }

        if ($this->dependentSchemas !== []) {
            $schema['dependentSchemas'] = [];

            foreach ($this->dependentSchemas as $property => $dependentSchema) {
                $schema['dependentSchemas'][$property] = $dependentSchema->toArray(
                    includeSchemaRef: false,
                    includeTitle: false,
                );
            }
        }

        return $schema;
    }

    /**
     * Resolve the property title from a schema instance.
     */
    protected function resolvePropertyTitle(JsonSchema $property): ?string
    {
        return $property->getInitialTitle() ?? $property->getTitle();
    }

    /**
     * Get unevaluated properties features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getUnevaluatedPropertiesFeatures(): array
    {
        if ($this->unevaluatedProperties === null) {
            return [];
        }

        return [SchemaFeature::UnevaluatedProperties];
    }

    /**
     * Get dependent schemas features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getDependentSchemasFeatures(): array
    {
        if ($this->dependentSchemas === []) {
            return [];
        }

        return [SchemaFeature::DependentSchemas];
    }
}

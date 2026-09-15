<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

/**
 * @mixin \Cortex\JsonSchema\Contracts\JsonSchema
 */
trait HasProperties
{
    /**
     * @var array<array-key, \Cortex\JsonSchema\Contracts\JsonSchema>
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
     * @var array<string, list<string>>
     */
    protected array $dependentRequired = [];

    /**
     * Add properties keyed by each schema's title.
     *
     * Repeated calls merge with existing properties rather than replacing them.
     * The last schema for a given name wins.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function properties(JsonSchema ...$properties): static
    {
        foreach ($properties as $index => $property) {
            $title = $this->resolvePropertyTitle($property);

            if ($title === null) {
                throw new SchemaException($this->untitledPropertyMessage((int) $index, $property));
            }

            $this->property($title, $property, $property->isRequired());
        }

        return $this;
    }

    /**
     * Set a named property, optionally marking it as required.
     *
     * Unlike {@see properties()}, the schema does not need a title — `$name`
     * is the property key, so `title` remains metadata. Repeated calls merge
     * with existing properties rather than replacing them. The last schema
     * for a given name wins.
     */
    public function property(string $name, JsonSchema $jsonSchema, bool $required = false): static
    {
        $this->properties[$name] = $jsonSchema;

        if ($required || $jsonSchema->isRequired()) {
            $this->requiredProperties[] = $name;
        }

        return $this;
    }

    /**
     * Mark the given property names as required.
     *
     * Names do not need to match currently defined properties, so a list
     * computed at runtime can be applied before or after {@see properties()}
     * / {@see property()}.
     */
    public function requireProperties(string ...$names): static
    {
        foreach ($names as $name) {
            $this->requiredProperties[] = $name;
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
     * Set dependent required property names.
     * This feature is only available in Draft 2019-09 and later.
     *
     * @param array<string, list<string>> $dependencies
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function dependentRequired(array $dependencies): static
    {
        $this->validateFeatureSupport(SchemaFeature::DependentRequired);

        $this->dependentRequired = $dependencies;

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
        return array_map(strval(...), array_keys($this->properties));
    }

    /**
     * @return array<array-key, \Cortex\JsonSchema\Contracts\JsonSchema>
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
        foreach (array_keys($this->properties) as $name) {
            $this->requiredProperties[] = (string) $name;
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
            $properties = [];

            foreach ($this->properties as $name => $prop) {
                $propertySchema = $prop->toArray(includeSchemaRef: false, includeTitle: true);

                // If the property schema has a title and it matches the name,
                // then we don't need to include it in the schema.
                // Compare as strings so numeric-string keys ('0', '1') match
                // after PHP's integer key coercion.
                if (array_key_exists('title', $propertySchema) && $propertySchema['title'] === (string) $name) {
                    unset($propertySchema['title']);
                }

                $properties[$name] = $propertySchema;
            }

            // PHP stores numeric-string keys as ints, so a map of only "0"/"1"
            // is a list. JSON Schema `properties` must be an object.
            $schema['properties'] = array_is_list($properties) ? (object) $properties : $properties;
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

        if ($this->dependentRequired !== []) {
            $schema['dependentRequired'] = $this->dependentRequired;
        }

        return $schema;
    }

    /**
     * Resolve the property title from a schema instance.
     */
    protected function resolvePropertyTitle(JsonSchema $jsonSchema): ?string
    {
        return $jsonSchema->getInitialTitle() ?? $jsonSchema->getTitle();
    }

    /**
     * Build a diagnostic message for an untitled property.
     */
    protected function untitledPropertyMessage(int $index, JsonSchema $jsonSchema): string
    {
        $parentTitle = $this->getTitle();
        $parentLabel = ($parentTitle !== null && $parentTitle !== '')
            ? sprintf('"%s"', $parentTitle)
            : 'untitled schema';

        return sprintf(
            'Property %d of %s (%s) must have a title',
            $index + 1,
            $parentLabel,
            $jsonSchema::class,
        );
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

    /**
     * Get dependent required features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getDependentRequiredFeatures(): array
    {
        if ($this->dependentRequired === []) {
            return [];
        }

        return [SchemaFeature::DependentRequired];
    }
}

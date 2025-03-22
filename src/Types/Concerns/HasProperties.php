<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasProperties
{
    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\Schema>
     */
    protected array $properties = [];

    /**
     * @var array<int, string>
     */
    protected array $requiredProperties = [];

    /**
     * @var bool|\Cortex\JsonSchema\Contracts\Schema|null
     */
    protected mixed $additionalProperties = null;

    protected ?int $minProperties = null;

    protected ?int $maxProperties = null;

    protected ?Schema $propertyNames = null;

    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\Schema>
     */
    protected array $patternProperties = [];

    /**
     * Set properties.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function properties(Schema ...$properties): static
    {
        foreach ($properties as $property) {
            $title = $property->getTitle();

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
     * @param bool|\Cortex\JsonSchema\Contracts\Schema $allowed Whether additional properties are allowed, or a schema they must match
     */
    public function additionalProperties(bool|Schema $allowed): static
    {
        $this->additionalProperties = $allowed;

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
    public function propertyNames(Schema $schema): static
    {
        $this->propertyNames = $schema;

        return $this;
    }

    /**
     * Add a pattern property schema.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function patternProperty(string $pattern, Schema $schema): static
    {
        // Validate the pattern is a valid regex
        if (@preg_match('/' . $pattern . '/', '') === false) {
            throw new SchemaException('Invalid pattern: ' . $pattern);
        }

        $this->patternProperties[$pattern] = $schema;

        return $this;
    }

    /**
     * Add multiple pattern property schemas.
     *
     * @param array<string, \Cortex\JsonSchema\Contracts\Schema> $patterns
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
                $schema['properties'][$name] = $prop->toArray(includeSchemaRef: false, includeTitle: false);
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
            $schema['additionalProperties'] = $this->additionalProperties instanceof Schema
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

        return $schema;
    }
}

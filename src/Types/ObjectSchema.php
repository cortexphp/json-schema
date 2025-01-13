<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Exceptions\SchemaException;

class ObjectSchema extends AbstractSchema
{
    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\Schema>
     */
    protected array $properties = [];

    /**
     * @var array<int, string>
     */
    public array $requiredProperties = [];

    protected ?bool $additionalProperties = null;

    protected bool|Schema|null $unevaluatedProperties = null;

    protected ?int $minProperties = null;

    protected ?int $maxProperties = null;

    protected ?Schema $propertyNames = null;

    /**
     * @var array<string, array<int, string>>
     */
    protected array $dependentRequired = [];

    /**
     * @var array<string, \Cortex\JsonSchema\Contracts\Schema>
     */
    protected array $dependentSchemas = [];

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Object, $title);
    }

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
     * Allow or disallow additional properties
     */
    public function additionalProperties(bool $allowed): static
    {
        $this->additionalProperties = $allowed;

        return $this;
    }

    /**
     * Get the property keys
     *
     * @return array<int, string>
     */
    public function getPropertyKeys(): array
    {
        return array_keys($this->properties);
    }

    /**
     * Set whether unevaluated properties are allowed and optionally their schema.
     */
    public function unevaluatedProperties(bool|Schema $value): static
    {
        $this->unevaluatedProperties = $value;

        return $this;
    }

    /**
     * Get the unevaluated properties setting
     */
    public function getUnevaluatedProperties(): bool|Schema|null
    {
        return $this->unevaluatedProperties;
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
     * Get the minimum number of properties
     */
    public function getMinProperties(): ?int
    {
        return $this->minProperties;
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
     * Get the maximum number of properties
     */
    public function getMaxProperties(): ?int
    {
        return $this->maxProperties;
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
     * Get the property names schema
     */
    public function getPropertyNames(): ?Schema
    {
        return $this->propertyNames;
    }

    /**
     * Set properties that are required when a property is present
     *
     * @param array<int, string> $requiredProperties
     */
    public function dependentRequired(string $property, array $requiredProperties): static
    {
        $this->dependentRequired[$property] = $requiredProperties;

        return $this;
    }

    /**
     * Get the dependent required properties
     *
     * @return array<string, array<int, string>>
     */
    public function getDependentRequired(): array
    {
        return $this->dependentRequired;
    }

    /**
     * Set a schema that must be valid when a property is present
     */
    public function dependentSchema(string $property, Schema $schema): static
    {
        $this->dependentSchemas[$property] = $schema;

        return $this;
    }

    /**
     * Get the dependent schemas
     *
     * @return array<string, Schema>
     */
    public function getDependentSchemas(): array
    {
        return $this->dependentSchemas;
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

        if ($this->requiredProperties !== []) {
            $schema['required'] = array_values(array_unique($this->requiredProperties));
        }

        if ($this->additionalProperties !== null) {
            $schema['additionalProperties'] = $this->additionalProperties;
        }

        return $schema;
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

        if ($this->propertyNames !== null) {
            $schema['propertyNames'] = $this->propertyNames->toArray($includeSchemaRef, $includeTitle);
        }

        if ($this->dependentRequired !== []) {
            $schema['dependentRequired'] = $this->dependentRequired;
        }

        if ($this->dependentSchemas !== []) {
            $schema['dependentSchemas'] = array_map(
                fn(Schema $schema): array => $schema->toArray($includeSchemaRef, $includeTitle),
                $this->dependentSchemas,
            );
        }

        if ($this->unevaluatedProperties !== null) {
            $schema['unevaluatedProperties'] = $this->unevaluatedProperties instanceof Schema
                ? $this->unevaluatedProperties->toArray($includeSchemaRef, $includeTitle)
                : $this->unevaluatedProperties;
        }

        if ($this->minProperties !== null) {
            $schema['minProperties'] = $this->minProperties;
        }

        if ($this->maxProperties !== null) {
            $schema['maxProperties'] = $this->maxProperties;
        }

        return $this->addPropertiesToSchema($schema);
    }
}

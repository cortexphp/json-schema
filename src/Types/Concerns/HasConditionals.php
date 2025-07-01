<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasConditionals
{
    protected ?Schema $if = null;

    protected ?Schema $then = null;

    protected ?Schema $else = null;

    protected ?Schema $not = null;

    /**
     * @var array<int, Schema>
     */
    protected array $allOf = [];

    /**
     * @var array<int, Schema>
     */
    protected array $anyOf = [];

    /**
     * @var array<int, Schema>
     */
    protected array $oneOf = [];

    /**
     * Set the if condition
     */
    public function if(Schema $schema): static
    {
        // Validate that if-then-else is supported in the current version
        $this->validateFeatureSupport(SchemaFeature::If);

        $this->if = $schema;

        return $this;
    }

    /**
     * Set the then condition
     */
    public function then(Schema $schema): static
    {
        if ($this->if === null) {
            throw new SchemaException('Cannot set then condition without if condition');
        }

        $this->validateFeatureSupport(SchemaFeature::Then);
        $this->validateFeatureSupport(SchemaFeature::IfThenElse);

        $this->then = $schema;

        return $this;
    }

    /**
     * Set the else condition
     */
    public function else(Schema $schema): static
    {
        if ($this->if === null) {
            throw new SchemaException('Cannot set else condition without if condition');
        }

        $this->validateFeatureSupport(SchemaFeature::Else);
        $this->validateFeatureSupport(SchemaFeature::IfThenElse);

        $this->else = $schema;

        return $this;
    }

    /**
     * Set the allOf condition
     */
    public function allOf(Schema ...$schemas): static
    {
        $this->allOf = array_values($schemas);

        return $this;
    }

    /**
     * Set the anyOf condition
     */
    public function anyOf(Schema ...$schemas): static
    {
        $this->anyOf = array_values($schemas);

        return $this;
    }

    /**
     * Set the oneOf condition
     */
    public function oneOf(Schema ...$schemas): static
    {
        $this->oneOf = array_values($schemas);

        return $this;
    }

    /**
     * Set the not condition
     */
    public function not(Schema $schema): static
    {
        $this->not = $schema;

        return $this;
    }

    /**
     * Add conditional fields to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addConditionalsToSchema(array $schema): array
    {
        if ($this->if !== null) {
            $schema['if'] = $this->if->toArray(includeSchemaRef: false);

            if ($this->then !== null) {
                $schema['then'] = $this->then->toArray(includeSchemaRef: false);
            }

            if ($this->else !== null) {
                $schema['else'] = $this->else->toArray(includeSchemaRef: false);
            }
        }

        if ($this->allOf !== []) {
            $schema['allOf'] = array_map(
                static fn(Schema $schema): array => $schema->toArray(includeSchemaRef: false),
                $this->allOf,
            );
        }

        if ($this->anyOf !== []) {
            $schema['anyOf'] = array_map(
                static fn(Schema $schema): array => $schema->toArray(includeSchemaRef: false),
                $this->anyOf,
            );
        }

        if ($this->oneOf !== []) {
            $schema['oneOf'] = array_map(
                static fn(Schema $schema): array => $schema->toArray(includeSchemaRef: false),
                $this->oneOf,
            );
        }

        if ($this->not !== null) {
            $schema['not'] = $this->not->toArray(includeSchemaRef: false);
        }

        return $schema;
    }

    /**
     * Get conditional features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getConditionalFeatures(): array
    {
        $features = [];

        if ($this->if !== null) {
            $features[] = SchemaFeature::If;
        }

        if ($this->then !== null) {
            $features[] = SchemaFeature::Then;
        }

        if ($this->else !== null) {
            $features[] = SchemaFeature::Else;
        }

        // If we have a complete if-then-else construct, include the composite feature
        if ($this->if !== null && ($this->then !== null || $this->else !== null)) {
            $features[] = SchemaFeature::IfThenElse;
        }

        return $features;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasNumericConstraints
{
    protected ?float $minimum = null;

    protected ?float $maximum = null;

    protected ?float $exclusiveMinimum = null;

    protected ?float $exclusiveMaximum = null;

    protected ?float $multipleOf = null;

    /**
     * Set the minimum value (inclusive).
     */
    public function minimum(float $value): static
    {
        $this->minimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (inclusive).
     */
    public function maximum(float $value): static
    {
        $this->maximum = $value;

        return $this;
    }

    /**
     * Set the exclusive minimum value.
     */
    public function exclusiveMinimum(float $value): static
    {
        $this->exclusiveMinimum = $value;

        return $this;
    }

    /**
     * Set the exclusive maximum value.
     */
    public function exclusiveMaximum(float $value): static
    {
        $this->exclusiveMaximum = $value;

        return $this;
    }

    /**
     * Set the multipleOf value.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function multipleOf(float $value): static
    {
        if ($value <= 0) {
            throw new SchemaException('multipleOf must be greater than 0');
        }

        $this->multipleOf = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addNumericConstraintsToSchema(array $schema): array
    {
        if ($this->minimum !== null) {
            $schema['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $schema['maximum'] = $this->maximum;
        }

        if ($this->exclusiveMinimum !== null) {
            $schema['exclusiveMinimum'] = $this->exclusiveMinimum;
        }

        if ($this->exclusiveMaximum !== null) {
            $schema['exclusiveMaximum'] = $this->exclusiveMaximum;
        }

        if ($this->multipleOf !== null) {
            $schema['multipleOf'] = $this->multipleOf;
        }

        return $schema;
    }
}

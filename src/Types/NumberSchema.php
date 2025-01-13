<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Exceptions\SchemaException;

class NumberSchema extends AbstractSchema
{
    protected ?float $minimum = null;

    protected ?float $maximum = null;

    protected ?float $exclusiveMinimum = null;

    protected ?float $exclusiveMaximum = null;

    protected ?float $multipleOf = null;

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Number, $title);
    }

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
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = parent::toArray($includeSchemaRef, $includeTitle);

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

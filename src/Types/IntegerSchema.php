<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Exceptions\SchemaException;

final class IntegerSchema extends AbstractSchema
{
    protected ?int $minimum = null;

    protected ?int $maximum = null;

    protected ?int $exclusiveMinimum = null;

    protected ?int $exclusiveMaximum = null;

    protected ?int $multipleOf = null;

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Integer, $title);
    }

    /**
     * Set the minimum value (inclusive).
     */
    public function minimum(int $value): static
    {
        $this->minimum = $value;

        return $this;
    }

    /**
     * Set the maximum value (inclusive).
     */
    public function maximum(int $value): static
    {
        $this->maximum = $value;

        return $this;
    }

    /**
     * Set the exclusive minimum value.
     */
    public function exclusiveMinimum(int $value): static
    {
        $this->exclusiveMinimum = $value;

        return $this;
    }

    /**
     * Set the exclusive maximum value.
     */
    public function exclusiveMaximum(int $value): static
    {
        $this->exclusiveMaximum = $value;

        return $this;
    }

    /**
     * Set the multipleOf value.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function multipleOf(int $value): static
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

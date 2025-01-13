<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Exceptions\SchemaException;

class ArraySchema extends AbstractSchema
{
    protected ?Schema $items = null;

    protected ?int $minItems = null;

    protected ?int $maxItems = null;

    protected bool $uniqueItems = false;

    protected ?Schema $contains = null;

    protected ?int $minContains = null;

    protected ?int $maxContains = null;

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Array, $title);
    }

    /**
     * Set the schema for validating array items.
     */
    public function items(Schema $schema): static
    {
        $this->items = $schema;

        return $this;
    }

    /**
     * Set the minimum number of items.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function minItems(int $value): static
    {
        if ($value < 0) {
            throw new SchemaException('minItems must be greater than or equal to 0');
        }

        $this->minItems = $value;

        return $this;
    }

    /**
     * Set the maximum number of items.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxItems(int $value): static
    {
        if ($value < 0) {
            throw new SchemaException('maxItems must be greater than or equal to 0');
        }

        if ($this->minItems !== null && $value < $this->minItems) {
            throw new SchemaException('maxItems must be greater than or equal to minItems');
        }

        $this->maxItems = $value;

        return $this;
    }

    /**
     * Set whether items must be unique.
     */
    public function uniqueItems(bool $value = true): static
    {
        $this->uniqueItems = $value;

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

        if ($this->items !== null) {
            $schema['items'] = $this->items->toArray();
        }

        if ($this->minItems !== null) {
            $schema['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $schema['maxItems'] = $this->maxItems;
        }

        if ($this->uniqueItems) {
            $schema['uniqueItems'] = true;
        }

        if ($this->contains !== null) {
            $schema['contains'] = $this->contains->toArray();
        }

        if ($this->minContains !== null) {
            $schema['minContains'] = $this->minContains;
        }

        if ($this->maxContains !== null) {
            $schema['maxContains'] = $this->maxContains;
        }

        return $schema;
    }

    /**
     * Set the schema that array items must contain
     */
    public function contains(Schema $schema): static
    {
        $this->contains = $schema;

        return $this;
    }

    /**
     * Get the contains schema
     */
    public function getContains(): ?Schema
    {
        return $this->contains;
    }

    /**
     * Set the minimum number of items that must match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function minContains(int $min): static
    {
        if ($min < 0) {
            throw new SchemaException('minContains must be non-negative');
        }

        if ($this->maxContains !== null && $min > $this->maxContains) {
            throw new SchemaException('minContains cannot be greater than maxContains');
        }

        $this->minContains = $min;

        return $this;
    }

    /**
     * Get the minimum number of items that must match the contains schema
     */
    public function getMinContains(): ?int
    {
        return $this->minContains;
    }

    /**
     * Set the maximum number of items that can match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxContains(int $max): static
    {
        if ($max < 0) {
            throw new SchemaException('maxContains must be non-negative');
        }

        if ($this->minContains !== null && $max < $this->minContains) {
            throw new SchemaException('maxContains cannot be less than minContains');
        }

        $this->maxContains = $max;

        return $this;
    }

    /**
     * Get the maximum number of items that can match the contains schema
     */
    public function getMaxContains(): ?int
    {
        return $this->maxContains;
    }
}

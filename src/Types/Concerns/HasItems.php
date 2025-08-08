<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasItems
{
    protected ?JsonSchema $items = null;

    protected ?int $minItems = null;

    protected ?int $maxItems = null;

    protected bool $uniqueItems = false;

    /**
     * Set the schema for validating array items.
     */
    public function items(JsonSchema $jsonSchema): static
    {
        $this->items = $jsonSchema;

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
     * Add properties to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addItemsToSchema(array $schema): array
    {
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

        return $schema;
    }
}

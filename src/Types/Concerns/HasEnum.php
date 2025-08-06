<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasEnum
{
    /**
     * @var non-empty-array<int|string|bool|float|null>|null
     */
    protected ?array $enum = null;

    /**
     * Set the allowed enum values.
     *
     * @param non-empty-array<int|string|bool|float|null> $values
     */
    public function enum(array $values): static
    {
        $this->enum = array_values(array_unique($values, SORT_REGULAR));

        return $this;
    }

    /**
     * Add enum values to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addEnumToSchema(array $schema): array
    {
        if ($this->enum !== null) {
            $schema['enum'] = $this->enum;
        }

        return $schema;
    }
}

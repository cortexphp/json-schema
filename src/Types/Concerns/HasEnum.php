<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/**
 * @mixin \Cortex\JsonSchema\Contracts\JsonSchema
 */
trait HasEnum
{
    /**
     * @var non-empty-array<int|string|bool|float|array|null>|null
     */
    protected ?array $enum = null;

    /**
     * Set the allowed enum values.
     *
     * @param non-empty-array<int|string|bool|float|array|null> $values
     */
    public function enum(array $values): static
    {
        $unique = [];

        foreach ($values as $value) {
            if (! in_array($value, $unique, strict: true)) {
                $unique[] = $value;
            }
        }

        /** @var non-empty-array<int|string|bool|float|array|null> $unique */
        $this->enum = $unique;

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

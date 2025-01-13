<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

trait HasConst
{
    /**
     * @var int|string|bool|float|null
     */
    protected mixed $const = null;

    protected bool $hasConst = false;

    /**
     * Set the constant value.
     *
     * @param int|string|bool|float|null $value
     */
    public function const(mixed $value): static
    {
        $this->const = $value;
        $this->hasConst = true;

        return $this;
    }

    /**
     * Add const value to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addConstToSchema(array $schema): array
    {
        if ($this->hasConst) {
            $schema['const'] = $this->const;
        }

        return $schema;
    }
}

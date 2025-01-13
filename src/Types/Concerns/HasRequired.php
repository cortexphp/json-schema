<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasRequired
{
    protected bool $required = false;

    /**
     * Set the schema as required
     */
    public function required(): static
    {
        $this->required = true;

        return $this;
    }

    /**
     * Determine if the schema is required
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}

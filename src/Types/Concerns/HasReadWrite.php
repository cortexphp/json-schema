<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasReadWrite
{
    protected ?bool $readOnly = null;

    protected ?bool $writeOnly = null;

    /**
     * Mark as read-only
     */
    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * Mark as write-only
     */
    public function writeOnly(bool $writeOnly = true): static
    {
        $this->writeOnly = $writeOnly;

        return $this;
    }

    /**
     * Add read/write fields to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addReadWriteToSchema(array $schema): array
    {
        if ($this->readOnly !== null) {
            $schema['readOnly'] = $this->readOnly;
        }

        if ($this->writeOnly !== null) {
            $schema['writeOnly'] = $this->writeOnly;
        }

        return $schema;
    }
}

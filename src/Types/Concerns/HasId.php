<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasId
{
    protected ?string $id = null;

    /**
     * Set the $id value for this schema.
     */
    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the $id value for this schema.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Add $id to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addIdToSchema(array $schema): array
    {
        if ($this->id !== null) {
            $schema['$id'] = $this->id;
        }

        return $schema;
    }
}

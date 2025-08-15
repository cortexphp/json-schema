<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasDescription
{
    protected ?string $description = null;

    /**
     * Set the description
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Add description field to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addDescriptionToSchema(array $schema): array
    {
        if ($this->description !== null) {
            $schema['description'] = $this->description;
        }

        return $schema;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

trait HasRef
{
    protected ?string $ref = null;

    /**
     * Set the $ref value for this schema.
     */
    public function ref(string $ref): static
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Add $ref to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addRefToSchema(array $schema): array
    {
        if ($this->ref !== null) {
            $schema['$ref'] = $this->ref;
        }

        return $schema;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Contracts\Schema;

trait HasDefinitions
{
    /**
     * @var array<string, Schema>
     */
    protected array $definitions = [];

    /**
     * Add a definition to the schema.
     */
    public function addDefinition(string $name, Schema $schema): static
    {
        $this->definitions[$name] = $schema;

        return $this;
    }

    /**
     * Add multiple definitions to the schema.
     *
     * @param array<string, \Cortex\JsonSchema\Contracts\Schema> $definitions
     */
    public function addDefinitions(array $definitions): static
    {
        foreach ($definitions as $name => $schema) {
            $this->addDefinition($name, $schema);
        }

        return $this;
    }

    /**
     * Get a definition from the schema.
     */
    public function getDefinition(string $name): ?Schema
    {
        return $this->definitions[$name] ?? null;
    }

    /**
     * Add definitions to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addDefinitionsToSchema(array $schema): array
    {
        if ($this->definitions !== []) {
            $schema['definitions'] = [];

            foreach ($this->definitions as $name => $definition) {
                $schema['definitions'][$name] = $definition->toArray(includeSchemaRef: false);
            }
        }

        return $schema;
    }
}

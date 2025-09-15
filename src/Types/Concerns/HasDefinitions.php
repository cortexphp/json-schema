<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Contracts\JsonSchema;

trait HasDefinitions
{
    /**
     * @var array<string, JsonSchema>
     */
    protected array $definitions = [];

    /**
     * Add a definition to the schema.
     */
    public function addDefinition(string $name, JsonSchema $jsonSchema): static
    {
        $this->definitions[$name] = $jsonSchema;

        return $this;
    }

    /**
     * Add multiple definitions to the schema.
     *
     * @param array<string, \Cortex\JsonSchema\Contracts\JsonSchema> $definitions
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
    public function getDefinition(string $name): ?JsonSchema
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
            // Use version-appropriate keyword: $defs for 2019-09+, definitions for Draft 07
            $keyword = $this->getVersionAppropriateKeyword('$defs');
            $schema[$keyword] = [];

            foreach ($this->definitions as $name => $definition) {
                /** @var array<string, mixed> $definitions */
                $definitions = $schema[$keyword];
                $definitions[$name] = $definition->toArray(includeSchemaRef: false);
                $schema[$keyword] = $definitions;
            }
        }

        return $schema;
    }

    /**
     * Get definition features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getDefinitionFeatures(): array
    {
        if ($this->definitions === []) {
            return [];
        }

        // If using $defs keyword, report the Defs feature
        return $this->isFeatureSupported(SchemaFeature::Defs) ? [SchemaFeature::Defs] : [];
    }
}

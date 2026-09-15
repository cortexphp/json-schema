<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

/**
 * @mixin \Cortex\JsonSchema\Contracts\JsonSchema
 */
trait HasKeywords
{
    /**
     * @var array<string, mixed>
     */
    protected array $keywords = [];

    /**
     * Set an arbitrary JSON Schema or OpenAPI keyword, including unmodelled
     * draft keywords and vendor extensions such as `discriminator` or `x-*`.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function keyword(string $name, mixed $value): static
    {
        if ($name === '') {
            throw new SchemaException('Keyword name must not be empty');
        }

        $this->keywords[$name] = $value;

        return $this;
    }

    /**
     * Add extra keywords to the schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addKeywordsToSchema(array $schema): array
    {
        foreach ($this->keywords as $name => $value) {
            $schema[$name] = $this->normalizeKeywordValue($value);
        }

        return $schema;
    }

    /**
     * Recursively serialise nested schema values attached via {@see keyword()}.
     */
    protected function normalizeKeywordValue(mixed $value): mixed
    {
        if ($value instanceof JsonSchema) {
            return $value->toArray(includeSchemaRef: false, includeTitle: true);
        }

        if (! is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizeKeywordValue($item);
        }

        return $normalized;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasTitle
{
    protected ?string $title = null;

    /**
     * Set the title
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Determine if the schema has a title
     */
    public function hasTitle(): bool
    {
        return $this->title !== null;
    }

    /**
     * Add title to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addTitleToSchema(array $schema, bool $includeTitle = true): array
    {
        if ($this->title !== null && $includeTitle) {
            $schema['title'] = $this->title;
        }

        return $schema;
    }
}

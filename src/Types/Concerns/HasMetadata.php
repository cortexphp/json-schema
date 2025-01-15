<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasMetadata
{
    protected mixed $default = null;

    protected bool $deprecated = false;

    protected ?string $comment = null;

    /**
     * @var array<array-key, mixed>|null
     */
    protected ?array $examples = null;

    /**
     * Set the default value
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * Get the default value
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Mark the schema as deprecated
     */
    public function deprecated(bool $deprecated = true): static
    {
        $this->deprecated = $deprecated;

        return $this;
    }

    /**
     * Check if the schema is deprecated
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * Set a comment for the schema
     */
    public function comment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the schema comment
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Add examples to the schema
     *
     * @param array<array-key, mixed> $examples
     */
    public function examples(array $examples): static
    {
        $this->examples = $examples;

        return $this;
    }

    /**
     * Add metadata to the schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addMetadataToSchema(array $schema): array
    {
        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        if ($this->deprecated) {
            $schema['deprecated'] = true;
        }

        if ($this->comment !== null) {
            $schema['$comment'] = $this->comment;
        }

        if ($this->examples !== null) {
            $schema['examples'] = $this->examples;
        }

        return $schema;
    }
}

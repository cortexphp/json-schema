<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Contracts;

use Cortex\JsonSchema\Enums\SchemaVersion;

interface Schema
{
    /**
     * Set the title
     */
    public function title(string $title): static;

    /**
     * Get the title
     */
    public function getTitle(): ?string;

    /**
     * Set the description
     */
    public function description(string $description): static;

    /**
     * Get the description
     */
    public function getDescription(): ?string;

    /**
     * Determine if the schema is required
     */
    public function isRequired(): bool;

    /**
     * Add null type to schema.
     */
    public function nullable(): static;

    /**
     * Set the default value
     */
    public function default(mixed $value): static;

    /**
     * Set the allowed enum values.
     *
     * @param non-empty-array<int|string|bool|float|null> $values
     */
    public function enum(array $values): static;

    /**
     * Set the schema as required.
     */
    public function required(): static;

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array;

    /**
     * Convert to JSON.
     */
    public function toJson(int $flags = 0): string;

    /**
     * Validate the given value against the schema.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function validate(mixed $value): void;

    /**
     * Determine if the given value is valid against the schema.
     */
    public function isValid(mixed $value): bool;

    /**
     * Set the JSON Schema version for this schema.
     */
    public function version(SchemaVersion $schemaVersion): static;

    /**
     * Get the JSON Schema version for this schema.
     */
    public function getVersion(): SchemaVersion;
}

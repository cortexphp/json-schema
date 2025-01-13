<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Contracts;

use Cortex\JsonSchema\Enums\SchemaType;

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
     * Get the type or types
     *
     * @return \Cortex\JsonSchema\Enums\SchemaType|array<int, \Cortex\JsonSchema\Enums\SchemaType>
     */
    public function getType(): SchemaType|array;

    /**
     * Determine if the schema is required
     */
    public function isRequired(): bool;

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
}

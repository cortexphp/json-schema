<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Exceptions\SchemaException;

final class StringSchema extends AbstractSchema
{
    protected ?int $minLength = null;

    protected ?int $maxLength = null;

    protected ?string $pattern = null;

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::String, $title);
    }

    /**
     * Set the minimum length.
     */
    public function minLength(int $length): static
    {
        if ($length < 0) {
            throw new SchemaException('Minimum length must be greater than or equal to 0');
        }

        $this->minLength = $length;

        return $this;
    }

    /**
     * Set the maximum length.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxLength(int $length): static
    {
        if ($length < 0) {
            throw new SchemaException('Maximum length must be greater than or equal to 0');
        }

        if ($this->minLength !== null && $length < $this->minLength) {
            throw new SchemaException('Maximum length must be greater than or equal to minimum length');
        }

        $this->maxLength = $length;

        return $this;
    }

    /**
     * Set the pattern.
     */
    public function pattern(string $pattern): static
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = parent::toArray($includeSchemaRef, $includeTitle);

        return $this->addLengthToSchema($schema);
    }

    /**
     * Add length constraints to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addLengthToSchema(array $schema): array
    {
        if ($this->minLength !== null) {
            $schema['minLength'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $schema['maxLength'] = $this->maxLength;
        }

        if ($this->pattern !== null) {
            $schema['pattern'] = $this->pattern;
        }

        return $schema;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaFeature;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

final class StringSchema extends AbstractSchema
{
    protected ?int $minLength = null;

    protected ?int $maxLength = null;

    protected ?string $pattern = null;

    protected ?string $contentEncoding = null;

    protected ?string $contentMediaType = null;

    protected JsonSchema|bool|null $contentSchema = null;

    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(SchemaType::String, $title, $schemaVersion);
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
     * Set the content encoding.
     */
    public function contentEncoding(string $contentEncoding): static
    {
        $this->contentEncoding = $contentEncoding;

        return $this;
    }

    /**
     * Set the content media type.
     */
    public function contentMediaType(string $contentMediaType): static
    {
        $this->contentMediaType = $contentMediaType;

        return $this;
    }

    /**
     * Set the content schema.
     */
    public function contentSchema(JsonSchema|bool $contentSchema): static
    {
        $this->validateFeatureSupport(SchemaFeature::ContentSchema);
        $this->contentSchema = $contentSchema;

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

        $schema = $this->addLengthToSchema($schema);

        return $this->addContentToSchema($schema);
    }

    /**
     * Get features used by this schema from all traits.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    #[Override]
    protected function getUsedFeatures(): array
    {
        $features = [
            ...parent::getUsedFeatures(),
            ...$this->getContentFeatures(),
        ];

        $uniqueFeatures = [];

        foreach ($features as $feature) {
            $uniqueFeatures[$feature->value] = $feature;
        }

        return array_values($uniqueFeatures);
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

    /**
     * Add content keywords to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addContentToSchema(array $schema): array
    {
        if ($this->contentEncoding !== null) {
            $schema['contentEncoding'] = $this->contentEncoding;
        }

        if ($this->contentMediaType !== null) {
            $schema['contentMediaType'] = $this->contentMediaType;
        }

        if ($this->contentSchema !== null) {
            $schema['contentSchema'] = $this->contentSchema instanceof JsonSchema
                ? $this->contentSchema->toArray(includeSchemaRef: false, includeTitle: false)
                : $this->contentSchema;
        }

        return $schema;
    }

    /**
     * Get content features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getContentFeatures(): array
    {
        $features = [];

        if ($this->contentSchema !== null) {
            $features[] = SchemaFeature::ContentSchema;
        }

        return $features;
    }
}

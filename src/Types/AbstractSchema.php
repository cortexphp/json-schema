<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\Concerns\HasEnum;
use Cortex\JsonSchema\Types\Concerns\HasConst;
use Cortex\JsonSchema\Types\Concerns\HasTitle;
use Cortex\JsonSchema\Types\Concerns\HasFormat;
use Cortex\JsonSchema\Types\Concerns\HasMetadata;
use Cortex\JsonSchema\Types\Concerns\HasRequired;
use Cortex\JsonSchema\Types\Concerns\HasReadWrite;
use Cortex\JsonSchema\Types\Concerns\HasValidation;
use Cortex\JsonSchema\Types\Concerns\HasDescription;
use Cortex\JsonSchema\Types\Concerns\HasConditionals;

abstract class AbstractSchema implements Schema
{
    use HasEnum;
    use HasConst;
    use HasTitle;
    use HasFormat;
    use HasMetadata;
    use HasRequired;
    use HasReadWrite;
    use HasValidation;
    use HasDescription;
    use HasConditionals;

    protected string $schemaVersion = 'http://json-schema.org/draft-07/schema#';

    /**
     * @param \Cortex\JsonSchema\Enums\SchemaType|array<int, \Cortex\JsonSchema\Enums\SchemaType> $type
     */
    public function __construct(
        protected SchemaType|array $type,
        ?string $title = null,
    ) {
        $this->title = $title;
    }

    /**
     * Get the type or types.
     *
     * @return \Cortex\JsonSchema\Enums\SchemaType|array<int, \Cortex\JsonSchema\Enums\SchemaType>
     */
    public function getType(): SchemaType|array
    {
        return $this->type;
    }

    /**
     * Add null type to schema.
     */
    public function nullable(): static
    {
        if ($this->isNullable()) {
            return $this;
        }

        if (is_array($this->type)) {
            $this->type[] = SchemaType::Null;
        } else {
            $this->type = [
                $this->type,
                SchemaType::Null,
            ];
        }

        return $this;
    }

    /**
     * Check if the schema allows null values.
     */
    protected function isNullable(): bool
    {
        return is_array($this->type) && in_array(SchemaType::Null, $this->type, true);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = [
            'type' => is_array($this->type)
                ? array_map(fn(SchemaType $type) => $type->value, $this->type)
                : $this->type->value,
        ];

        if ($includeSchemaRef) {
            $schema['$schema'] = $this->schemaVersion;
        }

        $schema = $this->addTitleToSchema($schema, $includeTitle);
        $schema = $this->addFormatToSchema($schema);
        $schema = $this->addDescriptionToSchema($schema);
        $schema = $this->addEnumToSchema($schema);
        $schema = $this->addConstToSchema($schema);
        $schema = $this->addConditionalsToSchema($schema);
        $schema = $this->addMetadataToSchema($schema);

        return $this->addReadWriteToSchema($schema);
    }

    /**
     * Convert to JSON.
     */
    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }
}

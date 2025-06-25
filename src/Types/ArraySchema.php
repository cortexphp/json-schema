<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\Concerns\HasItems;
use Cortex\JsonSchema\Exceptions\SchemaException;

final class ArraySchema extends AbstractSchema
{
    use HasItems;

    protected ?Schema $contains = null;

    protected ?int $minContains = null;

    protected ?int $maxContains = null;

    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(SchemaType::Array, $title, $schemaVersion);
    }

    /**
     * Set the schema that array items must contain
     */
    public function contains(Schema $schema): static
    {
        $this->contains = $schema;

        return $this;
    }

    /**
     * Set the minimum number of items that must match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function minContains(int $min): static
    {
        if ($min < 0) {
            throw new SchemaException('minContains must be greater than or equal to 0');
        }

        if ($this->maxContains !== null && $min > $this->maxContains) {
            throw new SchemaException('minContains cannot be greater than maxContains');
        }

        $this->minContains = $min;

        return $this;
    }

    /**
     * Set the maximum number of items that can match the contains schema
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function maxContains(int $max): static
    {
        if ($max < 0) {
            throw new SchemaException('maxContains must be greater than or equal to 0');
        }

        if ($this->minContains !== null && $max < $this->minContains) {
            throw new SchemaException('maxContains cannot be less than minContains');
        }

        $this->maxContains = $max;

        return $this;
    }

    /**
     * Convert the schema to an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(bool $includeSchemaRef = true, bool $includeTitle = true): array
    {
        $schema = parent::toArray($includeSchemaRef, $includeTitle);

        $schema = $this->addItemsToSchema($schema);

        if ($this->contains instanceof Schema) {
            $schema['contains'] = $this->contains->toArray();
        }

        if ($this->minContains !== null) {
            $schema['minContains'] = $this->minContains;
        }

        if ($this->maxContains !== null) {
            $schema['maxContains'] = $this->maxContains;
        }

        return $schema;
    }
}

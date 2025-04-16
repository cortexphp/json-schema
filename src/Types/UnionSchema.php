<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\Concerns\HasItems;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Cortex\JsonSchema\Types\Concerns\HasProperties;
use Cortex\JsonSchema\Types\Concerns\HasNumericConstraints;

final class UnionSchema extends AbstractSchema
{
    use HasItems;
    use HasProperties;
    use HasNumericConstraints;

    /**
     * @param array<int, \Cortex\JsonSchema\Enums\SchemaType> $types
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function __construct(
        array $types,
        ?string $title = null,
    ) {
        if ($types === []) {
            throw new SchemaException('Union schema must have at least one type');
        }

        $uniqueTypes = array_unique(
            array_map(static fn(SchemaType $schemaType) => $schemaType->value, $types),
        );

        if (count($uniqueTypes) !== count($types)) {
            throw new SchemaException('Union schema types must be unique');
        }

        parent::__construct($types, $title);
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

        $schema = $this->addNumericConstraintsToSchema($schema);
        $schema = $this->addItemsToSchema($schema);

        return $this->addPropertiesToSchema($schema);
    }
}

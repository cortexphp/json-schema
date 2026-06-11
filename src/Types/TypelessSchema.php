<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\Concerns\HasItems;
use Cortex\JsonSchema\Types\Concerns\HasProperties;
use Cortex\JsonSchema\Types\Concerns\HasNumericConstraints;

final class TypelessSchema extends AbstractSchema
{
    use HasItems;
    use HasProperties;
    use HasNumericConstraints;

    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(null, $title, $schemaVersion);
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

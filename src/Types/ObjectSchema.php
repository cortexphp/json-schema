<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Override;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\Concerns\HasProperties;

final class ObjectSchema extends AbstractSchema
{
    use HasProperties;

    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Object, $title);
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

        return $this->addPropertiesToSchema($schema);
    }
}

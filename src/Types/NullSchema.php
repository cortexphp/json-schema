<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Enums\SchemaVersion;

final class NullSchema extends AbstractSchema
{
    public function __construct(?string $title = null, ?SchemaVersion $schemaVersion = null)
    {
        parent::__construct(SchemaType::Null, $title, $schemaVersion);
    }
}

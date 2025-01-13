<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Cortex\JsonSchema\Enums\SchemaType;

class BooleanSchema extends AbstractSchema
{
    public function __construct(?string $title = null)
    {
        parent::__construct(SchemaType::Boolean, $title);
    }
}

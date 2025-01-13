<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types;

use Cortex\JsonSchema\Enums\SchemaType;

class IntegerSchema extends NumberSchema
{
    public function __construct(?string $title = null)
    {
        parent::__construct($title);
        $this->type = SchemaType::Integer;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Exceptions;

class UnknownTypeException extends SchemaException
{
    public static function forType(string $type): self
    {
        return new self("Unknown type: {$type}");
    }
}


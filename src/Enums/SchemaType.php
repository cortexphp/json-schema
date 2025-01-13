<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Enums;

use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;

enum SchemaType: string
{
    case String = 'string';
    case Number = 'number';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Object = 'object';
    case Array = 'array';
    case Null = 'null';

    /**
     * Create a new schema instance from the current type.
     */
    public function instance(?string $title = null): Schema
    {
        return match ($this) {
            self::String => new StringSchema($title),
            self::Number => new NumberSchema($title),
            self::Integer => new IntegerSchema($title),
            self::Boolean => new BooleanSchema($title),
            self::Object => new ObjectSchema($title),
            self::Array => new ArraySchema($title),
            self::Null => new NullSchema($title),
        };
    }
}

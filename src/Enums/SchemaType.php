<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Enums;

use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

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
    public function instance(?string $title = null, ?SchemaVersion $schemaVersion = null): JsonSchema
    {
        return match ($this) {
            self::String => new StringSchema($title, $schemaVersion),
            self::Number => new NumberSchema($title, $schemaVersion),
            self::Integer => new IntegerSchema($title, $schemaVersion),
            self::Boolean => new BooleanSchema($title, $schemaVersion),
            self::Object => new ObjectSchema($title, $schemaVersion),
            self::Array => new ArraySchema($title, $schemaVersion),
            self::Null => new NullSchema($title, $schemaVersion),
        };
    }

    /**
     * Create a new schema type instance from a given scalar type.
     */
    public static function fromScalar(string $type): self
    {
        return match ($type) {
            'int' => self::Integer,
            'float' => self::Number,
            'string' => self::String,
            'array' => self::Array,
            'bool' => self::Boolean,
            'object' => self::Object,
            'null' => self::Null,
            default => throw new SchemaException('Unknown type: ' . $type),
        };
    }
}

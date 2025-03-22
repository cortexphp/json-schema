<?php

declare(strict_types=1);

namespace Cortex\JsonSchema;

use Closure;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Converters\EnumConverter;
use Cortex\JsonSchema\Converters\ClassConverter;
use Cortex\JsonSchema\Converters\ClosureConverter;

class SchemaFactory
{
    public static function string(?string $title = null): StringSchema
    {
        return new StringSchema($title);
    }

    public static function object(?string $title = null): ObjectSchema
    {
        return new ObjectSchema($title);
    }

    public static function array(?string $title = null): ArraySchema
    {
        return new ArraySchema($title);
    }

    public static function number(?string $title = null): NumberSchema
    {
        return new NumberSchema($title);
    }

    public static function integer(?string $title = null): IntegerSchema
    {
        return new IntegerSchema($title);
    }

    public static function boolean(?string $title = null): BooleanSchema
    {
        return new BooleanSchema($title);
    }

    public static function null(?string $title = null): NullSchema
    {
        return new NullSchema($title);
    }

    /**
     * @param array<int, \Cortex\JsonSchema\Enums\SchemaType> $types
     */
    public static function union(array $types, ?string $title = null): UnionSchema
    {
        return new UnionSchema($types, $title);
    }

    public static function mixed(?string $title = null): UnionSchema
    {
        return new UnionSchema(SchemaType::cases(), $title);
    }

    /**
     * Create a schema from a given closure.
     */
    public static function fromClosure(Closure $closure): ObjectSchema
    {
        return (new ClosureConverter($closure))->convert();
    }

    /**
     * Create a schema from a given class.
     *
     * @param object|class-string $class
     */
    public static function fromClass(object|string $class, bool $publicOnly = true): ObjectSchema
    {
        return (new ClassConverter($class, $publicOnly))->convert();
    }

    /**
     * Create a schema from a given enum.
     *
     * @param class-string<\BackedEnum> $enum
     */
    public static function fromEnum(string $enum): ObjectSchema
    {
        return (new EnumConverter($enum))->convert();
    }
}

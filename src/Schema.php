<?php

declare(strict_types=1);

namespace Cortex\JsonSchema;

use Closure;
use BackedEnum;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Converters\EnumConverter;
use Cortex\JsonSchema\Converters\JsonConverter;
use Cortex\JsonSchema\Converters\ClassConverter;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Cortex\JsonSchema\Converters\ClosureConverter;

class Schema
{
    private static ?SchemaVersion $schemaVersion = null;

    /**
     * Set the default schema version for all new schemas.
     */
    public static function setDefaultVersion(SchemaVersion $schemaVersion): void
    {
        self::$schemaVersion = $schemaVersion;
    }

    /**
     * Get the current default schema version.
     */
    public static function getDefaultVersion(): SchemaVersion
    {
        return self::$schemaVersion ?? SchemaVersion::default();
    }

    /**
     * Reset the default version to the package default.
     */
    public static function resetDefaultVersion(): void
    {
        self::$schemaVersion = null;
    }

    public static function string(?string $title = null, ?SchemaVersion $schemaVersion = null): StringSchema
    {
        return new StringSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function object(?string $title = null, ?SchemaVersion $schemaVersion = null): ObjectSchema
    {
        return new ObjectSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function array(?string $title = null, ?SchemaVersion $schemaVersion = null): ArraySchema
    {
        return new ArraySchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function number(?string $title = null, ?SchemaVersion $schemaVersion = null): NumberSchema
    {
        return new NumberSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function integer(?string $title = null, ?SchemaVersion $schemaVersion = null): IntegerSchema
    {
        return new IntegerSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function boolean(?string $title = null, ?SchemaVersion $schemaVersion = null): BooleanSchema
    {
        return new BooleanSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function null(?string $title = null, ?SchemaVersion $schemaVersion = null): NullSchema
    {
        return new NullSchema($title, $schemaVersion ?? self::getDefaultVersion());
    }

    /**
     * @param array<int, \Cortex\JsonSchema\Enums\SchemaType> $types
     */
    public static function union(array $types, ?string $title = null, ?SchemaVersion $schemaVersion = null): UnionSchema
    {
        return new UnionSchema($types, $title, $schemaVersion ?? self::getDefaultVersion());
    }

    public static function mixed(?string $title = null, ?SchemaVersion $schemaVersion = null): UnionSchema
    {
        return new UnionSchema(SchemaType::cases(), $title, $schemaVersion ?? self::getDefaultVersion());
    }

    /**
     * Create a schema from a given closure.
     */
    public static function fromClosure(
        Closure $closure,
        ?SchemaVersion $schemaVersion = null,
        bool $ignoreUnknownTypes = false,
    ): ObjectSchema {
        $closureConverter = new ClosureConverter(
            $closure,
            $schemaVersion ?? self::getDefaultVersion(),
            $ignoreUnknownTypes,
        );

        return $closureConverter->convert();
    }

    /**
     * Create a schema from a given class.
     *
     * @param object|class-string $class
     */
    public static function fromClass(
        object|string $class,
        bool $publicOnly = true,
        ?SchemaVersion $schemaVersion = null,
    ): ObjectSchema {
        $classConverter = new ClassConverter(
            $class,
            $publicOnly,
            $schemaVersion ?? self::getDefaultVersion(),
        );

        return $classConverter->convert();
    }

    /**
     * Create a schema from a given enum.
     *
     * @param class-string<\BackedEnum> $enum
     */
    public static function fromEnum(string $enum, ?SchemaVersion $schemaVersion = null): StringSchema|IntegerSchema
    {
        $enumConverter = new EnumConverter(
            $enum,
            $schemaVersion ?? self::getDefaultVersion(),
        );

        return $enumConverter->convert();
    }

    /**
     * Create a schema from a given value.
     */
    public static function from(
        mixed $value,
        ?SchemaVersion $version = null,
        bool $ignoreUnknownTypes = false,
    ): JsonSchema {
        $schemaVersion = $version ?? self::getDefaultVersion();

        return match (true) {
            $value instanceof Closure => self::fromClosure($value, $schemaVersion, $ignoreUnknownTypes),
            is_string($value) && enum_exists($value) && is_subclass_of($value, BackedEnum::class) => self::fromEnum(
                $value,
                $schemaVersion,
            ),
            is_string($value) && class_exists($value) || is_object($value) => self::fromClass(
                $value,
                true,
                $schemaVersion,
            ),
            // @phpstan-ignore argument.type
            is_array($value) || (is_string($value) && json_validate($value)) => self::fromJson($value, $schemaVersion),
            default => throw new SchemaException(
                'Unsupported value type. Only closures, enums, and classes are supported.',
            ),
        };
    }

    /**
     * Create a schema from a JSON Schema definition.
     *
     * @param string|array<string, mixed> $json
     */
    public static function fromJson(string|array $json, ?SchemaVersion $schemaVersion = null): JsonSchema
    {
        return (new JsonConverter($json, $schemaVersion ?? self::getDefaultVersion()))->convert();
    }
}

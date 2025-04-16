<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters\Concerns;

use ReflectionEnum;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

trait InteractsWithTypes
{
    /**
     * Resolve the schema instance from the given reflection type.
     */
    protected static function getSchemaFromReflectionType(
        ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type,
    ): Schema {
        $schemaTypes = match (true) {
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => array_map(
                // @phpstan-ignore argument.type
                fn(ReflectionNamedType $reflectionNamedType): SchemaType => self::resolveSchemaType(
                    $reflectionNamedType,
                ),
                $type->getTypes(),
            ),
            // If the parameter is not typed or explicitly typed as mixed, we use all schema types
            in_array($type?->getName(), ['mixed', null], true) => SchemaType::cases(),
            default => [self::resolveSchemaType($type)],
        };

        return count($schemaTypes) === 1
            ? $schemaTypes[0]->instance()
            : new UnionSchema(array_values($schemaTypes));
    }

    /**
     * Resolve the schema type from the given reflection type.
     */
    protected static function resolveSchemaType(ReflectionNamedType $reflectionNamedType): SchemaType
    {
        $typeName = $reflectionNamedType->getName();

        if (enum_exists($typeName)) {
            $reflectionEnum = new ReflectionEnum($typeName);
            $typeName = $reflectionEnum->getBackingType()?->getName();

            if ($typeName === null) {
                throw new SchemaException('Enum type has no backing type: ' . $reflectionEnum->getName());
            }
        }

        return SchemaType::fromScalar($typeName);
    }
}

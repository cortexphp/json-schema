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
                fn(ReflectionNamedType $t): SchemaType => self::resolveSchemaType($t),
                $type->getTypes(),
            ),
            // If the parameter is not typed or explicitly typed as mixed, we use all schema types
            in_array($type?->getName(), ['mixed', null], true) => SchemaType::cases(),
            default => [self::resolveSchemaType($type)],
        };

        return count($schemaTypes) === 1
            ? $schemaTypes[0]->instance()
            : new UnionSchema($schemaTypes);
    }

    /**
     * Resolve the schema type from the given reflection type.
     */
    protected static function resolveSchemaType(ReflectionNamedType $type): SchemaType
    {
        $typeName = $type->getName();

        if (enum_exists($typeName)) {
            $reflection = new ReflectionEnum($typeName);
            $typeName = $reflection->getBackingType()?->getName();

            if ($typeName === null) {
                throw new SchemaException('Enum type has no backing type: ' . $reflection->getName());
            }
        }

        return SchemaType::fromScalar($typeName);
    }
}

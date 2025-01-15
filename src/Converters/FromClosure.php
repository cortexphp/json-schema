<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use Closure;
use ReflectionEnum;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

class FromClosure
{
    public static function convert(Closure $closure): ObjectSchema
    {
        $reflection = new ReflectionFunction($closure);
        $schema = new ObjectSchema();

        // TODO: handle descriptions
        // $doc = $reflection->getDocComment();

        foreach ($reflection->getParameters() as $parameter) {
            $propertySchema = self::getPropertySchema($parameter);

            // No type hint, skip
            if ($propertySchema === null) {
                continue;
            }

            $schema->properties($propertySchema);
        }

        return $schema;
    }

    /**
     * Create a schema from a given type.
     */
    protected static function getPropertySchema(ReflectionParameter $parameter): ?Schema
    {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        $matchedTypes = match (true) {
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => array_map(
                fn(ReflectionNamedType $t): SchemaType => self::resolveSchemaType($t),
                $type->getTypes(),
            ),
            $type instanceof ReflectionNamedType => [self::resolveSchemaType($type)],
            default => throw new SchemaException('Unknown type: ' . $type),
        };

        // TODO: handle mixed type

        $schema = count($matchedTypes) === 1
            ? $matchedTypes[0]->instance()
            : new UnionSchema($matchedTypes);

        $schema->title($parameter->getName());

        if ($type->allowsNull()) {
            $schema->nullable();
        }

        if ($parameter->isDefaultValueAvailable() && ! $parameter->isDefaultValueConstant()) {
            $schema->default($parameter->getDefaultValue());
        }

        if (! $parameter->isOptional()) {
            $schema->required();
        }

        // If it's an enum, add the possible values
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (enum_exists($typeName)) {
                $reflection = new ReflectionEnum($typeName);

                if ($reflection->isBacked()) {
                    $cases = $typeName::cases();
                    $schema->enum(array_map(fn($case): int|string => $case->value, $cases));
                }
            }
        }

        return $schema;
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
                throw new SchemaException('Enum type has no backing type: ' . $typeName);
            }
        }

        return SchemaType::fromScalar($typeName);
    }
}

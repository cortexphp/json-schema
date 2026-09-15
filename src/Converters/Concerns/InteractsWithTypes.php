<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters\Concerns;

use ReflectionEnum;
use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

trait InteractsWithTypes
{
    abstract protected function schemaVersion(): SchemaVersion;

    /**
     * Resolve the schema instance from the given reflection type.
     */
    protected function getSchemaFromReflectionType(?ReflectionType $type): JsonSchema
    {
        $schemaTypes = match (true) {
            $type instanceof ReflectionUnionType, $type instanceof ReflectionIntersectionType => array_map(
                fn(ReflectionType $reflectionType): SchemaType => $this->resolveSchemaType(
                    $this->assertNamedType($reflectionType),
                ),
                $type->getTypes(),
            ),
            // If the parameter is not typed or explicitly typed as mixed, we use all schema types
            ! $type instanceof ReflectionType || ($type instanceof ReflectionNamedType && $type->getName() === 'mixed') => SchemaType::cases(),
            $type instanceof ReflectionNamedType => [$this->resolveSchemaType($type)],
            default => throw new SchemaException('Unsupported reflection type: ' . $type::class),
        };

        return count($schemaTypes) === 1
            ? $schemaTypes[0]->instance(null, $this->schemaVersion())
            : new UnionSchema(array_values($schemaTypes), null, $this->schemaVersion());
    }

    /**
     * Resolve the schema type from the given reflection type.
     */
    protected function resolveSchemaType(ReflectionNamedType $reflectionNamedType): SchemaType
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

    /**
     * Apply docblock-derived item types to an array schema when mappable.
     *
     * @param array<array-key, string> $itemTypes
     */
    protected function applyArrayItems(ArraySchema $arraySchema, array $itemTypes): void
    {
        $itemsSchema = $this->getItemsSchema($itemTypes);

        if ($itemsSchema !== null) {
            $arraySchema->items($itemsSchema);
        }
    }

    /**
     * Build an items schema from docblock element type strings.
     *
     * @param array<array-key, string> $itemTypes
     */
    protected function getItemsSchema(array $itemTypes): ?JsonSchema
    {
        if ($itemTypes === []) {
            return null;
        }

        $schemaTypes = [];

        foreach ($itemTypes as $itemType) {
            $schemaType = SchemaType::tryFromScalar(ltrim($itemType, '\\'));

            if (! $schemaType instanceof SchemaType) {
                return null;
            }

            if (! in_array($schemaType, $schemaTypes, true)) {
                $schemaTypes[] = $schemaType;
            }
        }

        return count($schemaTypes) === 1
            ? $schemaTypes[0]->instance(null, $this->schemaVersion())
            : new UnionSchema($schemaTypes, null, $this->schemaVersion());
    }

    /**
     * Assert that a reflection type from a union/intersection is a named type.
     */
    private function assertNamedType(ReflectionType $reflectionType): ReflectionNamedType
    {
        if (! $reflectionType instanceof ReflectionNamedType) {
            throw new SchemaException('Unsupported reflection type in union/intersection: ' . $reflectionType::class);
        }

        return $reflectionType;
    }
}

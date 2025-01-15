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

class ClosureConverter
{
    public function __construct(
        protected Closure $closure,
    ) {}

    public function convert(): ObjectSchema
    {
        $reflection = new ReflectionFunction($this->closure);
        $schema = new ObjectSchema();

        // TODO: handle descriptions

        foreach ($reflection->getParameters() as $parameter) {
            $schema->properties(self::getSchemaFromReflectionParameter($parameter));
        }

        return $schema;
    }

    /**
     * Create a schema from a given type.
     */
    protected static function getSchemaFromReflectionParameter(ReflectionParameter $parameter): Schema
    {
        $type = $parameter->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($parameter->getName());

        if ($type === null || $type->allowsNull()) {
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
                    /** @var non-empty-array<array-key, \BackedEnum> */
                    $cases = $typeName::cases();
                    $schema->enum(array_map(fn($case): int|string => $case->value, $cases));
                }
            }
        }

        return $schema;
    }

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
            // TODO: use phpstan parser to get the type also
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
                throw new SchemaException('Enum type has no backing type: ' . $typeName);
            }
        }

        return SchemaType::fromScalar($typeName);
    }
}

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
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

class ClosureConverter
{
    protected ReflectionFunction $reflection;

    public function __construct(
        protected Closure $closure,
    ) {
        $this->reflection = new ReflectionFunction($this->closure);
    }

    public function convert(): ObjectSchema
    {
        $schema = new ObjectSchema();

        // Get the description from the doc parser
        $description = $this->getDocParser()?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $schema->description($description);
        }

        // Get the parameters from the doc parser
        $params = $this->getDocParser()?->params() ?? [];

        // Add the parameters to the objectschema
        foreach ($this->reflection->getParameters() as $parameter) {
            $schema->properties(self::getSchemaFromReflectionParameter($parameter, $params));
        }

        return $schema;
    }

    /**
     * Create a schema from a given type.
     *
     * @param array<array-key, array{name: string, types: array<array-key, string>, description: string|null}> $docParams
     */
    protected static function getSchemaFromReflectionParameter(
        ReflectionParameter $parameter,
        array $docParams = [],
    ): Schema {
        $type = $parameter->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($parameter->getName());

        // Add the description to the schema if it exists
        $param = array_filter($docParams, fn($param): bool => $param['name'] === $parameter->getName());
        $description = $param[0]['description'] ?? null;

        if ($description !== null) {
            $schema->description($description);
        }

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

    protected function getDocParser(): ?DocParser
    {
        if ($docComment = $this->reflection->getDocComment()) {
            return new DocParser($docComment);
        }

        return null;
    }
}

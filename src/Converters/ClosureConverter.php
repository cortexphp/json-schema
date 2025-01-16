<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use Closure;
use BackedEnum;
use ReflectionEnum;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithTypes;

class ClosureConverter implements Converter
{
    use InteractsWithTypes;

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
    protected function getSchemaFromReflectionParameter(
        ReflectionParameter $parameter,
        array $docParams = [],
    ): Schema {
        $type = $parameter->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($parameter->getName());

        // Add the description to the schema if it exists
        $param = array_filter($docParams, static fn(array $param): bool => $param['name'] === $parameter->getName());
        $description = $param[0]['description'] ?? null;

        if ($description !== null) {
            $schema->description($description);
        }

        if ($type === null || $type->allowsNull()) {
            $schema->nullable();
        }

        if ($parameter->isDefaultValueAvailable() && ! $parameter->isDefaultValueConstant()) {
            $defaultValue = $parameter->getDefaultValue();

            // If the default value is a backed enum, use its value
            if ($defaultValue instanceof BackedEnum) {
                $defaultValue = $defaultValue->value;
            }

            $schema->default($defaultValue);
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
                    $schema->enum(array_map(fn(BackedEnum $case): int|string => $case->value, $cases));
                }
            }
        }

        return $schema;
    }

    protected function getDocParser(): ?DocParser
    {
        if ($docComment = $this->reflection->getDocComment()) {
            return new DocParser($docComment);
        }

        return null;
    }
}

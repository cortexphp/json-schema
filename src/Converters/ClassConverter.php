<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use BackedEnum;
use ReflectionEnum;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithTypes;

class ClassConverter implements Converter
{
    use InteractsWithTypes;

    /**
     * @var \ReflectionClass<object>
     */
    protected ReflectionClass $reflection;

    /**
     * @param object|class-string $class
     */
    public function __construct(
        protected object|string $class,
        protected bool $publicOnly = true,
    ) {
        $this->reflection = new ReflectionClass($this->class);
    }

    public function convert(): ObjectSchema
    {
        $schema = new ObjectSchema();

        // Get the description from the doc parser
        $description = $this->getDocParser($this->reflection)?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $schema->description($description);
        }

        $properties = $this->reflection->getProperties(
            $this->publicOnly ? ReflectionProperty::IS_PUBLIC : null,
        );

        // Add the properties to the object schema
        foreach ($properties as $property) {
            $schema->properties(self::getSchemaFromReflectionProperty($property));
        }

        return $schema;
    }

    /**
     * Create a schema from a given type.
     */
    protected function getSchemaFromReflectionProperty(
        ReflectionProperty $property,
    ): Schema {
        $type = $property->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($property->getName());

        $variable = $this->getDocParser($property)?->variable();

        // Add the description to the schema if it exists
        if ($variable?->description !== null) {
            $schema->description($variable->description);
        }

        if ($type === null || $type->allowsNull()) {
            $schema->nullable();
        }

        if ($property->hasDefaultValue()) {
            $defaultValue = $property->getDefaultValue();

            // If the default value is a backed enum, use its value
            if ($defaultValue instanceof BackedEnum) {
                $defaultValue = $defaultValue->value;
            }

            $schema->default($defaultValue);
        } else {
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

    /**
     * @param ReflectionProperty|ReflectionClass<object> $reflection
     */
    protected function getDocParser(ReflectionProperty|ReflectionClass $reflection): ?DocParser
    {
        $docComment = $reflection->getDocComment();

        return is_string($docComment)
            ? new DocParser($docComment)
            : null;
    }
}

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
use Cortex\JsonSchema\Enums\SchemaVersion;
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
        protected ?SchemaVersion $version = null,
    ) {
        $this->reflection = new ReflectionClass($this->class);
        $this->version = $version ?? SchemaVersion::default();
    }

    public function convert(): ObjectSchema
    {
        $objectSchema = new ObjectSchema(null, $this->version);

        // Get the description from the doc parser
        $description = $this->getDocParser($this->reflection)?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $objectSchema->description($description);
        }

        $properties = $this->reflection->getProperties(
            $this->publicOnly ? ReflectionProperty::IS_PUBLIC : null,
        );

        // Add the properties to the object schema
        foreach ($properties as $property) {
            $objectSchema->properties(self::getSchemaFromReflectionProperty($property));
        }

        return $objectSchema;
    }

    /**
     * Create a schema from a given type.
     */
    protected function getSchemaFromReflectionProperty(
        ReflectionProperty $reflectionProperty,
    ): Schema {
        $type = $reflectionProperty->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($reflectionProperty->getName());

        $variable = $this->getDocParser($reflectionProperty)?->variable();

        // Add the description to the schema if it exists
        if ($variable?->description !== null) {
            $schema->description($variable->description);
        }

        if ($type === null || $type->allowsNull()) {
            $schema->nullable();
        }

        if ($reflectionProperty->hasDefaultValue()) {
            $defaultValue = $reflectionProperty->getDefaultValue();

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
                $reflectionEnum = new ReflectionEnum($typeName);

                if ($reflectionEnum->isBacked()) {
                    /** @var non-empty-array<int, string|int> $values */
                    $values = array_column($typeName::cases(), 'value');
                    $schema->enum($values);
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

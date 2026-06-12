<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use BackedEnum;
use ReflectionEnum;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionParameter;
use Cortex\JsonSchema\Support\NodeData;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Support\NodeCollection;
use Cortex\JsonSchema\Exceptions\UnknownTypeException;
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
        protected bool $ignoreUnknownTypes = false,
    ) {
        $this->reflection = new ReflectionClass($this->class);
        $this->version = $version ?? SchemaVersion::default();
    }

    public function convert(): ObjectSchema
    {
        $objectSchema = new ObjectSchema(schemaVersion: $this->version);

        $docParser = $this->getDocParser($this->reflection);

        if ($docParser?->isDeprecated() === true) {
            $objectSchema->deprecated();
        }

        // Get the description from the doc parser
        $description = $docParser?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $objectSchema->description($description);
        }

        $properties = $this->reflection->getProperties(
            $this->publicOnly ? ReflectionProperty::IS_PUBLIC : null,
        );

        // Constructor `@param` tags document promoted properties, which have no
        // docblock of their own. Parse them once so we can resolve descriptions.
        $promotedParams = $this->getConstructorParams();

        // Add the properties to the object schema
        foreach ($properties as $property) {
            // Static properties are not part of the instance state, so skip them.
            if ($property->isStatic()) {
                continue;
            }

            try {
                $objectSchema->properties(self::getSchemaFromReflectionProperty($property, $promotedParams));
            } catch (UnknownTypeException $unknownTypeException) {
                if ($this->ignoreUnknownTypes) {
                    continue;
                }

                throw $unknownTypeException;
            }
        }

        return $objectSchema;
    }

    /**
     * Create a schema from a given type.
     *
     * @param \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null $nodeCollection
     */
    protected function getSchemaFromReflectionProperty(
        ReflectionProperty $reflectionProperty,
        ?NodeCollection $nodeCollection = null,
    ): JsonSchema {
        $type = $reflectionProperty->getType();

        // @phpstan-ignore argument.type
        $jsonSchema = self::getSchemaFromReflectionType($type);

        $jsonSchema->title($reflectionProperty->getName());

        $docParser = $this->getDocParser($reflectionProperty);

        if ($docParser?->isDeprecated() === true) {
            $jsonSchema->deprecated();
        }

        $variable = $docParser?->variable();
        $description = $this->resolvePropertyDescription($variable, $reflectionProperty, $nodeCollection);

        if ($description !== null) {
            $jsonSchema->description($description);
        }

        if ($jsonSchema instanceof ArraySchema) {
            $this->applyArrayItems(
                $jsonSchema,
                $this->resolvePropertyItemTypes($variable, $reflectionProperty, $nodeCollection),
            );
        }

        if ($type === null || $type->allowsNull()) {
            $jsonSchema->nullable();
        }

        // Promoted properties report their default value on the constructor
        // parameter rather than on the property itself.
        $promotedParameter = $reflectionProperty->isPromoted()
            ? $this->getConstructorParameter($reflectionProperty->getName())
            : null;

        $hasDefault = $reflectionProperty->hasDefaultValue()
            || $promotedParameter?->isDefaultValueAvailable() === true;

        if ($hasDefault) {
            $defaultValue = $reflectionProperty->hasDefaultValue()
                ? $reflectionProperty->getDefaultValue()
                : $promotedParameter?->getDefaultValue();

            // If the default value is a backed enum, use its value
            if ($defaultValue instanceof BackedEnum) {
                $defaultValue = $defaultValue->value;
            }

            $jsonSchema->default($defaultValue);
        } else {
            $jsonSchema->required();
        }

        // If it's an enum, add the possible values
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            if (enum_exists($typeName)) {
                $reflectionEnum = new ReflectionEnum($typeName);

                if ($reflectionEnum->isBacked()) {
                    /** @var non-empty-array<int, string|int> $values */
                    $values = array_column($typeName::cases(), 'value');
                    $jsonSchema->enum($values);
                }
            }
        }

        return $jsonSchema;
    }

    /**
     * Resolve a property description from `@var` or promoted constructor `@param` tags.
     *
     * @param \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null $nodeCollection
     */
    protected function resolvePropertyDescription(
        ?NodeData $nodeData,
        ReflectionProperty $reflectionProperty,
        ?NodeCollection $nodeCollection,
    ): ?string {
        if ($nodeData?->description !== null) {
            return $nodeData->description;
        }

        if ($reflectionProperty->isPromoted()) {
            return $nodeCollection?->get($reflectionProperty->getName())?->description;
        }

        return null;
    }

    /**
     * Resolve array element types from `@var` or promoted constructor `@param` tags.
     *
     * @param \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null $nodeCollection
     *
     * @return array<array-key, string>
     */
    protected function resolvePropertyItemTypes(
        ?NodeData $nodeData,
        ReflectionProperty $reflectionProperty,
        ?NodeCollection $nodeCollection,
    ): array {
        $itemTypes = $nodeData instanceof NodeData ? $nodeData->itemTypes : [];

        if ($itemTypes !== [] || ! $reflectionProperty->isPromoted()) {
            return $itemTypes;
        }

        $promotedNode = $nodeCollection?->get($reflectionProperty->getName());

        return $promotedNode instanceof NodeData ? $promotedNode->itemTypes : [];
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

    /**
     * Parse the constructor's `@param` tags, used to describe promoted properties.
     *
     * @return \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null
     */
    protected function getConstructorParams(): ?NodeCollection
    {
        $constructor = $this->reflection->getConstructor();
        $docComment = $constructor?->getDocComment();

        return is_string($docComment)
            ? (new DocParser($docComment))->params()
            : null;
    }

    /**
     * Resolve the constructor parameter matching the given (promoted) property name.
     */
    protected function getConstructorParameter(string $name): ?ReflectionParameter
    {
        $constructor = $this->reflection->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        return null;
    }
}

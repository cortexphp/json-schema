<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use ReflectionClass;
use ReflectionProperty;
use ReflectionParameter;
use Cortex\JsonSchema\Support\NodeData;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Support\NodeCollection;
use Cortex\JsonSchema\Exceptions\UnknownTypeException;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithEnums;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithTypes;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithMembers;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithDocblocks;

class ClassConverter implements Converter
{
    use InteractsWithTypes;
    use InteractsWithEnums;
    use InteractsWithMembers;
    use InteractsWithDocblocks;

    /**
     * @var \ReflectionClass<object>
     */
    protected ReflectionClass $reflection;

    protected SchemaVersion $version;

    /**
     * @var array<string, ReflectionParameter>|null
     */
    private ?array $constructorParameters = null;

    /**
     * @param object|class-string $class
     */
    public function __construct(
        protected object|string $class,
        protected bool $publicOnly = true,
        ?SchemaVersion $schemaVersion = null,
        protected bool $ignoreUnknownTypes = false,
    ) {
        $this->reflection = new ReflectionClass($this->class);
        $this->version = $schemaVersion ?? SchemaVersion::default();
    }

    public function convert(): ObjectSchema
    {
        $objectSchema = new ObjectSchema(schemaVersion: $this->version);

        $this->applySchemaDocblock($objectSchema, $this->docParser($this->reflection));

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
                $objectSchema->properties($this->getSchemaFromReflectionProperty($property, $promotedParams));
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
        $jsonSchema = $this->baseMemberSchema($reflectionProperty);

        $docParser = $this->docParser($reflectionProperty);

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

            $jsonSchema->default($this->unwrapEnumValue($defaultValue));
        } else {
            $jsonSchema->required();
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

        return $this->promotedNode($reflectionProperty, $nodeCollection)?->description;
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

        $promotedNode = $this->promotedNode($reflectionProperty, $nodeCollection);

        return $promotedNode instanceof NodeData ? $promotedNode->itemTypes : [];
    }

    /**
     * Look up the constructor `@param` node for a promoted property.
     *
     * @param \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null $nodeCollection
     */
    protected function promotedNode(
        ReflectionProperty $reflectionProperty,
        ?NodeCollection $nodeCollection,
    ): ?NodeData {
        if (! $reflectionProperty->isPromoted()) {
            return null;
        }

        return $nodeCollection?->get($reflectionProperty->getName());
    }

    /**
     * Parse the constructor's `@param` tags, used to describe promoted properties.
     *
     * @return \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>|null
     */
    protected function getConstructorParams(): ?NodeCollection
    {
        $constructor = $this->reflection->getConstructor();

        if ($constructor === null) {
            return null;
        }

        return $this->docParser($constructor)?->params();
    }

    /**
     * Resolve the constructor parameter matching the given (promoted) property name.
     */
    protected function getConstructorParameter(string $name): ?ReflectionParameter
    {
        return $this->getConstructorParameters()[$name] ?? null;
    }

    /**
     * Cache constructor parameters keyed by name.
     *
     * @return array<string, ReflectionParameter>
     */
    protected function getConstructorParameters(): array
    {
        if ($this->constructorParameters !== null) {
            return $this->constructorParameters;
        }

        $parameters = [];

        foreach ($this->reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        return $this->constructorParameters = $parameters;
    }

    protected function schemaVersion(): SchemaVersion
    {
        return $this->version;
    }
}

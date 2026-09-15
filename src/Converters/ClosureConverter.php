<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use Closure;
use ReflectionFunction;
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

class ClosureConverter implements Converter
{
    use InteractsWithTypes;
    use InteractsWithEnums;
    use InteractsWithMembers;
    use InteractsWithDocblocks;

    protected ReflectionFunction $reflection;

    protected SchemaVersion $version;

    public function __construct(
        protected Closure $closure,
        ?SchemaVersion $schemaVersion = null,
        protected bool $ignoreUnknownTypes = false,
    ) {
        $this->reflection = new ReflectionFunction($this->closure);
        $this->version = $schemaVersion ?? SchemaVersion::default();
    }

    public function convert(): ObjectSchema
    {
        $objectSchema = new ObjectSchema(schemaVersion: $this->version);

        $docParser = $this->docParser($this->reflection);
        $this->applySchemaDocblock($objectSchema, $docParser, $this->reflection->isDeprecated());

        // Get the parameters from the doc parser
        $params = $docParser?->params();

        // Add the parameters to the objectschema
        foreach ($this->reflection->getParameters() as $reflectionParameter) {
            try {
                $objectSchema->properties($this->getSchemaFromReflectionParameter($reflectionParameter, $params));
            } catch (UnknownTypeException $e) {
                // If ignoreUnknownTypes is true, skip this parameter
                if ($this->ignoreUnknownTypes) {
                    continue;
                }

                // Otherwise, re-throw the exception
                throw $e;
            }
        }

        return $objectSchema;
    }

    /**
     * Create a schema from a given type.
     *
     * @param \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData> $nodeCollection
     */
    protected function getSchemaFromReflectionParameter(
        ReflectionParameter $reflectionParameter,
        ?NodeCollection $nodeCollection = null,
    ): JsonSchema {
        $jsonSchema = $this->baseMemberSchema($reflectionParameter);

        $docParam = $nodeCollection?->get($reflectionParameter->getName());

        // Add the description to the schema if it exists
        if ($docParam?->description !== null) {
            $jsonSchema->description($docParam->description);
        }

        if ($jsonSchema instanceof ArraySchema) {
            $this->applyArrayItems(
                $jsonSchema,
                $docParam instanceof NodeData ? $docParam->itemTypes : [],
            );
        }

        if ($reflectionParameter->isDefaultValueAvailable() && ! $reflectionParameter->isDefaultValueConstant()) {
            $jsonSchema->default($this->unwrapEnumValue($reflectionParameter->getDefaultValue()));
        }

        if (! $reflectionParameter->isOptional()) {
            $jsonSchema->required();
        }

        return $jsonSchema;
    }

    protected function schemaVersion(): SchemaVersion
    {
        return $this->version;
    }
}

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
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Support\NodeCollection;
use Cortex\JsonSchema\Converters\Concerns\InteractsWithTypes;

class ClosureConverter implements Converter
{
    use InteractsWithTypes;

    protected ReflectionFunction $reflection;

    public function __construct(
        protected Closure $closure,
        protected ?SchemaVersion $version = null,
    ) {
        $this->reflection = new ReflectionFunction($this->closure);
        $this->version = $version ?? SchemaVersion::default();
    }

    public function convert(): ObjectSchema
    {
        $objectSchema = new ObjectSchema(schemaVersion: $this->version);

        // Get the description from the doc parser
        $description = $this->getDocParser()?->description() ?? null;

        // Add the description to the schema if it exists
        if ($description !== null) {
            $objectSchema->description($description);
        }

        // Get the parameters from the doc parser
        $params = $this->getDocParser()?->params();

        // Add the parameters to the objectschema
        foreach ($this->reflection->getParameters() as $parameter) {
            $objectSchema->properties(self::getSchemaFromReflectionParameter($parameter, $params));
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
    ): Schema {
        $type = $reflectionParameter->getType();

        // @phpstan-ignore argument.type
        $schema = self::getSchemaFromReflectionType($type);

        $schema->title($reflectionParameter->getName());

        $docParam = $nodeCollection?->get($reflectionParameter->getName());

        // Add the description to the schema if it exists
        if ($docParam?->description !== null) {
            $schema->description($docParam->description);
        }

        if ($type === null || $type->allowsNull()) {
            $schema->nullable();
        }

        if ($reflectionParameter->isDefaultValueAvailable() && ! $reflectionParameter->isDefaultValueConstant()) {
            $defaultValue = $reflectionParameter->getDefaultValue();

            // If the default value is a backed enum, use its value
            if ($defaultValue instanceof BackedEnum) {
                $defaultValue = $defaultValue->value;
            }

            $schema->default($defaultValue);
        }

        if (! $reflectionParameter->isOptional()) {
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

    protected function getDocParser(): ?DocParser
    {
        $docComment = $this->reflection->getDocComment();

        return is_string($docComment)
            ? new DocParser($docComment)
            : null;
    }
}

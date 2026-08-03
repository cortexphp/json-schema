<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use JsonException;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Support\SchemaData;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Types\AbstractSchema;
use Cortex\JsonSchema\Types\TypelessSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

class JsonConverter implements Converter
{
    private SchemaData $schemaData;

    private SchemaVersion $schemaVersion;

    /**
     * @param string|array<int|string, mixed> $json
     */
    public function __construct(string|array $json, SchemaVersion $schemaVersion)
    {
        if (is_string($json)) {
            try {
                /** @var array<int|string, mixed>|string $decoded */
                $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new SchemaException('Invalid JSON Schema', $e->getCode(), previous: $e);
            }

            if (! is_array($decoded)) {
                throw new SchemaException('Invalid JSON Schema: root must be an object');
            }

            $this->schemaData = new SchemaData($decoded);
        } else {
            $this->schemaData = new SchemaData($json);
        }

        if (($schemaUri = $this->schemaData->getString('$schema')) !== null) {
            $this->schemaVersion = $this->detectSchemaVersion($schemaUri) ?? $schemaVersion;
        } else {
            $this->schemaVersion = $schemaVersion;
        }
    }

    public function convert(): JsonSchema
    {
        $title = $this->schemaData->getString('title');

        if ($this->shouldUseTypelessSchema()) {
            return $this->createTypelessSchema($title);
        }

        $type = $this->resolveType();

        return $this->createFor($type, $title);
    }

    /**
     * Resolve the schema type from an explicit type keyword or inferred keywords.
     */
    private function resolveType(): ?SchemaType
    {
        $type = $this->schemaData->getValue('type');

        if (is_array($type)) {
            return null;
        }

        if (is_string($type)) {
            return SchemaType::tryFrom($type) ?? throw new SchemaException(
                'Unsupported schema type: ' . $type,
            );
        }

        if ($type === null) {
            return $this->inferTypeFromKeywords();
        }

        throw new SchemaException('Unsupported schema type: ' . gettype($type));
    }

    /**
     * Create a schema for the given type.
     */
    private function createFor(?SchemaType $schemaType, ?string $title): JsonSchema
    {
        return match ($schemaType) {
            SchemaType::String => $this->createStringSchema($title),
            SchemaType::Number => $this->createNumberSchema($title),
            SchemaType::Integer => $this->createIntegerSchema($title),
            SchemaType::Boolean => $this->createBooleanSchema($title),
            SchemaType::Array => $this->createArraySchema($title),
            SchemaType::Object => $this->createObjectSchema($title),
            SchemaType::Null => $this->createNullSchema($title),
            null => $this->createUnionSchema($title),
        };
    }

    /**
     * Resolve a keyword value that may be a boolean or a subschema object.
     */
    private function getBoolOrSchema(string $key): bool|JsonSchema|null
    {
        $value = $this->schemaData->getValue($key);

        if (is_bool($value)) {
            return $value;
        }

        return $this->convertSubschema($value);
    }

    /**
     * Infer a schema type from present validation keywords when no explicit type is given.
     */
    private function inferTypeFromKeywords(): ?SchemaType
    {
        if ($this->schemaData->hasAny([
            'pattern', 'minLength', 'maxLength', 'format', 'contentEncoding', 'contentMediaType',
        ])) {
            return SchemaType::String;
        }

        foreach (['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf'] as $keyword) {
            if ($this->schemaData->has($keyword)) {
                $value = $this->schemaData->getValue($keyword);

                return is_int($value) || (is_float($value) && floor($value) === $value)
                    ? SchemaType::Integer
                    : SchemaType::Number;
            }
        }

        if ($this->schemaData->has('items') || $this->schemaData->has('prefixItems')) {
            return SchemaType::Array;
        }

        if ($this->schemaData->has('const')) {
            $const = $this->schemaData->getValue('const');

            return match (true) {
                is_string($const) => SchemaType::String,
                is_int($const) => SchemaType::Integer,
                is_float($const) => SchemaType::Number,
                is_bool($const) => SchemaType::Boolean,
                is_array($const) => SchemaType::Array,
                $const === null => SchemaType::Null,
                default => null,
            };
        }

        return null;
    }

    /**
     * Determine whether this schema should omit the type keyword.
     */
    private function shouldUseTypelessSchema(): bool
    {
        if ($this->schemaData->has('type')) {
            return false;
        }

        return $this->schemaData->hasAny([
            '$ref', 'allOf', 'anyOf', 'oneOf', 'not', 'if', 'then', 'else',
            '$defs', 'definitions', 'properties', 'patternProperties',
            'dependentSchemas', 'dependentRequired', 'required',
        ]);
    }

    /**
     * Apply keywords shared across all schema types.
     */
    private function applyCommonKeywords(AbstractSchema $schema): void
    {
        if (($id = $this->schemaData->getString('$id')) !== null) {
            $schema->id($id);
        }

        if (($anchor = $this->schemaData->getString('$anchor')) !== null) {
            $schema->anchor($anchor);
        }

        if (($description = $this->schemaData->getString('description')) !== null) {
            $schema->description($description);
        }

        if (($comment = $this->schemaData->getString('$comment')) !== null) {
            $schema->comment($comment);
        }

        if ($this->schemaData->has('default')) {
            $schema->default($this->schemaData->getValue('default'));
        }

        if ($this->schemaData->getBool('deprecated')) {
            $schema->deprecated();
        }

        if ($this->schemaData->getBool('readOnly')) {
            $schema->readOnly();
        }

        if ($this->schemaData->getBool('writeOnly')) {
            $schema->writeOnly();
        }

        if (($enum = $this->schemaData->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $schema->enum($enum);
        }

        if ($this->schemaData->has('const')) {
            $const = $this->schemaData->getValue('const');

            if (is_bool($const) || is_float($const) || is_int($const) || is_string($const) || $const === null) {
                $schema->const($const);
            }
        }

        if (($examples = $this->schemaData->getArray('examples')) !== null) {
            $schema->examples($examples);
        }

        if (($format = $this->schemaData->getString('format')) !== null) {
            $schema->format($format);
        }

        if (($ref = $this->schemaData->getString('$ref')) !== null) {
            $schema->ref($ref);
        }

        $this->applyConditionals($schema);
        $this->applyDefinitions($schema);
    }

    /**
     * Apply conditional composition keywords.
     */
    private function applyConditionals(AbstractSchema $schema): void
    {
        if (($allOf = $this->getArrayOfSchemas('allOf')) !== []) {
            $schema->allOf(...$allOf);
        }

        if (($anyOf = $this->getArrayOfSchemas('anyOf')) !== []) {
            $schema->anyOf(...$anyOf);
        }

        if (($oneOf = $this->getArrayOfSchemas('oneOf')) !== []) {
            $schema->oneOf(...$oneOf);
        }

        if (($not = $this->convertSubschema($this->schemaData->getValue('not'))) instanceof JsonSchema) {
            $schema->not($not);
        }

        if (($if = $this->convertSubschema($this->schemaData->getValue('if'))) instanceof JsonSchema) {
            $schema->if($if);

            if (($then = $this->convertSubschema($this->schemaData->getValue('then'))) instanceof JsonSchema) {
                $schema->then($then);
            }

            if (($else = $this->convertSubschema($this->schemaData->getValue('else'))) instanceof JsonSchema) {
                $schema->else($else);
            }
        }
    }

    /**
     * Apply $defs / definitions to the schema.
     */
    private function applyDefinitions(AbstractSchema $schema): void
    {
        $definitions = $this->schemaData->getArray('$defs') ?? $this->schemaData->getArray('definitions');

        if ($definitions === null) {
            return;
        }

        foreach ($definitions as $name => $definitionData) {
            if (! is_string($name)) {
                continue;
            }

            $definition = $this->convertSubschema($definitionData);

            if ($definition instanceof JsonSchema) {
                $schema->addDefinition($name, $definition);
            }
        }
    }

    /**
     * Apply object-specific keywords.
     */
    private function applyObjectKeywords(ObjectSchema|UnionSchema|TypelessSchema $objectSchema): void
    {
        $required = $this->schemaData->getArray('required') ?? [];

        if (($properties = $this->schemaData->getArray('properties')) !== null) {
            foreach ($properties as $name => $propertyData) {
                if (! is_string($name)) {
                    continue;
                }

                $propertySchema = $this->convertSubschema($propertyData);

                if (! $propertySchema instanceof JsonSchema) {
                    continue;
                }

                $objectSchema->property($name, $propertySchema, in_array($name, $required, true));
            }
        } elseif ($required !== []) {
            $requiredProps = array_values(array_filter($required, is_string(...)));

            if ($requiredProps !== []) {
                $objectSchema->requireProperties(...$requiredProps);
            }
        }

        if (($patternProperties = $this->schemaData->getArray('patternProperties')) !== null) {
            foreach ($patternProperties as $pattern => $propertyData) {
                if (! is_string($pattern)) {
                    continue;
                }

                $propertySchema = $this->convertSubschema($propertyData);

                if ($propertySchema instanceof JsonSchema) {
                    $objectSchema->patternProperty($pattern, $propertySchema);
                }
            }
        }

        if (($propertyNames = $this->convertSubschema(
            $this->schemaData->getValue('propertyNames'),
        )) instanceof JsonSchema) {
            $objectSchema->propertyNames($propertyNames);
        }

        if (($additionalProperties = $this->getBoolOrSchema('additionalProperties')) !== null) {
            $objectSchema->additionalProperties($additionalProperties);
        }

        if (($unevaluatedProperties = $this->getBoolOrSchema('unevaluatedProperties')) !== null) {
            $objectSchema->unevaluatedProperties($unevaluatedProperties);
        }

        if (($dependentSchemas = $this->schemaData->getArray('dependentSchemas')) !== null) {
            foreach ($dependentSchemas as $property => $dependentData) {
                if (! is_string($property)) {
                    continue;
                }

                $dependentSchema = $this->convertSubschema($dependentData);

                if ($dependentSchema instanceof JsonSchema) {
                    $objectSchema->dependentSchema($property, $dependentSchema);
                }
            }
        }

        if (($dependentRequired = $this->schemaData->getArray('dependentRequired')) !== null) {
            /** @var array<string, list<string>> $normalized */
            $normalized = [];

            foreach ($dependentRequired as $property => $requiredProperties) {
                if (! is_string($property)) {
                    continue;
                }

                if (! is_array($requiredProperties)) {
                    continue;
                }

                $normalized[$property] = array_values(array_filter($requiredProperties, is_string(...)));
            }

            if ($normalized !== []) {
                $objectSchema->dependentRequired($normalized);
            }
        }

        if (($minProperties = $this->schemaData->getInt('minProperties')) !== null) {
            $objectSchema->minProperties($minProperties);
        }

        if (($maxProperties = $this->schemaData->getInt('maxProperties')) !== null) {
            $objectSchema->maxProperties($maxProperties);
        }
    }

    /**
     * Apply array-specific keywords.
     */
    private function applyArrayKeywords(ArraySchema $arraySchema): void
    {
        $items = $this->schemaData->getValue('items');

        if (is_array($items)) {
            if (array_is_list($items)) {
                $tupleSchemas = array_values(array_filter(
                    array_map($this->convertSubschema(...), $items),
                ));

                if ($tupleSchemas !== []) {
                    $arraySchema->tupleItems($tupleSchemas);
                }
            } elseif (($itemsSchema = $this->convertSubschema($items)) instanceof JsonSchema) {
                $arraySchema->items($itemsSchema);
            }
        }

        if (($additionalItems = $this->getBoolOrSchema('additionalItems')) !== null) {
            $arraySchema->additionalItems($additionalItems);
        }

        if (($prefixItems = $this->schemaData->getArray('prefixItems')) !== null && array_is_list($prefixItems)) {
            $prefixSchemas = array_values(array_filter(
                array_map($this->convertSubschema(...), $prefixItems),
            ));

            if ($prefixSchemas !== []) {
                $arraySchema->prefixItems($prefixSchemas);
            }
        }

        if (($minItems = $this->schemaData->getInt('minItems')) !== null) {
            $arraySchema->minItems($minItems);
        }

        if (($maxItems = $this->schemaData->getInt('maxItems')) !== null) {
            $arraySchema->maxItems($maxItems);
        }

        if ($this->schemaData->getBool('uniqueItems')) {
            $arraySchema->uniqueItems();
        }

        if (($contains = $this->convertSubschema($this->schemaData->getValue('contains'))) instanceof JsonSchema) {
            $arraySchema->contains($contains);
        }

        if (($minContains = $this->schemaData->getInt('minContains')) !== null) {
            $arraySchema->minContains($minContains);
        }

        if (($maxContains = $this->schemaData->getInt('maxContains')) !== null) {
            $arraySchema->maxContains($maxContains);
        }

        if (($unevaluatedItems = $this->getBoolOrSchema('unevaluatedItems')) !== null) {
            $arraySchema->unevaluatedItems($unevaluatedItems);
        }
    }

    /**
     * Apply integer-specific numeric constraints.
     */
    private function applyIntegerConstraints(IntegerSchema $integerSchema): void
    {
        if (($minimum = $this->schemaData->getInt('minimum')) !== null) {
            $integerSchema->minimum($minimum);
        }

        if (($maximum = $this->schemaData->getInt('maximum')) !== null) {
            $integerSchema->maximum($maximum);
        }

        if (($exclusiveMinimum = $this->schemaData->getInt('exclusiveMinimum')) !== null) {
            $integerSchema->exclusiveMinimum($exclusiveMinimum);
        }

        if (($exclusiveMaximum = $this->schemaData->getInt('exclusiveMaximum')) !== null) {
            $integerSchema->exclusiveMaximum($exclusiveMaximum);
        }

        if (($multipleOf = $this->schemaData->getInt('multipleOf')) !== null) {
            $integerSchema->multipleOf($multipleOf);
        }
    }

    /**
     * Apply float numeric constraints.
     */
    private function applyNumberConstraints(NumberSchema|UnionSchema|TypelessSchema $schema): void
    {
        if (($minimum = $this->schemaData->getFloat('minimum')) !== null) {
            $schema->minimum($minimum);
        }

        if (($maximum = $this->schemaData->getFloat('maximum')) !== null) {
            $schema->maximum($maximum);
        }

        if (($exclusiveMinimum = $this->schemaData->getFloat('exclusiveMinimum')) !== null) {
            $schema->exclusiveMinimum($exclusiveMinimum);
        }

        if (($exclusiveMaximum = $this->schemaData->getFloat('exclusiveMaximum')) !== null) {
            $schema->exclusiveMaximum($exclusiveMaximum);
        }

        if (($multipleOf = $this->schemaData->getFloat('multipleOf')) !== null) {
            $schema->multipleOf($multipleOf);
        }
    }

    /**
     * Detect schema version from a $schema URI.
     */
    private function detectSchemaVersion(string $schemaUri): ?SchemaVersion
    {
        return match (true) {
            str_contains($schemaUri, 'draft-06') => SchemaVersion::Draft_06,
            str_contains($schemaUri, 'draft-07') => SchemaVersion::Draft_07,
            str_contains($schemaUri, 'draft/2019-09') => SchemaVersion::Draft_2019_09,
            str_contains($schemaUri, 'draft/2020-12') => SchemaVersion::Draft_2020_12,
            default => null,
        };
    }

    /**
     * Convert a raw value to a JsonSchema instance if it is an array subschema.
     */
    private function convertSubschema(mixed $value): ?JsonSchema
    {
        if (! is_array($value)) {
            return null;
        }

        return new self($value, $this->schemaVersion)->convert();
    }

    /**
     * Convert an array of raw subschema objects to JsonSchema instances.
     *
     * @return array<int, JsonSchema>
     */
    private function getArrayOfSchemas(string $key): array
    {
        $value = $this->schemaData->getArray($key);

        if ($value === null || ! array_is_list($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map($this->convertSubschema(...), $value),
        ));
    }

    private function createTypelessSchema(?string $title): TypelessSchema
    {
        $typelessSchema = new TypelessSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($typelessSchema);

        if ($this->schemaData->hasAny(['properties', 'patternProperties', 'required'])) {
            $this->applyObjectKeywords($typelessSchema);
        }

        return $typelessSchema;
    }

    private function createStringSchema(?string $title): StringSchema
    {
        $stringSchema = new StringSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($stringSchema);

        if (($minLength = $this->schemaData->getInt('minLength')) !== null) {
            $stringSchema->minLength($minLength);
        }

        if (($maxLength = $this->schemaData->getInt('maxLength')) !== null) {
            $stringSchema->maxLength($maxLength);
        }

        if (($pattern = $this->schemaData->getString('pattern')) !== null) {
            $stringSchema->pattern($pattern);
        }

        if (($contentEncoding = $this->schemaData->getString('contentEncoding')) !== null) {
            $stringSchema->contentEncoding($contentEncoding);
        }

        if (($contentMediaType = $this->schemaData->getString('contentMediaType')) !== null) {
            $stringSchema->contentMediaType($contentMediaType);
        }

        $contentSchema = $this->schemaData->getValue('contentSchema');

        if (is_bool($contentSchema)) {
            $stringSchema->contentSchema($contentSchema);
        } elseif (($converted = $this->convertSubschema($contentSchema)) instanceof JsonSchema) {
            $stringSchema->contentSchema($converted);
        }

        return $stringSchema;
    }

    private function createNumberSchema(?string $title): NumberSchema
    {
        $numberSchema = new NumberSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($numberSchema);
        $this->applyNumberConstraints($numberSchema);

        return $numberSchema;
    }

    private function createIntegerSchema(?string $title): IntegerSchema
    {
        $integerSchema = new IntegerSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($integerSchema);
        $this->applyIntegerConstraints($integerSchema);

        return $integerSchema;
    }

    private function createBooleanSchema(?string $title): BooleanSchema
    {
        $booleanSchema = new BooleanSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($booleanSchema);

        return $booleanSchema;
    }

    private function createArraySchema(?string $title): ArraySchema
    {
        $arraySchema = new ArraySchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($arraySchema);
        $this->applyArrayKeywords($arraySchema);

        return $arraySchema;
    }

    private function createObjectSchema(?string $title): ObjectSchema
    {
        $objectSchema = new ObjectSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($objectSchema);
        $this->applyObjectKeywords($objectSchema);

        return $objectSchema;
    }

    private function createNullSchema(?string $title): NullSchema
    {
        $nullSchema = new NullSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($nullSchema);

        return $nullSchema;
    }

    private function createUnionSchema(?string $title): UnionSchema
    {
        $typeData = $this->schemaData->getValue('type');

        if (is_array($typeData)) {
            $types = array_values(array_map(
                SchemaType::from(...),
                array_filter($typeData, is_string(...)),
            ));
            $schema = new UnionSchema($types, $title, $this->schemaVersion);
        } else {
            $schema = new UnionSchema(SchemaType::cases(), $title, $this->schemaVersion);
        }

        $this->applyCommonKeywords($schema);
        $this->applyNumberConstraints($schema);

        return $schema;
    }
}

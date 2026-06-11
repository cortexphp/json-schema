<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use JsonException;
use ReflectionClass;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Contracts\Converter;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Types\AbstractSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

class JsonConverter implements Converter
{
    /**
     * @var array<int|string, mixed>
     */
    private array $data;

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

            $this->data = $decoded;
        } else {
            $this->data = $json;
        }

        if (isset($this->data['$schema']) && is_string($this->data['$schema'])) {
            $this->schemaVersion = $this->detectSchemaVersion($this->data['$schema']);
        } else {
            $this->schemaVersion = $schemaVersion;
        }
    }

    public function convert(): JsonSchema
    {
        $type = $this->data['type'] ?? null;
        $title = isset($this->data['title']) && is_string($this->data['title']) ? $this->data['title'] : null;

        if ($this->shouldUseTypelessSchema()) {
            return $this->createTypelessSchema($title);
        }

        if ($type === null && ($inferredType = $this->inferTypeFromKeywords()) !== null) {
            return match ($inferredType) {
                'string' => $this->createStringSchema($title),
                'number' => $this->createNumberSchema($title),
                'integer' => $this->createIntegerSchema($title),
                'boolean' => $this->createBooleanSchema($title),
                'array' => $this->createArraySchema($title),
                default => $this->createUnionSchema($title),
            };
        }

        if (is_array($type)) {
            return $this->createUnionSchema($title);
        }

        return match ($type) {
            'string' => $this->createStringSchema($title),
            'number' => $this->createNumberSchema($title),
            'integer' => $this->createIntegerSchema($title),
            'boolean' => $this->createBooleanSchema($title),
            'array' => $this->createArraySchema($title),
            'object' => $this->createObjectSchema($title),
            'null' => $this->createNullSchema($title),
            null => $this->createUnionSchema($title),
            default => throw new SchemaException(
                'Unsupported schema type: ' . (is_string($type) ? $type : gettype($type)),
            ),
        };
    }

    /**
     * Safely get a string value from the data array.
     */
    private function getString(string $key): ?string
    {
        return isset($this->data[$key]) && is_string($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Safely get an integer value from the data array.
     */
    private function getInt(string $key): ?int
    {
        return isset($this->data[$key]) && is_numeric($this->data[$key]) ? (int) $this->data[$key] : null;
    }

    /**
     * Safely get a float value from the data array.
     */
    private function getFloat(string $key): ?float
    {
        return isset($this->data[$key]) && is_numeric($this->data[$key]) ? (float) $this->data[$key] : null;
    }

    /**
     * Safely get a boolean value from the data array.
     */
    private function getBool(string $key): bool
    {
        return isset($this->data[$key]) && (bool) $this->data[$key];
    }

    /**
     * Safely get an array value from the data array.
     *
     * @return array<int|string, mixed>|null
     */
    private function getArray(string $key): ?array
    {
        return isset($this->data[$key]) && is_array($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Get a mixed value from the data array.
     */
    private function getValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Resolve a keyword value that may be a boolean or a subschema object.
     */
    private function getBoolOrSchema(string $key): bool|JsonSchema|null
    {
        $value = $this->getValue($key);

        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return (new self($value, $this->schemaVersion))->convert();
        }

        return null;
    }

    /**
     * Infer a schema type from present validation keywords when no explicit type is given.
     */
    private function inferTypeFromKeywords(): ?string
    {
        $stringKeywords = array_flip(
            [
                'pattern', 'minLength', 'maxLength', 'format', 'contentEncoding', 'contentMediaType'],
        );

        if (array_intersect_key($this->data, $stringKeywords) !== []) {
            return 'string';
        }

        foreach (['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf'] as $keyword) {
            if (array_key_exists($keyword, $this->data)) {
                $value = $this->getValue($keyword);

                return is_int($value) || (is_float($value) && floor($value) === $value) ? 'integer' : 'number';
            }
        }

        if (array_key_exists('items', $this->data) || array_key_exists('prefixItems', $this->data)) {
            return 'array';
        }

        if (array_key_exists('const', $this->data)) {
            $const = $this->getValue('const');

            return match (true) {
                is_string($const) => 'string',
                is_int($const) => 'integer',
                is_float($const) => 'number',
                is_bool($const) => 'boolean',
                is_array($const) => 'array',
                $const === null => 'null',
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
        if (array_key_exists('type', $this->data)) {
            return false;
        }

        $structuralKeywords = array_flip([
            '$ref', 'allOf', 'anyOf', 'oneOf', 'not', 'if', 'then', 'else',
            '$defs', 'definitions', 'properties', 'patternProperties',
            'dependentSchemas', 'dependentRequired', 'required',
        ]);

        return array_intersect_key($this->data, $structuralKeywords) !== [];
    }

    /**
     * Apply keywords shared across all schema types.
     */
    private function applyCommonKeywords(AbstractSchema $schema): void
    {
        if (($id = $this->getString('$id')) !== null) {
            $schema->id($id);
        }

        if (($anchor = $this->getString('$anchor')) !== null) {
            $schema->anchor($anchor);
        }

        if (($description = $this->getString('description')) !== null) {
            $schema->description($description);
        }

        if (($comment = $this->getString('$comment')) !== null) {
            $schema->comment($comment);
        }

        if (array_key_exists('default', $this->data)) {
            $schema->default($this->getValue('default'));
        }

        if ($this->getBool('deprecated')) {
            $schema->deprecated();
        }

        if ($this->getBool('readOnly')) {
            $schema->readOnly();
        }

        if ($this->getBool('writeOnly')) {
            $schema->writeOnly();
        }

        if (($enum = $this->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $schema->enum($enum);
        }

        if (array_key_exists('const', $this->data)) {
            $const = $this->getValue('const');

            if (is_bool($const) || is_float($const) || is_int($const) || is_string($const) || $const === null) {
                $schema->const($const);
            }
        }

        if (($examples = $this->getArray('examples')) !== null) {
            $schema->examples($examples);
        }

        if (($format = $this->getString('format')) !== null) {
            $schema->format($format);
        }

        if (($ref = $this->getString('$ref')) !== null) {
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

        if (($not = $this->convertSubschema($this->getValue('not'))) instanceof JsonSchema) {
            $schema->not($not);
        }

        if (($if = $this->convertSubschema($this->getValue('if'))) instanceof JsonSchema) {
            $schema->if($if);

            if (($then = $this->convertSubschema($this->getValue('then'))) instanceof JsonSchema) {
                $schema->then($then);
            }

            if (($else = $this->convertSubschema($this->getValue('else'))) instanceof JsonSchema) {
                $schema->else($else);
            }
        }
    }

    /**
     * Apply $defs / definitions to the schema.
     */
    private function applyDefinitions(AbstractSchema $schema): void
    {
        $definitions = $this->getArray('$defs') ?? $this->getArray('definitions');

        if ($definitions === null) {
            return;
        }

        foreach ($definitions as $name => $definitionData) {
            if (! is_string($name)) {
                continue;
            }

            if (! is_array($definitionData)) {
                continue;
            }

            $schema->addDefinition($name, (new self($definitionData, $this->schemaVersion))->convert());
        }
    }

    /**
     * Apply object-specific keywords.
     */
    private function applyObjectKeywords(ObjectSchema|UnionSchema $objectSchema): void
    {
        $required = $this->getArray('required') ?? [];

        if (($properties = $this->getArray('properties')) !== null) {
            $propertySchemas = [];
            $requiredProps = [];

            foreach ($properties as $name => $propertyData) {
                if (! is_string($name)) {
                    continue;
                }

                if (! is_array($propertyData)) {
                    continue;
                }

                $propertySchemas[$name] = (new self($propertyData, $this->schemaVersion))->convert();

                if (in_array($name, $required, true)) {
                    $requiredProps[] = $name;
                }
            }

            $reflection = new ReflectionClass($objectSchema);
            $reflection->getProperty('properties')->setValue($objectSchema, $propertySchemas);
            $reflection->getProperty('requiredProperties')->setValue($objectSchema, $requiredProps);
        } elseif ($required !== []) {
            $requiredProps = array_values(array_filter($required, is_string(...)));

            if ($requiredProps !== []) {
                $reflection = new ReflectionClass($objectSchema);
                $reflection->getProperty('requiredProperties')->setValue($objectSchema, $requiredProps);
            }
        }

        if (($patternProperties = $this->getArray('patternProperties')) !== null) {
            foreach ($patternProperties as $pattern => $propertyData) {
                if (! is_string($pattern)) {
                    continue;
                }

                if (! is_array($propertyData)) {
                    continue;
                }

                $objectSchema->patternProperty($pattern, (new self($propertyData, $this->schemaVersion))->convert());
            }
        }

        if (($propertyNames = $this->getArray('propertyNames')) !== null) {
            $objectSchema->propertyNames((new self($propertyNames, $this->schemaVersion))->convert());
        }

        if (($additionalProperties = $this->getBoolOrSchema('additionalProperties')) !== null) {
            $objectSchema->additionalProperties($additionalProperties);
        }

        if (($unevaluatedProperties = $this->getBoolOrSchema('unevaluatedProperties')) !== null) {
            $objectSchema->unevaluatedProperties($unevaluatedProperties);
        }

        if (($dependentSchemas = $this->getArray('dependentSchemas')) !== null) {
            foreach ($dependentSchemas as $property => $dependentData) {
                if (! is_string($property)) {
                    continue;
                }

                if (! is_array($dependentData)) {
                    continue;
                }

                $objectSchema->dependentSchema($property, (new self($dependentData, $this->schemaVersion))->convert());
            }
        }

        if (($dependentRequired = $this->getArray('dependentRequired')) !== null) {
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

        if (($minProperties = $this->getInt('minProperties')) !== null) {
            $objectSchema->minProperties($minProperties);
        }

        if (($maxProperties = $this->getInt('maxProperties')) !== null) {
            $objectSchema->maxProperties($maxProperties);
        }
    }

    /**
     * Apply array-specific keywords.
     */
    private function applyArrayKeywords(ArraySchema $arraySchema): void
    {
        $items = $this->getValue('items');

        if (is_array($items)) {
            if (array_is_list($items)) {
                $tupleSchemas = array_values(array_map(
                    fn(array $item): JsonSchema => (new self($item, $this->schemaVersion))->convert(),
                    array_filter($items, is_array(...)),
                ));

                if ($tupleSchemas !== []) {
                    $arraySchema->tupleItems($tupleSchemas);
                }
            } else {
                $arraySchema->items((new self($items, $this->schemaVersion))->convert());
            }
        }

        if (($additionalItems = $this->getBoolOrSchema('additionalItems')) !== null) {
            $arraySchema->additionalItems($additionalItems);
        }

        if (($prefixItems = $this->getArray('prefixItems')) !== null && array_is_list($prefixItems)) {
            $prefixSchemas = array_values(array_map(
                fn(array $item): JsonSchema => (new self($item, $this->schemaVersion))->convert(),
                array_filter($prefixItems, is_array(...)),
            ));

            if ($prefixSchemas !== []) {
                $arraySchema->prefixItems($prefixSchemas);
            }
        }

        if (($minItems = $this->getInt('minItems')) !== null) {
            $arraySchema->minItems($minItems);
        }

        if (($maxItems = $this->getInt('maxItems')) !== null) {
            $arraySchema->maxItems($maxItems);
        }

        if ($this->getBool('uniqueItems')) {
            $arraySchema->uniqueItems();
        }

        if (($contains = $this->getArray('contains')) !== null) {
            $arraySchema->contains((new self($contains, $this->schemaVersion))->convert());
        }

        if (($minContains = $this->getInt('minContains')) !== null) {
            $arraySchema->minContains($minContains);
        }

        if (($maxContains = $this->getInt('maxContains')) !== null) {
            $arraySchema->maxContains($maxContains);
        }

        if (($unevaluatedItems = $this->getBoolOrSchema('unevaluatedItems')) !== null) {
            $arraySchema->unevaluatedItems($unevaluatedItems);
        }
    }

    /**
     * Apply numeric constraint keywords.
     */
    private function applyNumericKeywords(AbstractSchema $schema): void
    {
        if ($schema instanceof IntegerSchema) {
            if (($minimum = $this->getInt('minimum')) !== null) {
                $schema->minimum($minimum);
            }

            if (($maximum = $this->getInt('maximum')) !== null) {
                $schema->maximum($maximum);
            }

            if (($exclusiveMinimum = $this->getInt('exclusiveMinimum')) !== null) {
                $schema->exclusiveMinimum($exclusiveMinimum);
            }

            if (($exclusiveMaximum = $this->getInt('exclusiveMaximum')) !== null) {
                $schema->exclusiveMaximum($exclusiveMaximum);
            }

            if (($multipleOf = $this->getInt('multipleOf')) !== null) {
                $schema->multipleOf($multipleOf);
            }

            return;
        }

        if (! $schema instanceof NumberSchema && ! $schema instanceof UnionSchema) {
            return;
        }

        if (($minimum = $this->getFloat('minimum')) !== null) {
            $schema->minimum($minimum);
        }

        if (($maximum = $this->getFloat('maximum')) !== null) {
            $schema->maximum($maximum);
        }

        if (($exclusiveMinimum = $this->getFloat('exclusiveMinimum')) !== null) {
            $schema->exclusiveMinimum($exclusiveMinimum);
        }

        if (($exclusiveMaximum = $this->getFloat('exclusiveMaximum')) !== null) {
            $schema->exclusiveMaximum($exclusiveMaximum);
        }

        if (($multipleOf = $this->getFloat('multipleOf')) !== null) {
            $schema->multipleOf($multipleOf);
        }
    }

    /**
     * Detect schema version from a $schema URI.
     */
    private function detectSchemaVersion(string $schemaUri): SchemaVersion
    {
        return match (true) {
            str_contains($schemaUri, 'draft-06') => SchemaVersion::Draft_06,
            str_contains($schemaUri, 'draft-07') => SchemaVersion::Draft_07,
            str_contains($schemaUri, 'draft/2019-09') => SchemaVersion::Draft_2019_09,
            str_contains($schemaUri, 'draft/2020-12') => SchemaVersion::Draft_2020_12,
            default => $this->schemaVersion,
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

        return (new self($value, $this->schemaVersion))->convert();
    }

    /**
     * Convert an array of raw subschema objects to JsonSchema instances.
     *
     * @return array<int, JsonSchema>
     */
    private function getArrayOfSchemas(string $key): array
    {
        $value = $this->getArray($key);

        if ($value === null || ! array_is_list($value)) {
            return [];
        }

        return array_values(array_map(
            fn(array $item): JsonSchema => (new self($item, $this->schemaVersion))->convert(),
            array_filter($value, is_array(...)),
        ));
    }

    private function createTypelessSchema(?string $title): UnionSchema
    {
        $unionSchema = UnionSchema::typeless($title, $this->schemaVersion);
        $this->applyCommonKeywords($unionSchema);

        $objectKeywords = array_flip(['properties', 'patternProperties', 'required']);

        if (array_intersect_key($this->data, $objectKeywords) !== []) {
            $this->applyObjectKeywords($unionSchema);
        }

        return $unionSchema;
    }

    private function createStringSchema(?string $title): StringSchema
    {
        $stringSchema = new StringSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($stringSchema);

        if (($minLength = $this->getInt('minLength')) !== null) {
            $stringSchema->minLength($minLength);
        }

        if (($maxLength = $this->getInt('maxLength')) !== null) {
            $stringSchema->maxLength($maxLength);
        }

        if (($pattern = $this->getString('pattern')) !== null) {
            $stringSchema->pattern($pattern);
        }

        if (($contentEncoding = $this->getString('contentEncoding')) !== null) {
            $stringSchema->contentEncoding($contentEncoding);
        }

        if (($contentMediaType = $this->getString('contentMediaType')) !== null) {
            $stringSchema->contentMediaType($contentMediaType);
        }

        $contentSchema = $this->getValue('contentSchema');

        if (is_array($contentSchema)) {
            $stringSchema->contentSchema((new self($contentSchema, $this->schemaVersion))->convert());
        } elseif (is_bool($contentSchema)) {
            $stringSchema->contentSchema($contentSchema);
        }

        return $stringSchema;
    }

    private function createNumberSchema(?string $title): NumberSchema
    {
        $numberSchema = new NumberSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($numberSchema);
        $this->applyNumericKeywords($numberSchema);

        return $numberSchema;
    }

    private function createIntegerSchema(?string $title): IntegerSchema
    {
        $integerSchema = new IntegerSchema($title, $this->schemaVersion);
        $this->applyCommonKeywords($integerSchema);
        $this->applyNumericKeywords($integerSchema);

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
        $typeData = $this->getValue('type');

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
        $this->applyNumericKeywords($schema);

        return $schema;
    }
}

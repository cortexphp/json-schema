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
        // Parse JSON string if provided
        if (is_string($json)) {
            try {
                /** @var array<int|string, mixed>|string $decoded */
                $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new SchemaException('Invalid JSON Schema', previous: $e);
            }

            if (! is_array($decoded)) {
                throw new SchemaException('Invalid JSON Schema: root must be an object');
            }

            $this->data = $decoded;
        } else {
            $this->data = $json;
        }

        // Extract schema version from JSON if provided
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

        // Handle union types when type is an array
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
            null => $this->createUnionSchema($title), // Handle union types or no type
            default => throw new SchemaException('Unsupported schema type: ' . (is_string($type) ? $type : gettype(
                $type,
            ))),
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
     * Get a const value that's properly typed for schema.
     */
    private function getConstValue(string $key): bool|float|int|string|null
    {
        $value = $this->getValue($key);

        if (is_bool($value) || is_float($value) || is_int($value) || is_string($value) || $value === null) {
            return $value;
        }

        return null;
    }

    /**
     * Detect schema version from $schema URI.
     */
    private function detectSchemaVersion(string $schemaUri): SchemaVersion
    {
        return match (true) {
            str_contains($schemaUri, 'draft-07') => SchemaVersion::Draft_07,
            str_contains($schemaUri, 'draft/2019-09') => SchemaVersion::Draft_2019_09,
            str_contains($schemaUri, 'draft/2020-12') => SchemaVersion::Draft_2020_12,
            default => $this->schemaVersion,
        };
    }

    private function createStringSchema(?string $title): StringSchema
    {
        $stringSchema = new StringSchema($title, $this->schemaVersion);

        if (($minLength = $this->getInt('minLength')) !== null) {
            $stringSchema->minLength($minLength);
        }

        if (($maxLength = $this->getInt('maxLength')) !== null) {
            $stringSchema->maxLength($maxLength);
        }

        if (($pattern = $this->getString('pattern')) !== null) {
            $stringSchema->pattern($pattern);
        }

        if (($format = $this->getString('format')) !== null) {
            $stringSchema->format($format);
        }

        if (($enum = $this->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $stringSchema->enum($enum);
        }

        if (($const = $this->getConstValue('const')) !== null) {
            $stringSchema->const($const);
        }

        if (($default = $this->getValue('default')) !== null) {
            $stringSchema->default($default);
        }

        if (($description = $this->getString('description')) !== null) {
            $stringSchema->description($description);
        }

        if ($this->getBool('deprecated')) {
            $stringSchema->deprecated();
        }

        if ($this->getBool('readOnly')) {
            $stringSchema->readOnly();
        }

        if ($this->getBool('writeOnly')) {
            $stringSchema->writeOnly();
        }

        return $stringSchema;
    }

    private function createNumberSchema(?string $title): NumberSchema
    {
        $numberSchema = new NumberSchema($title, $this->schemaVersion);

        if (($minimum = $this->getFloat('minimum')) !== null) {
            $numberSchema->minimum($minimum);
        }

        if (($maximum = $this->getFloat('maximum')) !== null) {
            $numberSchema->maximum($maximum);
        }

        if (($exclusiveMinimum = $this->getFloat('exclusiveMinimum')) !== null) {
            $numberSchema->exclusiveMinimum($exclusiveMinimum);
        }

        if (($exclusiveMaximum = $this->getFloat('exclusiveMaximum')) !== null) {
            $numberSchema->exclusiveMaximum($exclusiveMaximum);
        }

        if (($multipleOf = $this->getFloat('multipleOf')) !== null) {
            $numberSchema->multipleOf($multipleOf);
        }

        if (($enum = $this->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $numberSchema->enum($enum);
        }

        if (($const = $this->getConstValue('const')) !== null) {
            $numberSchema->const($const);
        }

        if (($default = $this->getValue('default')) !== null) {
            $numberSchema->default($default);
        }

        if (($description = $this->getString('description')) !== null) {
            $numberSchema->description($description);
        }

        return $numberSchema;
    }

    private function createIntegerSchema(?string $title): IntegerSchema
    {
        $integerSchema = new IntegerSchema($title, $this->schemaVersion);

        if (($minimum = $this->getInt('minimum')) !== null) {
            $integerSchema->minimum($minimum);
        }

        if (($maximum = $this->getInt('maximum')) !== null) {
            $integerSchema->maximum($maximum);
        }

        if (($exclusiveMinimum = $this->getInt('exclusiveMinimum')) !== null) {
            $integerSchema->exclusiveMinimum($exclusiveMinimum);
        }

        if (($exclusiveMaximum = $this->getInt('exclusiveMaximum')) !== null) {
            $integerSchema->exclusiveMaximum($exclusiveMaximum);
        }

        if (($multipleOf = $this->getInt('multipleOf')) !== null) {
            $integerSchema->multipleOf($multipleOf);
        }

        if (($enum = $this->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $integerSchema->enum($enum);
        }

        if (($const = $this->getConstValue('const')) !== null) {
            $integerSchema->const($const);
        }

        if (($default = $this->getValue('default')) !== null) {
            $integerSchema->default($default);
        }

        if (($description = $this->getString('description')) !== null) {
            $integerSchema->description($description);
        }

        return $integerSchema;
    }

    private function createBooleanSchema(?string $title): BooleanSchema
    {
        $booleanSchema = new BooleanSchema($title, $this->schemaVersion);

        if (($const = $this->getConstValue('const')) !== null) {
            $booleanSchema->const($const);
        }

        if (($default = $this->getValue('default')) !== null) {
            $booleanSchema->default($default);
        }

        if (($description = $this->getString('description')) !== null) {
            $booleanSchema->description($description);
        }

        if ($this->getBool('readOnly')) {
            $booleanSchema->readOnly();
        }

        return $booleanSchema;
    }

    private function createArraySchema(?string $title): ArraySchema
    {
        $arraySchema = new ArraySchema($title, $this->schemaVersion);

        if (($items = $this->getArray('items')) !== null) {
            $converter = new self($items, $this->schemaVersion);
            $itemSchema = $converter->convert();
            $arraySchema->items($itemSchema);
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
            $converter = new self($contains, $this->schemaVersion);
            $containsSchema = $converter->convert();
            $arraySchema->contains($containsSchema);
        }

        if (($minContains = $this->getInt('minContains')) !== null) {
            $arraySchema->minContains($minContains);
        }

        if (($maxContains = $this->getInt('maxContains')) !== null) {
            $arraySchema->maxContains($maxContains);
        }

        if (($description = $this->getString('description')) !== null) {
            $arraySchema->description($description);
        }

        return $arraySchema;
    }

    private function createObjectSchema(?string $title): ObjectSchema
    {
        $objectSchema = new ObjectSchema($title, $this->schemaVersion);
        $required = $this->getArray('required') ?? [];

        if (($properties = $this->getArray('properties')) !== null) {
            $propertySchemas = [];
            $requiredProps = [];

            foreach ($properties as $name => $propertyData) {
                // Runtime validation needed for type safety
                if (! is_array($propertyData)) {
                    continue;
                }

                $converter = new self($propertyData, $this->schemaVersion);
                $propertySchema = $converter->convert();

                $propertySchemas[$name] = $propertySchema;

                // Track required properties
                if (in_array($name, $required, true)) {
                    $requiredProps[] = $name;
                }
            }

            // Set properties and required directly using reflection to avoid title requirement
            $reflectionClass = new ReflectionClass($objectSchema);
            $propertiesProperty = $reflectionClass->getProperty('properties');
            $propertiesProperty->setValue($objectSchema, $propertySchemas);

            $requiredProperty = $reflectionClass->getProperty('requiredProperties');
            $requiredProperty->setValue($objectSchema, $requiredProps);
        }

        $additionalProperties = $this->getValue('additionalProperties');

        if ($additionalProperties !== null) {
            if (is_bool($additionalProperties)) {
                $objectSchema->additionalProperties($additionalProperties);
            } elseif (is_array($additionalProperties)) {
                $converter = new self($additionalProperties, $this->schemaVersion);
                $additionalSchema = $converter->convert();
                $objectSchema->additionalProperties($additionalSchema);
            }
        }

        if (($minProperties = $this->getInt('minProperties')) !== null) {
            $objectSchema->minProperties($minProperties);
        }

        if (($maxProperties = $this->getInt('maxProperties')) !== null) {
            $objectSchema->maxProperties($maxProperties);
        }

        if (($description = $this->getString('description')) !== null) {
            $objectSchema->description($description);
        }

        return $objectSchema;
    }

    private function createNullSchema(?string $title): NullSchema
    {
        $nullSchema = new NullSchema($title, $this->schemaVersion);

        if (($description = $this->getString('description')) !== null) {
            $nullSchema->description($description);
        }

        return $nullSchema;
    }

    private function createUnionSchema(?string $title): UnionSchema
    {
        // Handle union types when type is an array
        $typeData = $this->getValue('type');

        if (is_array($typeData)) {
            $types = [];
            foreach ($typeData as $typeName) {
                if (is_string($typeName)) {
                    $types[] = SchemaType::from($typeName);
                }
            }

            $schema = new UnionSchema($types, $title, $this->schemaVersion);
        } else {
            // If no type is specified, treat as mixed
            $schema = new UnionSchema(SchemaType::cases(), $title, $this->schemaVersion);
        }

        if (($enum = $this->getArray('enum')) !== null && $enum !== []) {
            /** @var non-empty-array<bool|float|int|string|null> $enum */
            $schema->enum($enum);
        }

        if (($const = $this->getConstValue('const')) !== null) {
            $schema->const($const);
        }

        if (($description = $this->getString('description')) !== null) {
            $schema->description($description);
        }

        return $schema;
    }
}

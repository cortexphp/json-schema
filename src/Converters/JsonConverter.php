<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters;

use JsonException;
use ReflectionClass;
use Cortex\JsonSchema\Contracts\Schema;
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
use Cortex\JsonSchema\Exceptions\SchemaException;

/**
 * @phpstan-type JsonSchemaValue array{type?: string|array<string>, title?: string, properties?: array<string, JsonSchemaValue>, required?: array<string>, additionalProperties?: JsonSchemaValue|bool, minProperties?: int, maxProperties?: int, description?: string, minLength?: int, maxLength?: int, pattern?: string, format?: string, enum?: array<string|int|float|bool>, const?: string|int|float|bool, default?: string|int|float|bool, deprecated?: bool, readOnly?: bool, writeOnly?: bool, minItems?: int, maxItems?: int, uniqueItems?: bool, contains?: JsonSchemaValue, minContains?: int, maxContains?: int}
 */
class JsonConverter implements Converter
{
    /**
     * @var JsonSchemaValue
     */
    private array $data;

    private SchemaVersion $schemaVersion;

    /**
     * @param string|JsonSchemaValue $json
     */
    public function __construct(string|array $json, SchemaVersion $schemaVersion)
    {
        // Parse JSON string if provided
        if (is_string($json)) {
            try {
                /** @var JsonSchemaValue $decoded */
                $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new SchemaException('Invalid JSON Schema: ' . $e->getMessage());
            }

            $this->data = $decoded;
        } else {
            $this->data = $json;
        }

        // Extract schema version from JSON if provided
        if (isset($this->data['$schema'])) {
            $this->schemaVersion = $this->detectSchemaVersion($this->data['$schema']);
        } else {
            $this->schemaVersion = $schemaVersion;
        }
    }

    public function convert(): Schema
    {
        $type = $this->data['type'] ?? null;
        $title = $this->data['title'] ?? null;

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
            default => throw new SchemaException('Unsupported schema type: ' . $type),
        };
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

        if (isset($this->data['minLength'])) {
            $stringSchema->minLength((int) $this->data['minLength']);
        }

        if (isset($this->data['maxLength'])) {
            $stringSchema->maxLength((int) $this->data['maxLength']);
        }

        if (isset($this->data['pattern'])) {
            $stringSchema->pattern((string) $this->data['pattern']);
        }

        if (isset($this->data['format'])) {
            $stringSchema->format((string) $this->data['format']);
        }

        if (isset($this->data['enum'])) {
            $stringSchema->enum((array) $this->data['enum']);
        }

        if (isset($this->data['const'])) {
            $stringSchema->const($this->data['const']);
        }

        if (isset($this->data['default'])) {
            $stringSchema->default($this->data['default']);
        }

        if (isset($this->data['description'])) {
            $stringSchema->description((string) $this->data['description']);
        }

        if (isset($this->data['deprecated']) && $this->data['deprecated']) {
            $stringSchema->deprecated();
        }

        if (isset($this->data['readOnly']) && $this->data['readOnly']) {
            $stringSchema->readOnly();
        }

        if (isset($this->data['writeOnly']) && $this->data['writeOnly']) {
            $stringSchema->writeOnly();
        }

        return $stringSchema;
    }

    private function createNumberSchema(?string $title): NumberSchema
    {
        $numberSchema = new NumberSchema($title, $this->schemaVersion);

        if (isset($this->data['minimum'])) {
            $numberSchema->minimum((float) $this->data['minimum']);
        }

        if (isset($this->data['maximum'])) {
            $numberSchema->maximum((float) $this->data['maximum']);
        }

        if (isset($this->data['exclusiveMinimum'])) {
            $numberSchema->exclusiveMinimum((float) $this->data['exclusiveMinimum']);
        }

        if (isset($this->data['exclusiveMaximum'])) {
            $numberSchema->exclusiveMaximum((float) $this->data['exclusiveMaximum']);
        }

        if (isset($this->data['multipleOf'])) {
            $numberSchema->multipleOf((float) $this->data['multipleOf']);
        }

        if (isset($this->data['enum'])) {
            $numberSchema->enum((array) $this->data['enum']);
        }

        if (isset($this->data['const'])) {
            $numberSchema->const($this->data['const']);
        }

        if (isset($this->data['default'])) {
            $numberSchema->default($this->data['default']);
        }

        if (isset($this->data['description'])) {
            $numberSchema->description((string) $this->data['description']);
        }

        return $numberSchema;
    }

    private function createIntegerSchema(?string $title): IntegerSchema
    {
        $integerSchema = new IntegerSchema($title, $this->schemaVersion);

        if (isset($this->data['minimum'])) {
            $integerSchema->minimum((int) $this->data['minimum']);
        }

        if (isset($this->data['maximum'])) {
            $integerSchema->maximum((int) $this->data['maximum']);
        }

        if (isset($this->data['exclusiveMinimum'])) {
            $integerSchema->exclusiveMinimum((int) $this->data['exclusiveMinimum']);
        }

        if (isset($this->data['exclusiveMaximum'])) {
            $integerSchema->exclusiveMaximum((int) $this->data['exclusiveMaximum']);
        }

        if (isset($this->data['multipleOf'])) {
            $integerSchema->multipleOf((int) $this->data['multipleOf']);
        }

        if (isset($this->data['enum'])) {
            $integerSchema->enum((array) $this->data['enum']);
        }

        if (isset($this->data['const'])) {
            $integerSchema->const($this->data['const']);
        }

        if (isset($this->data['default'])) {
            $integerSchema->default($this->data['default']);
        }

        if (isset($this->data['description'])) {
            $integerSchema->description((string) $this->data['description']);
        }

        return $integerSchema;
    }

    private function createBooleanSchema(?string $title): BooleanSchema
    {
        $booleanSchema = new BooleanSchema($title, $this->schemaVersion);

        if (isset($this->data['const'])) {
            $booleanSchema->const((bool) $this->data['const']);
        }

        if (isset($this->data['default'])) {
            $booleanSchema->default((bool) $this->data['default']);
        }

        if (isset($this->data['description'])) {
            $booleanSchema->description((string) $this->data['description']);
        }

        if (isset($this->data['readOnly']) && $this->data['readOnly']) {
            $booleanSchema->readOnly();
        }

        return $booleanSchema;
    }

    private function createArraySchema(?string $title): ArraySchema
    {
        $arraySchema = new ArraySchema($title, $this->schemaVersion);

        if (isset($this->data['items'])) {
            $converter = new self($this->data['items'], $this->schemaVersion);
            $itemSchema = $converter->convert();
            $arraySchema->items($itemSchema);
        }

        if (isset($this->data['minItems'])) {
            $arraySchema->minItems((int) $this->data['minItems']);
        }

        if (isset($this->data['maxItems'])) {
            $arraySchema->maxItems((int) $this->data['maxItems']);
        }

        if (isset($this->data['uniqueItems']) && $this->data['uniqueItems']) {
            $arraySchema->uniqueItems();
        }

        if (isset($this->data['contains'])) {
            $converter = new self($this->data['contains'], $this->schemaVersion);
            $containsSchema = $converter->convert();
            $arraySchema->contains($containsSchema);
        }

        if (isset($this->data['minContains'])) {
            $arraySchema->minContains((int) $this->data['minContains']);
        }

        if (isset($this->data['maxContains'])) {
            $arraySchema->maxContains((int) $this->data['maxContains']);
        }

        if (isset($this->data['description'])) {
            $arraySchema->description((string) $this->data['description']);
        }

        return $arraySchema;
    }

    private function createObjectSchema(?string $title): ObjectSchema
    {
        $objectSchema = new ObjectSchema($title, $this->schemaVersion);
        $required = $this->data['required'] ?? [];

        if (isset($this->data['properties'])) {
            $properties = [];
            $requiredProps = [];

            foreach ($this->data['properties'] as $name => $propertyData) {
                $converter = new self($propertyData, $this->schemaVersion);
                $propertySchema = $converter->convert();

                $properties[$name] = $propertySchema;

                // Track required properties
                if (in_array($name, $required, true)) {
                    $requiredProps[] = $name;
                }
            }

            // Set properties and required directly using reflection to avoid title requirement
            $reflectionClass = new ReflectionClass($objectSchema);
            $propertiesProperty = $reflectionClass->getProperty('properties');
            $propertiesProperty->setAccessible(true);
            $propertiesProperty->setValue($objectSchema, $properties);

            $requiredProperty = $reflectionClass->getProperty('requiredProperties');
            $requiredProperty->setAccessible(true);
            $requiredProperty->setValue($objectSchema, $requiredProps);
        }

        if (isset($this->data['additionalProperties'])) {
            if (is_bool($this->data['additionalProperties'])) {
                $objectSchema->additionalProperties($this->data['additionalProperties']);
            } else {
                $converter = new self($this->data['additionalProperties'], $this->schemaVersion);
                $additionalSchema = $converter->convert();
                $objectSchema->additionalProperties($additionalSchema);
            }
        }

        if (isset($this->data['minProperties'])) {
            $objectSchema->minProperties((int) $this->data['minProperties']);
        }

        if (isset($this->data['maxProperties'])) {
            $objectSchema->maxProperties((int) $this->data['maxProperties']);
        }

        if (isset($this->data['description'])) {
            $objectSchema->description((string) $this->data['description']);
        }

        return $objectSchema;
    }

    private function createNullSchema(?string $title): NullSchema
    {
        $nullSchema = new NullSchema($title, $this->schemaVersion);

        if (isset($this->data['description'])) {
            $nullSchema->description((string) $this->data['description']);
        }

        return $nullSchema;
    }

    private function createUnionSchema(?string $title): UnionSchema
    {
        // Handle union types when type is an array
        if (isset($this->data['type']) && is_array($this->data['type'])) {
            $types = [];
            foreach ($this->data['type'] as $typeName) {
                $types[] = SchemaType::from($typeName);
            }

            $schema = new UnionSchema($types, $title, $this->schemaVersion);
        } else {
            // If no type is specified, treat as mixed
            $schema = new UnionSchema(SchemaType::cases(), $title, $this->schemaVersion);
        }

        if (isset($this->data['enum'])) {
            $schema->enum((array) $this->data['enum']);
        }

        if (isset($this->data['const'])) {
            $schema->const($this->data['const']);
        }

        if (isset($this->data['description'])) {
            $schema->description((string) $this->data['description']);
        }

        return $schema;
    }
}

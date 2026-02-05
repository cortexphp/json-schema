<?php

declare(strict_types=1);

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Converters\JsonConverter;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can convert string JSON schema', function (): void {
    $json = [
        'type' => 'string',
        'title' => 'Test String',
        'minLength' => 1,
        'maxLength' => 100,
        'pattern' => '^[a-z]+$',
        'format' => 'email',
        'description' => 'A test string',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'string',
        'title' => 'Test String',
        'minLength' => 1,
        'maxLength' => 100,
        'pattern' => '^[a-z]+$',
        'format' => 'email',
        'description' => 'A test string',
    ]);
});

it('can convert object JSON schema with properties', function (): void {
    $json = [
        'type' => 'object',
        'title' => 'User',
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => 'User name',
            ],
            'age' => [
                'type' => 'integer',
                'minimum' => 0,
            ],
        ],
        'required' => ['name'],
        'additionalProperties' => false,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(ObjectSchema::class);

    $output = $jsonSchema->toArray();
    expect($output['type'])->toBe('object');
    expect($output['title'])->toBe('User');
    expect($output['additionalProperties'])->toBe(false);
    expect($output['properties']['name']['type'])->toBe('string');
    expect($output['properties']['age']['type'])->toBe('integer');
    expect($output['required'])->toBe(['name']);
});

it('can convert array JSON schema with items', function (): void {
    $json = [
        'type' => 'array',
        'title' => 'String Array',
        'items' => [
            'type' => 'string',
        ],
        'minItems' => 1,
        'maxItems' => 10,
        'uniqueItems' => true,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(ArraySchema::class);

    $output = $jsonSchema->toArray();
    expect($output['type'])->toBe('array');
    expect($output['title'])->toBe('String Array');
    expect($output['items']['type'])->toBe('string');
    expect($output['minItems'])->toBe(1);
    expect($output['maxItems'])->toBe(10);
    expect($output['uniqueItems'])->toBe(true);
});

it('can convert number schema with constraints', function (): void {
    $json = [
        'type' => 'number',
        'minimum' => 0.5,
        'maximum' => 100.5,
        'multipleOf' => 0.5,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(NumberSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'number',
        'minimum' => 0.5,
        'maximum' => 100.5,
        'multipleOf' => 0.5,
    ]);
});

it('can convert integer schema with constraints', function (): void {
    $json = [
        'type' => 'integer',
        'minimum' => 1,
        'maximum' => 100,
        'exclusiveMinimum' => 0,
        'exclusiveMaximum' => 101,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(IntegerSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'integer',
        'minimum' => 1,
        'maximum' => 100,
        'exclusiveMinimum' => 0,
        'exclusiveMaximum' => 101,
    ]);
});

it('can convert boolean schema', function (): void {
    $json = [
        'type' => 'boolean',
        'default' => true,
        'description' => 'A boolean value',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(BooleanSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'boolean',
        'default' => true,
        'description' => 'A boolean value',
    ]);
});

it('can convert null schema', function (): void {
    $json = [
        'type' => 'null',
        'description' => 'A null value',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(NullSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'null',
        'description' => 'A null value',
    ]);
});

it('can convert union schema with multiple types', function (): void {
    $json = [
        'type' => ['string', 'number'],
        'description' => 'String or number',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(UnionSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => ['string', 'number'],
        'description' => 'String or number',
    ]);
});

it('can handle schema without type as mixed', function (): void {
    $json = [
        'description' => 'Any value',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(UnionSchema::class);
    // Should include all possible types
    expect($jsonSchema->toArray()['type'])->toHaveCount(7);
});

it('can parse JSON string input', function (): void {
    $jsonString = '{"type": "string", "minLength": 5}';

    $converter = new JsonConverter($jsonString, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->toArray()['minLength'])->toBe(5);
});

it('detects schema version from $schema property', function (): void {
    $json = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        'type' => 'string',
        'deprecated' => true,
    ];

    // Even though we pass Draft_07, it should detect 2019-09 from the $schema
    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->getVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Should work with deprecated (2019-09+ feature)
    expect($jsonSchema->toArray()['deprecated'])->toBe(true);
});

it('detects draft-06 schema version from $schema property', function (): void {
    $json = [
        '$schema' => 'http://json-schema.org/draft-06/schema#',
        'type' => 'string',
    ];

    // Even though we pass Draft_07, it should detect Draft_06 from the $schema
    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->getVersion())->toBe(SchemaVersion::Draft_06);
});

it('can handle nested schemas', function (): void {
    $json = [
        'type' => 'object',
        'properties' => [
            'address' => [
                'type' => 'object',
                'properties' => [
                    'street' => [
                        'type' => 'string',
                    ],
                    'number' => [
                        'type' => 'integer',
                    ],
                ],
                'required' => ['street'],
            ],
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(ObjectSchema::class);

    $output = $jsonSchema->toArray();
    expect($output['properties']['address']['type'])->toBe('object');
    expect($output['properties']['address']['properties']['street']['type'])->toBe('string');
    expect($output['properties']['address']['required'])->toBe(['street']);
});

it('throws exception for invalid JSON string', function (): void {
    expect(fn(): JsonConverter => new JsonConverter('invalid json', SchemaVersion::Draft_07))
        ->toThrow(SchemaException::class);
});

it('throws exception for non-object JSON', function (): void {
    expect(fn(): JsonConverter => new JsonConverter('"just a string"', SchemaVersion::Draft_07))
        ->toThrow(SchemaException::class, 'Invalid JSON Schema: root must be an object');
});

it('throws exception for unsupported schema type', function (): void {
    $json = [
        'type' => 'unsupported',
    ];

    expect(fn(): JsonSchema => (new JsonConverter($json, SchemaVersion::Draft_07))->convert())
        ->toThrow(SchemaException::class, 'Unsupported schema type: unsupported');
});

it('integrates with Schema::fromJson', function (): void {
    $json = [
        'type' => 'string',
        'minLength' => 3,
    ];

    $jsonSchema = Schema::fromJson($json);

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->toArray()['minLength'])->toBe(3);
});

it('can handle array with contains and min/max contains', function (): void {
    $json = [
        'type' => 'array',
        'contains' => [
            'type' => 'string',
        ],
        'minContains' => 1,
        'maxContains' => 3,
    ];

    // Use 2019-09 version which supports minContains/maxContains
    $converter = new JsonConverter($json, SchemaVersion::Draft_2019_09);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(ArraySchema::class);

    $output = $jsonSchema->toArray();
    expect($output['type'])->toBe('array');
    expect($output['contains']['type'])->toBe('string');
    expect($output['minContains'])->toBe(1);
    expect($output['maxContains'])->toBe(3);
});

it('can handle string with enum and const', function (): void {
    $json = [
        'type' => 'string',
        'enum' => ['red', 'green', 'blue'],
        'const' => 'red',
        'default' => 'red',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);
    expect($jsonSchema->toArray())->toMatchArray([
        'type' => 'string',
        'enum' => ['red', 'green', 'blue'],
        'const' => 'red',
        'default' => 'red',
    ]);
});

it('can handle string content annotations', function (): void {
    $json = [
        'type' => 'string',
        'contentEncoding' => 'base64',
        'contentMediaType' => 'application/json',
        'contentSchema' => [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
            ],
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2019_09);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);

    $output = $jsonSchema->toArray();
    expect($output['contentEncoding'])->toBe('base64');
    expect($output['contentMediaType'])->toBe('application/json');
    expect($output['contentSchema']['type'])->toBe('object');
    expect($output['contentSchema']['properties']['name']['type'])->toBe('string');
});

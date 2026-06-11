<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

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

it('can handle conditional keywords', function (): void {
    $json = [
        'type' => 'object',
        'if' => [
            'properties' => [
                'isMember' => [
                    'const' => true,
                ],
            ],
        ],
        'then' => [
            'properties' => [
                'membershipNumber' => [
                    'type' => 'string',
                    'minLength' => 10,
                ],
            ],
        ],
        'else' => [
            'properties' => [
                'membershipNumber' => [
                    'type' => 'string',
                    'minLength' => 15,
                ],
            ],
        ],
        'allOf' => [
            [
                'type' => 'object',
            ],
        ],
        'anyOf' => [
            [
                'type' => 'object',
            ],
        ],
        'oneOf' => [
            [
                'type' => 'object',
            ],
        ],
        'not' => [
            'type' => 'null',
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(ObjectSchema::class);

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output)->toHaveKey('if');
    expect($output)->toHaveKey('then');
    expect($output)->toHaveKey('else');
    expect($output['allOf'])->toHaveCount(1);
    expect($output['anyOf'])->toHaveCount(1);
    expect($output['oneOf'])->toHaveCount(1);
    expect($output['not']['type'])->toBe('null');
});

it('can handle $ref keyword', function (): void {
    $json = [
        'type' => 'object',
        '$ref' => '#/$defs/user',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema->toArray(includeSchemaRef: false)['$ref'])->toBe('#/$defs/user');
});

it('can handle $defs keyword', function (): void {
    $json = [
        'type' => 'object',
        '$defs' => [
            'name' => [
                'type' => 'string',
            ],
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output['$defs']['name']['type'])->toBe('string');
});

it('can handle metadata keywords', function (): void {
    $json = [
        'type' => 'string',
        '$comment' => 'Internal note',
        'examples' => ['example@example.com'],
        'deprecated' => true,
        'readOnly' => true,
        'writeOnly' => true,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2019_09);
    $jsonSchema = $converter->convert();

    expect($jsonSchema->toArray(includeSchemaRef: false))->toMatchArray([
        'type' => 'string',
        '$comment' => 'Internal note',
        'examples' => ['example@example.com'],
        'deprecated' => true,
        'readOnly' => true,
        'writeOnly' => true,
    ]);
});

it('can handle object pattern and property name keywords', function (): void {
    $json = [
        'type' => 'object',
        'patternProperties' => [
            '^S_' => [
                'type' => 'string',
            ],
        ],
        'propertyNames' => [
            'pattern' => '^[A-Za-z_]*$',
        ],
        'minProperties' => 1,
        'maxProperties' => 5,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output['patternProperties']['^S_']['type'])->toBe('string');
    expect($output['propertyNames']['pattern'])->toBe('^[A-Za-z_]*$');
    expect($output['minProperties'])->toBe(1);
    expect($output['maxProperties'])->toBe(5);
});

it('can handle dependentSchemas and dependentRequired', function (): void {
    $json = [
        'type' => 'object',
        'dependentRequired' => [
            'foo' => ['bar'],
        ],
        'dependentSchemas' => [
            'foo' => [
                'required' => ['bar'],
            ],
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output['dependentRequired'])->toBe([
        'foo' => ['bar'],
    ]);
    expect($output['dependentSchemas']['foo']['required'])->toBe(['bar']);
});

it('can handle unevaluatedProperties', function (): void {
    $json = [
        'type' => 'object',
        'unevaluatedProperties' => false,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema->toArray(includeSchemaRef: false)['unevaluatedProperties'])->toBe(false);
});

it('can handle prefixItems and unevaluatedItems', function (): void {
    $json = [
        'type' => 'array',
        'prefixItems' => [
            [
                'type' => 'string',
            ],
            [
                'type' => 'integer',
            ],
        ],
        'unevaluatedItems' => false,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output['prefixItems'])->toHaveCount(2);
    expect($output['prefixItems'][0]['type'])->toBe('string');
    expect($output['prefixItems'][1]['type'])->toBe('integer');
    expect($output['unevaluatedItems'])->toBe(false);
});

it('can handle tuple items and additionalItems', function (): void {
    $json = [
        'type' => 'array',
        'items' => [
            [
                'type' => 'string',
            ],
            [
                'type' => 'integer',
            ],
        ],
        'additionalItems' => false,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_07);
    $jsonSchema = $converter->convert();

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output['items'])->toHaveCount(2);
    expect($output['items'][0]['type'])->toBe('string');
    expect($output['items'][1]['type'])->toBe('integer');
    expect($output['additionalItems'])->toBe(false);
});

it('can handle $anchor keyword', function (): void {
    $json = [
        'type' => 'object',
        '$anchor' => 'ProductSchema',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema->toArray(includeSchemaRef: false)['$anchor'])->toBe('ProductSchema');
});

it('can handle typeless structured schemas', function (): void {
    $json = [
        '$defs' => [
            'product' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ],
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(UnionSchema::class);

    $output = $jsonSchema->toArray(includeSchemaRef: false);
    expect($output)->not->toHaveKey('type');
    expect($output['$defs']['product']['type'])->toBe('object');
});

it('can handle boolean const values', function (): void {
    $json = [
        'type' => 'boolean',
        'const' => false,
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema->toArray(includeSchemaRef: false)['const'])->toBeFalse();
});

it('can handle number schema metadata and constraints', function (): void {
    $json = [
        'type' => 'number',
        'minimum' => 0,
        'exclusiveMaximum' => 100,
        'enum' => [1.5, 2.5],
        'default' => 1.5,
        'description' => 'A number',
    ];

    $converter = new JsonConverter($json, SchemaVersion::Draft_2020_12);
    $jsonSchema = $converter->convert();

    expect($jsonSchema)->toBeInstanceOf(NumberSchema::class);
    expect($jsonSchema->toArray(includeSchemaRef: false))->toMatchArray([
        'type' => 'number',
        'minimum' => 0,
        'exclusiveMaximum' => 100,
        'enum' => [1.5, 2.5],
        'default' => 1.5,
        'description' => 'A number',
    ]);
});

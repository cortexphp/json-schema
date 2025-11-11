<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Enums;

use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Exceptions\UnknownTypeException;

it('can create a schema from scalar type', function (string $input, SchemaType $schemaType): void {
    expect(SchemaType::fromScalar($input))->toBe($schemaType);
})->with([
    'integer' => ['int', SchemaType::Integer],
    'float' => ['float', SchemaType::Number],
    'string' => ['string', SchemaType::String],
    'array' => ['array', SchemaType::Array],
    'boolean' => ['bool', SchemaType::Boolean],
    'object' => ['object', SchemaType::Object],
    'null' => ['null', SchemaType::Null],
]);

it('throws exception for unknown scalar type', function (): void {
    expect(fn(): SchemaType => SchemaType::fromScalar('unknown'))
        ->toThrow(UnknownTypeException::class, 'Unknown type: unknown');
});

it('can create schema instance', function (SchemaType $schemaType, string $expectedClass): void {
    expect($schemaType->instance())->toBeInstanceOf($expectedClass);
})->with([
    'string schema' => [SchemaType::String, StringSchema::class],
    'number schema' => [SchemaType::Number, NumberSchema::class],
    'integer schema' => [SchemaType::Integer, IntegerSchema::class],
    'boolean schema' => [SchemaType::Boolean, BooleanSchema::class],
    'object schema' => [SchemaType::Object, ObjectSchema::class],
    'array schema' => [SchemaType::Array, ArraySchema::class],
    'null schema' => [SchemaType::Null, NullSchema::class],
]);

it('sets title when creating schema instances', function (): void {
    $title = 'My Schema';
    $jsonSchema = SchemaType::String->instance($title);

    expect($jsonSchema)->toBeInstanceOf(StringSchema::class)
        ->and($jsonSchema->getTitle())->toBe($title);
});

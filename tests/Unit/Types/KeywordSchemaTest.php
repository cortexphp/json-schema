<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Pest\Expectation;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Cortex\JsonSchema\Types\Concerns\HasKeywords;

covers(HasKeywords::class);

it('can attach unknown keywords to a schema', function (): void {
    $stringSchema = Schema::string('pet')
        ->keyword('discriminator', [
            'propertyName' => 'petType',
            'mapping' => [
                'cat' => '#/components/schemas/Cat',
            ],
        ])
        ->keyword('x-additionalPropertiesName', 'attributes');

    $schemaArray = $stringSchema->toArray(includeSchemaRef: false);

    expect($schemaArray)->toHaveKey('discriminator', [
        'propertyName' => 'petType',
        'mapping' => [
            'cat' => '#/components/schemas/Cat',
        ],
    ])
        ->toHaveKey('x-additionalPropertiesName', 'attributes');
});

it('can attach unknown keywords on nested schemas', function (): void {
    $objectSchema = Schema::object('Consult')->properties(
        Schema::string('type')
            ->keyword('x-enum-varnames', ['Standard', 'Urgent']),
    )->oneOf(
        Schema::object('Cat')->keyword('x-additionalPropertiesName', 'catAttrs'),
        Schema::object('Dog')->keyword('discriminator', [
            'propertyName' => 'breed',
        ]),
    );

    $schemaArray = $objectSchema->toArray(includeSchemaRef: false);

    /** @var array<string, array<string, mixed>> $properties */
    $properties = $schemaArray['properties'];
    /** @var list<array<string, mixed>> $oneOf */
    $oneOf = $schemaArray['oneOf'];

    expect($properties['type'])->toHaveKey('x-enum-varnames', ['Standard', 'Urgent'])
        ->and($oneOf)
        ->sequence(
            fn(Expectation $expectation): Expectation => $expectation->toHaveKey(
                'x-additionalPropertiesName',
                'catAttrs',
            ),
            fn(Expectation $expectation): Expectation => $expectation->toHaveKey(
                'discriminator',
                [
                    'propertyName' => 'breed',
                ],
            ),
        )
        ->and($oneOf[0])->not->toHaveKey('$schema');
});

it('serialises nested schema keyword values without a dialect URI', function (): void {
    $stringSchema = Schema::string('wrapper')
        ->keyword('x-item', Schema::string('entry')->minLength(1));

    $schemaArray = $stringSchema->toArray(includeSchemaRef: false);

    expect($schemaArray['x-item'])->toBe([
        'type' => 'string',
        'title' => 'entry',
        'minLength' => 1,
    ])->not->toHaveKey('$schema');
});

it('overwrites a keyword when set twice', function (): void {
    $stringSchema = Schema::string('name')
        ->keyword('x-foo', 'first')
        ->keyword('x-foo', 'second');

    expect($stringSchema->toArray(includeSchemaRef: false))->toHaveKey('x-foo', 'second');
});

it('throws when a keyword name is empty', function (): void {
    expect(fn(): StringSchema => Schema::string('name')->keyword('', 'value'))
        ->toThrow(SchemaException::class, 'Keyword name must not be empty');
});

it('publishes false, zero, and null keyword values', function (): void {
    $stringSchema = Schema::string('name')
        ->keyword('x-flag', false)
        ->keyword('x-count', 0)
        ->keyword('x-null', null);

    $schemaArray = $stringSchema->toArray(includeSchemaRef: false);

    expect($schemaArray)->toHaveKey('x-flag', false)
        ->and($schemaArray)->toHaveKey('x-count', 0)
        ->and($schemaArray)->toHaveKey('x-null', null);
});

it('serialises nested schema values inside keyword arrays', function (): void {
    $stringSchema = Schema::string('wrapper')->keyword('x-oneOf', [
        Schema::string('a'),
        [
            'nested' => Schema::integer('b'),
        ],
    ]);

    $schemaArray = $stringSchema->toArray(includeSchemaRef: false);

    /** @var list<array<string, mixed>> $oneOf */
    $oneOf = $schemaArray['x-oneOf'];

    expect($oneOf[0])->toBe([
        'type' => 'string',
        'title' => 'a',
    ])
        ->and($oneOf[1]['nested'])
        ->toBe([
            'type' => 'integer',
            'title' => 'b',
        ])
        ->and($oneOf[0])->not->toHaveKey('$schema');
});

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Types;

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Types\TypelessSchema;

covers(TypelessSchema::class);

it('can create a typeless schema via the Schema facade', function (): void {
    $typelessSchema = Schema::typeless('shape')
        ->oneOf(
            Schema::object()->properties(
                Schema::string('kind')->const('circle'),
                Schema::number('radius')->required(),
            ),
            Schema::object()->properties(
                Schema::string('kind')->const('square'),
                Schema::number('size')->required(),
            ),
        );

    expect($typelessSchema)->toBeInstanceOf(TypelessSchema::class);

    $schemaArray = $typelessSchema->toArray();

    expect($schemaArray)->not->toHaveKey('type');
    expect($schemaArray)->toHaveKey('title', 'shape');
    expect($schemaArray['oneOf'])->toHaveCount(2);
});

it('can create a typeless definition-only schema', function (): void {
    $typelessSchema = Schema::typeless()
        ->addDefinition('address', Schema::object()->properties(
            Schema::string('street')->required(),
            Schema::string('city')->required(),
        ));

    $schemaArray = $typelessSchema->toArray();

    expect($schemaArray)->not->toHaveKey('type');
    expect($schemaArray['$defs']['address']['type'])->toBe('object');
});

it('stays typeless when nullable is called', function (): void {
    $typelessSchema = Schema::typeless('shape')->nullable();

    expect($typelessSchema)->toBeInstanceOf(TypelessSchema::class);
    expect($typelessSchema->toArray())->not->toHaveKey('type');
});

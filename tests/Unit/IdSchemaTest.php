<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Targets;

use Cortex\JsonSchema\Schema;

it('can create a schema with a $id', function (): void {
    $stringSchema = Schema::string('name')
        ->id('https://example.com/schemas/name');

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('$id', 'https://example.com/schemas/name');
    expect($schemaArray)->toHaveKey('type', 'string');
});

it('can convert $id from JSON', function (): void {
    $json = [
        '$id' => 'https://example.com/schemas/name',
        'type' => 'string',
    ];

    $jsonSchema = Schema::fromJson($json);

    expect($jsonSchema->toArray())->toHaveKey('$id', 'https://example.com/schemas/name');
});

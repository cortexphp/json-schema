<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Targets;

use Cortex\JsonSchema\SchemaFactory as Schema;

it('can create a schema with a $ref', function (): void {
    $schema = Schema::string('name')
        ->ref('#/definitions/custom');

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$ref', '#/definitions/custom');
    expect($schemaArray)->toHaveKey('type', 'string');
});

it('can create a schema with both $ref and other properties', function (): void {
    $schema = Schema::string('name')
        ->ref('#/definitions/custom')
        ->description('A custom type')
        ->nullable();

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('$ref', '#/definitions/custom');
    expect($schemaArray)->toHaveKey('type', ['string', 'null']);
    expect($schemaArray)->toHaveKey('description', 'A custom type');
});

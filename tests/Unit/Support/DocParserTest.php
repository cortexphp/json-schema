<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Support\DocParser;

it('can parse description', function (): void {
    $docblock = '/** This is a test docblock */';
    $parser = new DocParser($docblock);

    expect($parser->description())->toBe('This is a test docblock');
});

it('can parse with no description', function (): void {
    $docblock = '/** */';
    $parser = new DocParser($docblock);

    expect($parser->description())->toBeNull();
});

it('can parse params', function (): void {
    $docblock = '/**
        @param ?string $nickname The nickname of the user
        @param ?int $age The age of the user
        @param float|int $price The price of the product
        @param $status
    */';
    $parser = new DocParser($docblock);

    expect($parser->params())->toBe([
        [
            'name' => 'nickname',
            'type' => '?string',
            'description' => 'The nickname of the user',
        ],
        [
            'name' => 'age',
            'type' => '?int',
            'description' => 'The age of the user',
        ],
        [
            'name' => 'price',
            'type' => '(float | int)',
            'description' => 'The price of the product',
        ],
        [
            'name' => 'status',
            'type' => null,
            'description' => null,
        ],
    ]);
});

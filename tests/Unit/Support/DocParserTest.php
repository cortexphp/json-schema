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

it('can parse params and description', function (): void {
    $docblock = <<<'EOD'
        /**
         * This is the description
         * @param ?string $nickname The nickname of the user
         * @param ?int $age The age of the user
         * @param float|int $price The price of the product
         * @param \Cortex\JsonSchema\Tests\Unit\Support\DocParserTest $test The test
         * @param $any
         */
        EOD;
    $parser = new DocParser($docblock);

    expect($parser->description())->toBe('This is the description');
    expect($parser->params())->toBe([
        [
            'name' => 'nickname',
            'types' => [
                'string',
                'null',
            ],
            'description' => 'The nickname of the user',
        ],
        [
            'name' => 'age',
            'types' => [
                'int',
                'null',
            ],
            'description' => 'The age of the user',
        ],
        [
            'name' => 'price',
            'types' => [
                'float',
                'int',
            ],
            'description' => 'The price of the product',
        ],
        [
            'name' => 'test',
            'types' => [
                '\Cortex\JsonSchema\Tests\Unit\Support\DocParserTest',
            ],
            'description' => 'The test',
        ],
        [
            'name' => 'any',
            'types' => [],
            'description' => null,
        ],
    ]);
});

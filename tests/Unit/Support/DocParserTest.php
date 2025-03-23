<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Support\NodeData;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Support\NodeCollection;

covers(DocParser::class);

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

    $params = $parser->params();

    expect($params)->toBeInstanceOf(NodeCollection::class);

    expect($params->get('nickname'))->toBeInstanceOf(NodeData::class);
    expect($params->get('nickname')->name)->toBe('nickname');
    expect($params->get('nickname')->description)->toBe('The nickname of the user');
    expect($params->get('nickname')->types)->toBe(['string', 'null']);

    expect($params->get('age'))->toBeInstanceOf(NodeData::class);
    expect($params->get('age')->name)->toBe('age');
    expect($params->get('age')->description)->toBe('The age of the user');
    expect($params->get('age')->types)->toBe(['int', 'null']);

    expect($params->get('price'))->toBeInstanceOf(NodeData::class);
    expect($params->get('price')->name)->toBe('price');
    expect($params->get('price')->description)->toBe('The price of the product');
    expect($params->get('price')->types)->toBe(['float', 'int']);

    expect($params->get('test'))->toBeInstanceOf(NodeData::class);
    expect($params->get('test')->name)->toBe('test');
    expect($params->get('test')->description)->toBe('The test');
    expect($params->get('test')->types)->toBe(['\Cortex\JsonSchema\Tests\Unit\Support\DocParserTest']);

    expect($params->get('any'))->toBeInstanceOf(NodeData::class);
    expect($params->get('any')->name)->toBe('any');
    expect($params->get('any')->description)->toBeNull();
    expect($params->get('any')->types)->toBe([]);
});

it('can parse variables', function (): void {
    $docblock = '/** @var string $nickname The nickname of the user */';
    $parser = new DocParser($docblock);

    $variable = $parser->variable();

    expect($variable)->toBeInstanceOf(NodeData::class);

    expect($variable->name)->toBe('nickname');
    expect($variable->types)->toBe(['string']);
    expect($variable->description)->toBe('The nickname of the user');
});

it('can parse variables with multiple types', function (): void {
    $docblock = '/** @var string|int $nickname The nickname of the user */';
    $parser = new DocParser($docblock);

    $variable = $parser->variable();

    expect($variable)->toBeInstanceOf(NodeData::class);

    expect($variable->name)->toBe('nickname');
    expect($variable->types)->toBe(['string', 'int']);
    expect($variable->description)->toBe('The nickname of the user');
});

it('can parse a multiline description', function (): void {
    $docblock = '/** This is a test docblock
     * with multiple lines
     * and a new line
     */';
    $parser = new DocParser($docblock);

    expect($parser->description())->toBe("This is a test docblock\nwith multiple lines\nand a new line");
});

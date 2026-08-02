<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Support;

use ReflectionClass;
use Cortex\JsonSchema\Support\NodeData;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Support\NodeCollection;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;

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

    expect($params)->toBeInstanceOf(NodeCollection::class)
        ->and($params->get('nickname'))
        ->toBeInstanceOf(NodeData::class)
        ->and($params->get('nickname')->name)
        ->toBe('nickname')
        ->and($params->get('nickname')->description)
        ->toBe('The nickname of the user')
        ->and($params->get('nickname')->types)
        ->toBe(['string', 'null'])
        ->and($params->get('age'))
        ->toBeInstanceOf(NodeData::class)
        ->and($params->get('age')->name)
        ->toBe('age')
        ->and($params->get('age')->description)
        ->toBe('The age of the user')
        ->and($params->get('age')->types)
        ->toBe(['int', 'null'])
        ->and($params->get('price'))
        ->toBeInstanceOf(NodeData::class)
        ->and($params->get('price')->name)
        ->toBe('price')
        ->and($params->get('price')->description)
        ->toBe('The price of the product')
        ->and($params->get('price')->types)
        ->toBe(['float', 'int'])
        ->and($params->get('test'))
        ->toBeInstanceOf(NodeData::class)
        ->and($params->get('test')->name)
        ->toBe('test')
        ->and($params->get('test')->description)
        ->toBe('The test')
        ->and($params->get('test')->types)
        ->toBe(['\Cortex\JsonSchema\Tests\Unit\Support\DocParserTest'])
        ->and($params->get('any'))
        ->toBeInstanceOf(NodeData::class)
        ->and($params->get('any')->name)
        ->toBe('any')
        ->and($params->get('any')->description)
        ->toBeNull()
        ->and($params->get('any')->types)
        ->toBeEmpty();
});

it('can parse variables', function (): void {
    $docblock = '/** @var string $nickname The nickname of the user */';
    $parser = new DocParser($docblock);

    $variable = $parser->variable();

    expect($variable)->toBeInstanceOf(NodeData::class)
        ->and($variable->name)
        ->toBe('nickname')
        ->and($variable->types)
        ->toBe(['string'])
        ->and($variable->description)
        ->toBe('The nickname of the user');
});

it('can parse variables with multiple types', function (): void {
    $docblock = '/** @var string|int $nickname The nickname of the user */';
    $parser = new DocParser($docblock);

    $variable = $parser->variable();

    expect($variable)->toBeInstanceOf(NodeData::class)
        ->and($variable->name)
        ->toBe('nickname')
        ->and($variable->types)
        ->toBe(['string', 'int'])
        ->and($variable->description)
        ->toBe('The nickname of the user');
});

it('can parse a multiline description', function (): void {
    $docblock = '/** This is a test docblock
     * with multiple lines
     * and a new line
     */';
    $parser = new DocParser($docblock);

    expect($parser->description())->toBe("This is a test docblock\nwith multiple lines\nand a new line");
});

it('handles empty descriptions in variables correctly', function (): void {
    // Test variable with empty description - should return null for description
    // This kills the EmptyStringToNotEmpty mutation on line 71
    $docblock = '/** @var string $name */';
    $parser = new DocParser($docblock);

    $variable = $parser->variable();

    expect($variable)->toBeInstanceOf(NodeData::class)
        ->and($variable->name)
        ->toBe('name')
        ->and($variable->types)
        ->toBe(['string'])
        ->and($variable->description)
        ->toBeNull(); // Empty description should be null

    // Compare with non-empty description
    $docblockWithDescription = '/** @var string $name The user name */';
    $parserWithDescription = new DocParser($docblockWithDescription);
    $variableWithDescription = $parserWithDescription->variable();

    expect($variableWithDescription->description)->toBe('The user name');
});

it('handles empty descriptions in parameters correctly', function (): void {
    // Test parameter with empty description
    $docblock = '/** @param string $name */';
    $parser = new DocParser($docblock);

    $params = $parser->params();
    $nameParam = $params->get('name');

    expect($nameParam)->toBeInstanceOf(NodeData::class)
        ->and($nameParam->name)
        ->toBe('name')
        ->and($nameParam->types)
        ->toBe(['string'])
        ->and($nameParam->description)
        ->toBeNull(); // Empty description should be null
});

it('correctly maps different type node scenarios', function (): void {
    // Test TypelessParamTagValueNode (no type specified)
    $docblock = '/** @param $typeless */';
    $parser = new DocParser($docblock);

    $params = $parser->params();
    $typelessParam = $params->get('typeless');

    expect($typelessParam)->toBeInstanceOf(NodeData::class)
        ->and($typelessParam->name)
        ->toBe('typeless')
        ->and($typelessParam->types)
        ->toBeEmpty(); // Should be empty array for typeless
    expect($typelessParam->description)->toBeNull();

    // Test UnionTypeNode
    $docblockUnion = '/** @param string|int|float $union */';
    $parserUnion = new DocParser($docblockUnion);
    $nodeCollection = $parserUnion->params();
    $unionParam = $nodeCollection->get('union');

    expect($unionParam->types)->toBe(['string', 'int', 'float']);

    // Test NullableTypeNode
    $docblockNullable = '/** @param ?string $nullable */';
    $parserNullable = new DocParser($docblockNullable);
    $paramsNullable = $parserNullable->params();
    $nullableParam = $paramsNullable->get('nullable');

    expect($nullableParam->types)->toBe(['string', 'null']);

    // Test simple TypeNode (default case)
    $docblockSimple = '/** @param int $simple */';
    $parserSimple = new DocParser($docblockSimple);
    $paramsSimple = $parserSimple->params();
    $simpleParam = $paramsSimple->get('simple');

    expect($simpleParam->types)->toBe(['int']);
});

it('correctly filters text nodes in getTextNodes method', function (): void {
    // Test that getTextNodes properly filters PhpDocTextNode instances
    // This kills the UnwrapArrayFilter and InstanceOfToTrue mutations

    $docblock = '/**
     * This is a description
     * @param string $test A parameter
     * @var int $number A variable
     */';
    $parser = new DocParser($docblock);

    // Use reflection to access the protected method
    $reflection = new ReflectionClass($parser);
    $reflectionMethod = $reflection->getMethod('parse');

    $getTextNodesMethod = $reflection->getMethod('getTextNodes');

    $phpDocNode = $reflectionMethod->invoke($parser);
    $textNodes = $getTextNodesMethod->invoke($parser, $phpDocNode->children);

    // Should only return PhpDocTextNode instances, filtering out param/var nodes
    expect($textNodes)->toBeArray();
    expect($textNodes)->not->toBeEmpty()
        ->toContainOnlyInstancesOf(PhpDocTextNode::class);

    // Test that description parsing works correctly with filtered nodes
    expect($parser->description())->toBe('This is a description');
});

it('handles docblocks with mixed content correctly', function (): void {
    // Test a complex docblock with multiple types of content
    $docblock = '/**
     * A complex description
     * with multiple lines
     * @param string|null $name The name parameter
     * @param ?int $age The age parameter
     * @param $typeless A parameter without type
     * @var array<string, mixed> $data Some data
     */';
    $parser = new DocParser($docblock);

    // Test description parsing
    expect($parser->description())->toBe('A complex description' . PHP_EOL . 'with multiple lines');

    // Test parameter parsing
    $params = $parser->params();

    expect($params->get('name')->types)->toBe(['string', 'null'])
        ->and($params->get('name')->description)
        ->toBe('The name parameter')
        ->and($params->get('age')->types)
        ->toBe(['int', 'null'])
        ->and($params->get('age')->description)
        ->toBe('The age parameter')
        ->and($params->get('typeless')->types)
        ->toBeEmpty()
        ->and($params->get('typeless')->description)
        ->toBe('A parameter without type');

    // Test variable parsing
    $variable = $parser->variable();
    expect($variable)->toBeInstanceOf(NodeData::class)
        ->and($variable->name)
        ->toBe('data')
        ->and($variable->types)
        ->toBe(['array<string, mixed>'])
        ->and($variable->description)
        ->toBe('Some data');
});

it('handles edge cases in text node filtering', function (): void {
    // Test that getTextNodes works correctly even with edge cases
    $docblock = '/** @param string $only_params */'; // No description text
    $parser = new DocParser($docblock);

    expect($parser->description())->toBeNull(); // Should handle no text nodes gracefully

    // Test with only text, no tags
    $textOnlyBlock = '/** Just a simple description */';
    $textOnlyParser = new DocParser($textOnlyBlock);

    expect($textOnlyParser->description())->toBe('Just a simple description')
        ->and($textOnlyParser->params()->nodes)
        ->toBeEmpty()
        ->and($textOnlyParser->variable())
        ->toBeNull();
});

it('exercises all type mapping branches in mapValueNodeToTypes', function (): void {
    // This test specifically targets the match(true) statement to ensure coverage
    // and kill the TrueToFalse mutation on line 92

    // Test complex union types to ensure match(true) logic is fully exercised
    $complexUnionDocblock = '/** @param string|int|float|bool|null $complex Multi-type parameter */';
    $parser = new DocParser($complexUnionDocblock);
    $params = $parser->params();
    $complexParam = $params->get('complex');

    expect($complexParam->types)->toBe(['string', 'int', 'float', 'bool', 'null'])
        ->and($complexParam->description)
        ->toBe('Multi-type parameter');

    // Test deeply nested nullable type
    $nestedDocblock = '/** @param ?\Cortex\JsonSchema\Support\NodeData $nested */';
    $nestedParser = new DocParser($nestedDocblock);
    $nodeCollection = $nestedParser->params();
    $nestedParam = $nodeCollection->get('nested');

    expect($nestedParam->types)->toBe([sprintf('\%s', NodeData::class), 'null']);

    // Test array types (default case in match statement)
    $arrayDocblock = '/** @param array<string, int> $arrayType */';
    $arrayParser = new DocParser($arrayDocblock);
    $arrayParams = $arrayParser->params();
    $arrayParam = $arrayParams->get('arrayType');

    expect($arrayParam->types)->toBe(['array<string, int>']);

    // Test variable with complex types to exercise the same code path
    $varDocblock = '/** @var \DateTime|\DateTimeImmutable $dateVar Date variable */';
    $varParser = new DocParser($varDocblock);
    $variable = $varParser->variable();

    expect($variable->types)->toBe(['\DateTime', '\DateTimeImmutable'])
        ->and($variable->description)
        ->toBe('Date variable');
});

it('directly tests mapValueNodeToTypes method coverage', function (): void {
    // Direct test to ensure the match(true) statement is covered
    // This should definitely kill the TrueToFalse mutation on line 92

    $docblock = '/**
     * @param string|int $union Union type
     * @param ?string $nullable Nullable type
     * @param int $simple Simple type
     * @param $typeless Typeless param
     */';

    $parser = new DocParser($docblock);

    // Use reflection to access the parse method and get the tag values
    $reflection = new ReflectionClass($parser);
    $reflectionMethod = $reflection->getMethod('parse');

    $phpDocNode = $reflectionMethod->invoke($parser);
    $paramTags = $phpDocNode->getParamTagValues();
    $typelessTags = $phpDocNode->getTypelessParamTagValues();

    // Test that we have the expected tags
    expect($paramTags)->toHaveCount(3); // union, nullable, simple
    expect($typelessTags)->toHaveCount(1); // typeless

    // Access the static method directly to ensure coverage
    $mapMethod = $reflection->getMethod('mapValueNodeToTypes');

    // Test each type scenario directly
    foreach ($paramTags as $paramTag) {
        $types = $mapMethod->invoke(null, $paramTag);
        expect($types)->toBeArray();

        // Verify specific type mappings based on parameter name
        if ($paramTag->parameterName === '$union') {
            expect($types)->toBe(['string', 'int']);
        } elseif ($paramTag->parameterName === '$nullable') {
            expect($types)->toBe(['string', 'null']);
        } elseif ($paramTag->parameterName === '$simple') {
            expect($types)->toBe(['int']);
        }
    }

    // Test typeless parameter
    foreach ($typelessTags as $typelessTag) {
        $types = $mapMethod->invoke(null, $typelessTag);
        expect($types)->toBe([]);
    }
});

it('can detect deprecated docblocks', function (): void {
    $docblock = '/** @deprecated This method is deprecated */';
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeTrue();
});

it('can detect non-deprecated docblocks', function (): void {
    $docblock = '/** This is a regular docblock */';
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeFalse();
});

it('can detect deprecated with other tags', function (): void {
    $docblock = <<<'EOD'
        /**
         * This is a test method
         * @deprecated Use newMethod() instead
         * @param string $name The name parameter
         * @return string
         */
        EOD;
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeTrue()
        ->and($parser->description())
        ->toBe('This is a test method');
});

it('can detect deprecated without description', function (): void {
    $docblock = '/** @deprecated */';
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeTrue();
});

it('handles empty docblock for deprecation check', function (): void {
    $docblock = '/** */';
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeFalse();
});

it('can detect multiple deprecated tags', function (): void {
    $docblock = <<<'EOD'
        /**
         * @deprecated Since version 2.0
         * @deprecated Will be removed in 3.0
         */
        EOD;
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeTrue();
});

it(
    'can parse array item types from docblocks',
    function (string $docblock, array $expectedItemTypes, string $source): void {
        $parser = new DocParser($docblock);

        $node = $source === 'params'
            ? $parser->params()->get('tags')
            : $parser->variable();

        expect($node?->itemTypes)->toBe($expectedItemTypes);
    },
)->with([
    'string[]' => ['/** @var string[] $tags */', ['string'], 'variable'],
    'array<string>' => ['/** @var array<string> $tags */', ['string'], 'variable'],
    'array<string, int>' => ['/** @var array<string, int> $scores */', ['int'], 'variable'],
    'list<bool>' => ['/** @var list<bool> $flags */', ['bool'], 'variable'],
    'array<int|string>' => ['/** @var array<int|string> $values */', ['int', 'string'], 'variable'],
    '(int|string)[]' => ['/** @var (int|string)[] $values */', ['int', 'string'], 'variable'],
    'DateTime[]' => ['/** @var DateTime[] $dates */', ['DateTime'], 'variable'],
    'param string[]' => ['/** @param string[] $tags The tags */', ['string'], 'params'],
]);

it('returns empty item types for non-array docblock types', function (): void {
    $docblock = '/** @var string $name The name */';
    $parser = new DocParser($docblock);

    expect($parser->variable()?->itemTypes)->toBe([]);
});

it('handles deprecation with complex docblock', function (): void {
    $docblock = <<<'EOD'
        /**
         * A complex method that does many things
         *
         * @param string|null $name The name parameter
         * @param int $age The age parameter
         * @var array<string, mixed> $data Some data
         * @deprecated This method is deprecated, use newComplexMethod() instead
         * @return array<string, mixed>
         * @throws \InvalidArgumentException When parameters are invalid
         */
        EOD;
    $parser = new DocParser($docblock);

    expect($parser->isDeprecated())->toBeTrue()
        ->and($parser->description())
        ->toBe('A complex method that does many things');

    $params = $parser->params();
    expect($params->get('name')->types)->toBe(['string', 'null'])
        ->and($params->get('age')->types)
        ->toBe(['int']);
});

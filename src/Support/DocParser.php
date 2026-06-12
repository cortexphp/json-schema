<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;

class DocParser
{
    public function __construct(
        protected string $docblock,
    ) {}

    /**
     * Get the description from the docblock.
     */
    public function description(): ?string
    {
        $phpDocNode = $this->parse();
        $textNodes = $this->getTextNodes($phpDocNode->children);

        return $textNodes[0]->text ?? null;
    }

    /**
     * Get the parameters from the docblock.
     *
     * @return \Cortex\JsonSchema\Support\NodeCollection<array-key, \Cortex\JsonSchema\Support\NodeData>
     */
    public function params(): NodeCollection
    {
        $nodes = array_map(
            static fn(ParamTagValueNode|TypelessParamTagValueNode $param): NodeData => self::createNodeData(
                ltrim($param->parameterName, '$'),
                $param->description,
                $param,
            ),
            array_merge(
                $this->parse()->getParamTagValues(),
                $this->parse()->getTypelessParamTagValues(),
            ),
        );

        return new NodeCollection($nodes);
    }

    /**
     * Get the variable from the docblock.
     */
    public function variable(): ?NodeData
    {
        $vars = array_map(
            static fn(VarTagValueNode $varTagValueNode): NodeData => self::createNodeData(
                ltrim($varTagValueNode->variableName, '$'),
                $varTagValueNode->description,
                $varTagValueNode,
            ),
            $this->parse()->getVarTagValues(),
        );

        // There should only be one variable in the docblock.
        return $vars[0] ?? null;
    }

    /**
     * Determine if the docblock is marked as deprecated.
     */
    public function isDeprecated(): bool
    {
        return $this->parse()->getTagsByName('@deprecated') !== [];
    }

    protected static function createNodeData(
        string $name,
        string $description,
        ParamTagValueNode|TypelessParamTagValueNode|VarTagValueNode $tag,
    ): NodeData {
        return new NodeData(
            name: $name,
            description: $description === '' ? null : $description,
            types: self::mapValueNodeToTypes($tag),
            itemTypes: self::mapValueNodeToItemTypes($tag),
        );
    }

    /**
     * Map the value node to its types.
     *
     * @return array<array-key, string>
     */
    protected static function mapValueNodeToTypes(
        ParamTagValueNode|TypelessParamTagValueNode|VarTagValueNode $param,
    ): array {
        if ($param instanceof TypelessParamTagValueNode) {
            return [];
        }

        return match (true) {
            $param->type instanceof UnionTypeNode => array_map(
                static fn(TypeNode $typeNode): string => (string) $typeNode,
                $param->type->types,
            ),
            $param->type instanceof NullableTypeNode => [
                (string) $param->type->type,
                'null',
            ],
            default => [(string) $param->type],
        };
    }

    /**
     * Map the value node to its array element types.
     *
     * @return array<array-key, string>
     */
    protected static function mapValueNodeToItemTypes(
        ParamTagValueNode|TypelessParamTagValueNode|VarTagValueNode $param,
    ): array {
        if ($param instanceof TypelessParamTagValueNode) {
            return [];
        }

        return self::extractItemTypesFromTypeNode($param->type);
    }

    /**
     * Extract array element types from a type node.
     *
     * @return array<array-key, string>
     */
    protected static function extractItemTypesFromTypeNode(TypeNode $typeNode): array
    {
        return match (true) {
            $typeNode instanceof ArrayTypeNode => self::typeNodeToStrings($typeNode->type),
            $typeNode instanceof GenericTypeNode => self::extractGenericArrayItemTypes($typeNode),
            default => [],
        };
    }

    /**
     * Extract element types from generic array/list types.
     *
     * @return array<array-key, string>
     */
    protected static function extractGenericArrayItemTypes(GenericTypeNode $genericTypeNode): array
    {
        $baseName = strtolower($genericTypeNode->type->name);

        if (! in_array($baseName, ['array', 'list', 'iterable', 'non-empty-array', 'non-empty-list'], true)) {
            return [];
        }

        if ($genericTypeNode->genericTypes === []) {
            return [];
        }

        $valueType = $genericTypeNode->genericTypes[array_key_last($genericTypeNode->genericTypes)];

        return self::typeNodeToStrings($valueType);
    }

    /**
     * Resolve a type node to its constituent type strings.
     *
     * @return array<array-key, string>
     */
    protected static function typeNodeToStrings(TypeNode $typeNode): array
    {
        return match (true) {
            $typeNode instanceof UnionTypeNode => array_merge(
                ...array_map(
                    self::typeNodeToStrings(...),
                    $typeNode->types,
                ),
            ),
            $typeNode instanceof NullableTypeNode => [
                ...self::typeNodeToStrings($typeNode->type),
                'null',
            ],
            default => [(string) $typeNode],
        };
    }

    /**
     * Parse the docblock into a PHPStan PhpDocNode.
     */
    protected function parse(): PhpDocNode
    {
        return $this->getParser()->parse(
            new TokenIterator($this->getLexer()->tokenize($this->docblock)),
        );
    }

    protected function getParser(): PhpDocParser
    {
        $parserConfig = $this->getConfig();
        $constExprParser = new ConstExprParser($parserConfig);
        $typeParser = new TypeParser($parserConfig, $constExprParser);

        return new PhpDocParser($parserConfig, $typeParser, $constExprParser);
    }

    protected function getConfig(): ParserConfig
    {
        return new ParserConfig(usedAttributes: []);
    }

    protected function getLexer(): Lexer
    {
        return new Lexer($this->getConfig());
    }

    /**
     * @param array<array-key, \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode> $children
     *
     * @return array<array-key, \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode>
     */
    protected function getTextNodes(array $children): array
    {
        return array_filter(
            $children,
            static fn(PhpDocChildNode $phpDocChildNode): bool => $phpDocChildNode instanceof PhpDocTextNode,
        );
    }
}

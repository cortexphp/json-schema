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
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
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
            static fn(ParamTagValueNode|TypelessParamTagValueNode $param): NodeData => new NodeData(
                name: ltrim($param->parameterName, '$'),
                description: $param->description === '' ? null : $param->description,
                types: self::mapValueNodeToTypes($param),
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
            static fn(VarTagValueNode $varTagValueNode): NodeData => new NodeData(
                name: ltrim($varTagValueNode->variableName, '$'),
                description: $varTagValueNode->description === '' ? null : $varTagValueNode->description,
                types: self::mapValueNodeToTypes($varTagValueNode),
            ),
            $this->parse()->getVarTagValues(),
        );

        // There should only be one variable in the docblock.
        return $vars[0] ?? null;
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
                fn(TypeNode $typeNode): string => (string) $typeNode,
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

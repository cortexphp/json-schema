<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
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
     * @return array<array-key, array{name: string, type: string|null, description: string|null}>
     */
    public function params(): array
    {
        return array_map(
            static fn(ParamTagValueNode|TypelessParamTagValueNode $param): array => [
                'name' => ltrim($param->parameterName, '$'),
                'type' => $param instanceof TypelessParamTagValueNode ? null : (string) $param->type,
                'description' => empty($param->description) ? null : $param->description,
            ],
            array_merge(
                $this->parse()->getParamTagValues(),
                $this->parse()->getTypelessParamTagValues(),
            ),
        );
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
        $config = $this->getConfig();
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);

        return new PhpDocParser($config, $typeParser, $constExprParser);
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
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode[] $children
     *
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode[]
     */
    protected function getTextNodes(array $children): array
    {
        return array_filter(
            $children,
            static fn(PhpDocChildNode $child): bool => $child instanceof PhpDocTextNode,
        );
    }
}

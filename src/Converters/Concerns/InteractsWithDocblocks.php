<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters\Concerns;

use ReflectionEnum;
use ReflectionClass;
use ReflectionProperty;
use ReflectionFunctionAbstract;
use Cortex\JsonSchema\Support\DocParser;
use Cortex\JsonSchema\Contracts\JsonSchema;

trait InteractsWithDocblocks
{
    /**
     * Create a doc parser from a reflector's docblock, if one exists.
     *
     * @param ReflectionClass<object>|ReflectionEnum<\UnitEnum>|ReflectionProperty|ReflectionFunctionAbstract $reflector
     */
    protected function docParser(
        ReflectionClass|ReflectionEnum|ReflectionProperty|ReflectionFunctionAbstract $reflector,
    ): ?DocParser {
        $docComment = $reflector->getDocComment();

        return is_string($docComment)
            ? new DocParser($docComment)
            : null;
    }

    /**
     * Apply deprecated and description metadata from a docblock onto a schema.
     */
    protected function applySchemaDocblock(
        JsonSchema $jsonSchema,
        ?DocParser $docParser,
        bool $deprecated = false,
    ): void {
        if ($deprecated || $docParser?->isDeprecated() === true) {
            $jsonSchema->deprecated();
        }

        $description = $docParser?->description();

        if ($description !== null) {
            $jsonSchema->description($description);
        }
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

class NodeData
{
    /**
     * @param array<array-key, string> $types
     * @param array<array-key, string> $itemTypes
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $types = [],
        public array $itemTypes = [],
    ) {}
}

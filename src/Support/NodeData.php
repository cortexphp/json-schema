<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

class NodeData
{
    /**
     * @param array<int, string> $types
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $types = [],
    ) {}
}

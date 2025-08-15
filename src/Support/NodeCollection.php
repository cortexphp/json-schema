<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

/**
 * @template TKey of array-key
 * @template TValue of NodeData
 */
class NodeCollection
{
    /**
     * @param array<array-key, NodeData> $nodes
     */
    public function __construct(
        public array $nodes,
    ) {}

    public function get(string $name): ?NodeData
    {
        $nodes = array_values(
            array_filter(
                $this->nodes,
                static fn(NodeData $nodeData): bool => $nodeData->name === $name,
            ),
        );

        return $nodes[0] ?? null;
    }
}

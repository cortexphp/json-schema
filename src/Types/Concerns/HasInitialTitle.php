<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasInitialTitle
{
    protected ?string $initialTitle = null;

    /**
     * Get the initial title.
     *
     * @internal
     */
    public function getInitialTitle(): ?string
    {
        return $this->initialTitle;
    }
}

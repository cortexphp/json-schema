<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Contracts;

interface Converter
{
    /**
     * Convert the value to a schema instance.
     */
    public function convert(): Schema;
}

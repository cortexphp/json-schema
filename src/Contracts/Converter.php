<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Contracts;

interface Converter
{
    /**
     * Convert the value to an object schema.
     */
    public function convert(): Schema;
}

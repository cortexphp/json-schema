<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Contracts;

use Cortex\JsonSchema\Types\ObjectSchema;

interface Converter
{
    /**
     * Convert the value to an object schema.
     */
    public function convert(): ObjectSchema;
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFormat;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasFormat
{
    protected SchemaFormat|string|null $format = null;

    /**
     * Set the format
     */
    public function format(SchemaFormat|string $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Add format field to schema array
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addFormatToSchema(array $schema): array
    {
        if ($this->format !== null) {
            $schema['format'] = $this->format instanceof SchemaFormat
                ? $this->format->value
                : $this->format;
        }

        return $schema;
    }
}

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFeature;

/**
 * @mixin \Cortex\JsonSchema\Contracts\JsonSchema
 */
trait HasAnchor
{
    protected ?string $anchor = null;

    /**
     * Set the plain-name anchor for this schema.
     */
    public function anchor(string $anchor): static
    {
        $this->validateFeatureSupport(SchemaFeature::Anchor);

        $this->anchor = $anchor;

        return $this;
    }

    /**
     * Add $anchor to schema array.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    protected function addAnchorToSchema(array $schema): array
    {
        if ($this->anchor !== null) {
            $schema['$anchor'] = $this->anchor;
        }

        return $schema;
    }

    /**
     * Get anchor features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getAnchorFeatures(): array
    {
        if ($this->anchor === null) {
            return [];
        }

        return [SchemaFeature::Anchor];
    }
}

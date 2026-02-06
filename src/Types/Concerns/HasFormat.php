<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Enums\SchemaFeature;

/** @mixin \Cortex\JsonSchema\Contracts\JsonSchema */
trait HasFormat
{
    protected SchemaFormat|string|null $format = null;

    /**
     * Set the format
     */
    public function format(SchemaFormat|string $format): static
    {
        // Validate version-specific formats
        if ($format instanceof SchemaFormat) {
            $this->validateFormatSupport($format);
        }

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

    /**
     * Validate that a format is supported in the current schema version.
     */
    protected function validateFormatSupport(SchemaFormat $schemaFormat): void
    {
        $feature = match ($schemaFormat) {
            SchemaFormat::Duration => SchemaFeature::FormatDuration,
            SchemaFormat::Uuid => SchemaFeature::FormatUuid,
            SchemaFormat::Date => SchemaFeature::FormatDate,
            SchemaFormat::Time => SchemaFeature::FormatTime,
            SchemaFormat::Regex => SchemaFeature::FormatRegex,
            SchemaFormat::RelativeJsonPointer => SchemaFeature::FormatRelativeJsonPointer,
            SchemaFormat::IdnEmail => SchemaFeature::FormatIdnEmail,
            SchemaFormat::IdnHostname => SchemaFeature::FormatIdnHostname,
            SchemaFormat::Iri => SchemaFeature::FormatIri,
            SchemaFormat::IriReference => SchemaFeature::FormatIriReference,
            // All other formats are available in all supported versions
            default => null,
        };

        if ($feature !== null) {
            $this->validateFeatureSupport($feature);
        }
    }

    /**
     * Get format features used by this schema.
     *
     * @return array<\Cortex\JsonSchema\Enums\SchemaFeature>
     */
    protected function getFormatFeatures(): array
    {
        if ($this->format === null || ! ($this->format instanceof SchemaFormat)) {
            return [];
        }

        $features = [];

        switch ($this->format) {
            case SchemaFormat::Date:
                $features[] = SchemaFeature::FormatDate;
                break;
            case SchemaFormat::Time:
                $features[] = SchemaFeature::FormatTime;
                break;
            case SchemaFormat::Regex:
                $features[] = SchemaFeature::FormatRegex;
                break;
            case SchemaFormat::RelativeJsonPointer:
                $features[] = SchemaFeature::FormatRelativeJsonPointer;
                break;
            case SchemaFormat::IdnEmail:
                $features[] = SchemaFeature::FormatIdnEmail;
                break;
            case SchemaFormat::IdnHostname:
                $features[] = SchemaFeature::FormatIdnHostname;
                break;
            case SchemaFormat::Iri:
                $features[] = SchemaFeature::FormatIri;
                break;
            case SchemaFormat::IriReference:
                $features[] = SchemaFeature::FormatIriReference;
                break;
            case SchemaFormat::Duration:
                $features[] = SchemaFeature::FormatDuration;
                break;
            case SchemaFormat::Uuid:
                $features[] = SchemaFeature::FormatUuid;
                break;
                // All other formats are available in all supported versions
        }

        return $features;
    }
}

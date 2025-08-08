<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Enums;

enum SchemaVersion: string
{
    case Draft_07 = 'http://json-schema.org/draft-07/schema#';
    case Draft_2019_09 = 'https://json-schema.org/draft/2019-09/schema';
    case Draft_2020_12 = 'https://json-schema.org/draft/2020-12/schema';

    /**
     * Get the latest version.
     */
    public static function latest(): self
    {
        return self::Draft_2020_12;
    }

    /**
     * Get the default version (most commonly used).
     */
    public static function default(): self
    {
        return self::Draft_2020_12;
    }

    /**
     * Get all supported versions.
     *
     * @return array<self>
     */
    public static function supported(): array
    {
        return [
            self::Draft_07,
            self::Draft_2019_09,
            self::Draft_2020_12,
        ];
    }

    /**
     * Check if this version supports a specific feature.
     */
    public function supports(SchemaFeature $schemaFeature): bool
    {
        $schemaVersion = $schemaFeature->getMinimumVersion();
        $maxVersion = $schemaFeature->getMaximumVersion();

        // Check if this version is at least the minimum required version
        if ($this->getYear() < $schemaVersion->getYear()) {
            return false;
        }

        // Check if this version is not beyond the maximum supported version
        return ! ($maxVersion instanceof self && $this->getYear() > $maxVersion->getYear());
    }

    /**
     * Get a human-readable name for the version.
     */
    public function getName(): string
    {
        return match ($this) {
            self::Draft_07 => 'Draft 7',
            self::Draft_2019_09 => 'Draft 2019-09',
            self::Draft_2020_12 => 'Draft 2020-12',
        };
    }

    /**
     * Get the year this version was published.
     */
    public function getYear(): int
    {
        return match ($this) {
            self::Draft_07 => 2018,
            self::Draft_2019_09 => 2019,
            self::Draft_2020_12 => 2020,
        };
    }
}

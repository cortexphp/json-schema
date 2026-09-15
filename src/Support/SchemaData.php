<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Support;

/**
 * Typed accessors over a raw JSON Schema data array.
 */
final class SchemaData
{
    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        private array $data,
    ) {}

    /**
     * Determine whether a key exists in the data.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get the full data array.
     *
     * @return array<int|string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Safely get a string value from the data array.
     */
    public function getString(string $key): ?string
    {
        return isset($this->data[$key]) && is_string($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Safely get an integer value from the data array.
     */
    public function getInt(string $key): ?int
    {
        return isset($this->data[$key]) && is_numeric($this->data[$key]) ? (int) $this->data[$key] : null;
    }

    /**
     * Safely get a float value from the data array.
     */
    public function getFloat(string $key): ?float
    {
        return isset($this->data[$key]) && is_numeric($this->data[$key]) ? (float) $this->data[$key] : null;
    }

    /**
     * Safely get a boolean value from the data array.
     */
    public function getBool(string $key): bool
    {
        return isset($this->data[$key]) && (bool) $this->data[$key];
    }

    /**
     * Safely get an array value from the data array.
     *
     * @return array<int|string, mixed>|null
     */
    public function getArray(string $key): ?array
    {
        return isset($this->data[$key]) && is_array($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Get a mixed value from the data array.
     */
    public function getValue(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Determine whether any of the given keys are present.
     *
     * @param list<string> $keys
     */
    public function hasAny(array $keys): bool
    {
        return array_intersect_key($this->data, array_flip($keys)) !== [];
    }
}

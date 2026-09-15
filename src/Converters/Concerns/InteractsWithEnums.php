<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Converters\Concerns;

use BackedEnum;
use ReflectionEnum;
use ReflectionType;
use ReflectionNamedType;
use Cortex\JsonSchema\Contracts\JsonSchema;

trait InteractsWithEnums
{
    /**
     * Resolve the backed enum case values for a given class name.
     *
     * @return non-empty-array<int, string|int>|null
     */
    protected function backedEnumValues(string $className): ?array
    {
        if (! enum_exists($className)) {
            return null;
        }

        $reflectionEnum = new ReflectionEnum($className);

        if (! $reflectionEnum->isBacked()) {
            return null;
        }

        /** @var non-empty-array<int, string|int> $values */
        $values = array_column($className::cases(), 'value');

        return $values;
    }

    /**
     * Apply backed enum values to a schema when the reflection type is a backed enum.
     */
    protected function applyBackedEnumValues(JsonSchema $jsonSchema, ?ReflectionType $reflectionType): void
    {
        if (! $reflectionType instanceof ReflectionNamedType) {
            return;
        }

        $values = $this->backedEnumValues($reflectionType->getName());

        if ($values !== null) {
            $jsonSchema->enum($values);
        }
    }

    /**
     * Unwrap a backed enum to its scalar value, leaving other values untouched.
     */
    protected function unwrapEnumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}

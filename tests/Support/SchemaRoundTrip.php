<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Support;

use PHPUnit\Framework\ExpectationFailedException;

final class SchemaRoundTrip
{
    /**
     * Keywords excluded from round-trip subset comparison.
     *
     * @var list<string>
     */
    private const array IGNORED_KEYS = ['$schema'];

    /**
     * Assert every keyword from the source schema is captured in the converted output.
     *
     * @param array<string, mixed> $source
     * @param array<string, mixed> $output
     */
    public static function assertSourceSubset(array $source, array $output, string $path = 'root'): void
    {
        foreach ($source as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS, strict: true)) {
                continue;
            }

            $currentPath = $path === 'root' ? (string) $key : $path . '.' . $key;

            if (! array_key_exists($key, $output)) {
                throw new ExpectationFailedException(
                    sprintf('Expected output to contain key [%s] from source at path [%s].', $key, $currentPath),
                );
            }

            $outputValue = $output[$key];

            if (is_array($value) && is_array($outputValue)) {
                if (array_is_list($value)) {
                    if (! array_is_list($outputValue)) {
                        throw new ExpectationFailedException(
                            sprintf('Expected list at path [%s], got associative array.', $currentPath),
                        );
                    }

                    if (count($value) !== count($outputValue)) {
                        throw new ExpectationFailedException(
                            sprintf(
                                'Expected list length %d at path [%s], got %d.',
                                count($value),
                                $currentPath,
                                count($outputValue),
                            ),
                        );
                    }

                    foreach ($value as $index => $item) {
                        if (is_array($item)) {
                            if (! is_array($outputValue[$index] ?? null)) {
                                throw new ExpectationFailedException(
                                    sprintf('Expected array at path [%s][%d].', $currentPath, $index),
                                );
                            }

                            self::assertSourceSubset($item, $outputValue[$index], $currentPath . '[' . $index . ']');
                        } elseif ($item !== ($outputValue[$index] ?? null)) {
                            throw new ExpectationFailedException(
                                sprintf(
                                    'Expected value %s at path [%s][%d], got %s.',
                                    json_encode($item),
                                    $currentPath,
                                    $index,
                                    json_encode($outputValue[$index] ?? null),
                                ),
                            );
                        }
                    }

                    continue;
                }

                self::assertSourceSubset($value, $outputValue, $currentPath);

                continue;
            }

            if ($value !== $outputValue && ! self::areNumericEqual($value, $outputValue)) {
                throw new ExpectationFailedException(
                    sprintf(
                        'Expected value %s at path [%s], got %s.',
                        json_encode($value),
                        $currentPath,
                        json_encode($outputValue),
                    ),
                );
            }
        }
    }

    /**
     * Check whether two values are numerically equal across int/float boundaries.
     */
    private static function areNumericEqual(mixed $a, mixed $b): bool
    {
        return (is_int($a) && is_float($b) && $a === (int) $b)
            || (is_float($a) && is_int($b) && $a === (float) $b);
    }
}

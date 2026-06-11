<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use DirectoryIterator;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Tests\Support\SchemaRoundTrip;

/**
 * @return array<string, array{0: string}>
 */
function jsonSchemaOrgFixtures(): array
{
    $fixtures = [];
    $basePath = __DIR__ . '/../../Fixtures/json-schema-org';

    foreach (['misc', 'examples'] as $group) {
        $directory = $basePath . '/' . $group;

        if (! is_dir($directory)) {
            continue;
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }

            if ($file->getExtension() !== 'json') {
                continue;
            }

            $name = $group . '/' . $file->getBasename('.json');
            $fixtures[$name] = [$file->getPathname()];
        }
    }

    ksort($fixtures);

    return $fixtures;
}

dataset('json schema org fixtures', jsonSchemaOrgFixtures());

it('round-trips json-schema.org fixtures', function (string $fixturePath): void {
    $sourceJson = file_get_contents($fixturePath);

    expect($sourceJson)->not->toBeFalse();

    /** @var array<string, mixed> $source */
    $source = json_decode($sourceJson, true, flags: JSON_THROW_ON_ERROR);

    $jsonSchema = Schema::fromJson($source);
    $output = $jsonSchema->toArray(includeSchemaRef: false);

    SchemaRoundTrip::assertSourceSubset($source, $output);
})->with('json schema org fixtures');

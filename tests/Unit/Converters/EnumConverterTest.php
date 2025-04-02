<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Converters\EnumConverter;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(EnumConverter::class);

it('can create a schema from an string backed enum', function (): void {
    /** This is the description of the string backed enum */
    enum PostStatus: string
    {
        case Draft = 'draft';
        case Published = 'published';
        case Archived = 'archived';
    }

    $schema = (new EnumConverter(PostStatus::class))->convert();

    expect($schema)->toBeInstanceOf(StringSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'string',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'title' => 'PostStatus',
        'description' => 'This is the description of the string backed enum',
        'enum' => ['draft', 'published', 'archived'],
    ]);
});

it('can create a schema from an integer backed enum', function (): void {
    /** This is the description of the integer backed enum */
    enum PostType: int
    {
        case Article = 1;
        case News = 2;
        case Tutorial = 3;
    }

    $schema = (new EnumConverter(PostType::class))->convert();

    expect($schema)->toBeInstanceOf(IntegerSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'integer',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'title' => 'PostType',
        'description' => 'This is the description of the integer backed enum',
        'enum' => [1, 2, 3],
    ]);
});

it('throws an exception if the enum is not a backed enum', function (): void {
    enum UserStatusNotBacked
    {
        case Active;
        case Inactive;
        case Pending;
    }

    new EnumConverter(UserStatusNotBacked::class);
})->throws(SchemaException::class);

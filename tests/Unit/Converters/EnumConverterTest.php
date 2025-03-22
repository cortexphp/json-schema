<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Converters\EnumConverter;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a schema from an enum', function (): void {
    /** This is the description of the enum */
    enum PostStatus: string
    {
        case Draft = 'draft';
        case Published = 'published';
        case Archived = 'archived';
    }

    $schema = (new EnumConverter(PostStatus::class))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'description' => 'This is the description of the enum',
        'properties' => [
            'PostStatus' => [
                'type' => 'string',
                'enum' => ['draft', 'published', 'archived'],
            ],
        ],
        'required' => [
            'PostStatus',
        ],
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

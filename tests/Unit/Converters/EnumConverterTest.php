<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Converters\EnumConverter;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a schema from an enum', function (): void {
    /** This is the description of the enum */
    enum UserStatus: string
    {
        case Active = 'active';
        case Inactive = 'inactive';
        case Pending = 'pending';
    }

    $schema = (new EnumConverter(UserStatus::class))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'description' => 'This is the description of the enum',
        'properties' => [
            'UserStatus' => [
                'type' => 'string',
                'enum' => ['active', 'inactive', 'pending'],
            ],
        ],
        'required' => [
            'UserStatus',
        ],
    ]);
});

it('throws an exception if the enum is not a backed enum', function (): void {
    enum UserStatus
    {
        case Active;
        case Inactive;
        case Pending;
    }

    (new EnumConverter(UserStatus::class))->convert();
})->throws(SchemaException::class);

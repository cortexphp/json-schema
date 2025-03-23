<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Converters\ClassConverter;

covers(ClassConverter::class);

it('can create a schema from a class', function (): void {
    $schema = (new ClassConverter(new class () {
        public string $name;

        public ?int $age = null;

        public float $height = 1.7;
    }))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'age' => [
                'type' => [
                    'integer',
                    'null',
                ],
                'default' => null,
            ],
            'height' => [
                'type' => 'number',
                'default' => 1.7,
            ],
        ],
        'required' => [
            'name',
        ],
    ]);
});

it('can create a schema from a class with docblocks', function (): void {
    $schema = (new ClassConverter(new class () {
        /**
         * @var string The name of the user
         */
        public string $name;

        /**
         * @var ?int The age of the user
         */
        public ?int $age = null;

        /**
         * @var float The height of the user in meters
         */
        public float $height = 1.7;
    }))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => 'The name of the user',
            ],
            'age' => [
                'type' => [
                    'integer',
                    'null',
                ],
                'description' => 'The age of the user',
                'default' => null,
            ],
            'height' => [
                'type' => 'number',
                'description' => 'The height of the user in meters',
                'default' => 1.7,
            ],
        ],
        'required' => [
            'name',
        ],
    ]);
});

it('can create a schema from a class with constructor property promotion', function (): void {
    /** This is the description of the class */
    $class = new class ('John Doe') {
        /**
         * This is the description of the constructor
         */
        public function __construct(
            public string $name,
            public int $age = 20,
        ) {}
    };

    $schema = (new ClassConverter($class))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'description' => 'This is the description of the class',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'age' => [
                'type' => 'integer',
            ],
        ],
        'required' => [
            'name',
            'age',
        ],
    ]);
});

it('can create a schema from a class with an enum', function (): void {
    enum UserStatus: string
    {
        case Active = 'active';
        case Inactive = 'inactive';
        case Pending = 'pending';
    }

    $schema = (new ClassConverter(new class () {
        public string $name;

        public UserStatus $status = UserStatus::Pending;
    }))->convert();

    expect($schema)->toBeInstanceOf(ObjectSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['active', 'inactive', 'pending'],
                'default' => 'pending',
            ],
        ],
        'required' => [
            'name',
        ],
    ]);
});

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Converters;

use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Converters\ClassConverter;

covers(ClassConverter::class);

it('can create a schema from a class', function (): void {
    $objectSchema = (new ClassConverter(new class () {
        public string $name;

        public ?int $age = null;

        public float $height = 1.7;
    }))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
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
    $objectSchema = (new ClassConverter(new class () {
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

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

    $objectSchema = (new ClassConverter($class))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

    $objectSchema = (new ClassConverter(new class () {
        public string $name;

        public UserStatus $status = UserStatus::Pending;
    }))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

it('can create a schema from a deprecated class', function (): void {
    /**
     * A deprecated user model class
     *
     * @deprecated Use UserV2 instead since v2.0
     */
    $class = new class () {
        /**
         * @var string The user's name
         */
        public string $name;

        /**
         * @var int The user's age
         */
        public int $age;

        /**
         * @var ?string The user's email address
         */
        public ?string $email = null;
    };

    $objectSchema = (new ClassConverter($class))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'description' => 'A deprecated user model class',
        'deprecated' => true,
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => "The user's name",
            ],
            'age' => [
                'type' => 'integer',
                'description' => "The user's age",
            ],
            'email' => [
                'type' => [
                    'string',
                    'null',
                ],
                'description' => "The user's email address",
                'default' => null,
            ],
        ],
        'required' => [
            'name',
            'age',
        ],
    ]);
});

it('can create a schema from a class with deprecated properties', function (): void {
    $class = new class () {
        /**
         * @var string The user's name
         */
        public string $name;

        /**
         * @var int The user's age
         *
         * @deprecated Use birthDate instead
         */
        public int $age;

        /**
         * @var ?string The user's email address
         *
         * @deprecated Email is no longer supported
         */
        public ?string $email = null;

        /**
         * @var string The user's birth date
         */
        public string $birthDate;
    };

    $objectSchema = (new ClassConverter($class))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => "The user's name",
            ],
            'age' => [
                'type' => 'integer',
                'description' => "The user's age",
                'deprecated' => true,
            ],
            'email' => [
                'type' => [
                    'string',
                    'null',
                ],
                'description' => "The user's email address",
                'default' => null,
                'deprecated' => true,
            ],
            'birthDate' => [
                'type' => 'string',
                'description' => "The user's birth date",
            ],
        ],
        'required' => [
            'name',
            'age',
            'birthDate',
        ],
    ]);
});

it('can create a schema from a deprecated class with deprecated properties', function (): void {
    /**
     * Legacy user model
     *
     * @deprecated This entire class is deprecated, use UserV3 instead
     */
    $class = new class () {
        /**
         * @var string The user's name
         */
        public string $name;

        /**
         * @var int The user's age
         *
         * @deprecated Age property is deprecated within this deprecated class
         */
        public int $age;
    };

    $objectSchema = (new ClassConverter($class))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'description' => 'Legacy user model',
        'deprecated' => true,
        'properties' => [
            'name' => [
                'type' => 'string',
                'description' => "The user's name",
            ],
            'age' => [
                'type' => 'integer',
                'description' => "The user's age",
                'deprecated' => true,
            ],
        ],
        'required' => [
            'name',
            'age',
        ],
    ]);
});

it('can create a schema from a deprecated class without description', function (): void {
    /**
     * @deprecated
     */
    $class = new class () {
        public string $name;

        public int $age;
    };

    $objectSchema = (new ClassConverter($class))->convert();

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'deprecated' => true,
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

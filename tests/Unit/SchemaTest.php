<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use stdClass;
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaType;
use Cortex\JsonSchema\Types\NullSchema;
use Cortex\JsonSchema\Types\ArraySchema;
use Cortex\JsonSchema\Types\UnionSchema;
use Cortex\JsonSchema\Types\NumberSchema;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Types\StringSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Types\BooleanSchema;
use Cortex\JsonSchema\Types\IntegerSchema;
use Cortex\JsonSchema\Contracts\JsonSchema;
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(Schema::class);

it('can create different schema types', function (): void {
    // Test array schema creation
    expect(Schema::array('items'))->toBeInstanceOf(ArraySchema::class);

    // Test boolean schema creation
    expect(Schema::boolean('active'))->toBeInstanceOf(BooleanSchema::class);

    // Test integer schema creation
    expect(Schema::integer('count'))->toBeInstanceOf(IntegerSchema::class);

    // Test null schema creation
    expect(Schema::null('deleted_at'))->toBeInstanceOf(NullSchema::class);

    // Test number schema creation
    expect(Schema::number('price'))->toBeInstanceOf(NumberSchema::class);

    // Test object schema creation
    expect(Schema::object('user'))->toBeInstanceOf(ObjectSchema::class);

    // Test string schema creation
    expect(Schema::string('name'))->toBeInstanceOf(StringSchema::class);

    // Test union schema creation
    expect(Schema::union([SchemaType::String, SchemaType::Integer]))->toBeInstanceOf(UnionSchema::class);

    // Test mixed schema creation
    expect(Schema::mixed())->toBeInstanceOf(UnionSchema::class);
});

it('can create schemas with default metadata', function (): void {
    $stringSchema = Schema::string('title')
        ->description('Description')
        ->readOnly()
        ->writeOnly();

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
    expect($schemaArray)->toHaveKey('title', 'title');
    expect($schemaArray)->toHaveKey('description', 'Description');
    expect($schemaArray)->toHaveKey('readOnly', true);
    expect($schemaArray)->toHaveKey('writeOnly', true);
});

it('can create a schema from a closure', function (): void {
    $closure = function (string $name, array $fooArray, ?int $age = null): void {};
    $objectSchema = Schema::fromClosure($closure);

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'properties' => [
            'name' => [
                'type' => 'string',
            ],
            'fooArray' => [
                'type' => 'array',
            ],
            'age' => [
                'type' => [
                    'integer',
                    'null',
                ],
                'default' => null,
            ],
        ],
        'required' => [
            'name',
            'fooArray',
        ],
    ]);

    expect($objectSchema->toJson())->toBe(json_encode($objectSchema->toArray()));

    // Assert that the from method behaves in the same way as the fromClosure method
    expect(Schema::from($closure))->toEqual($objectSchema);
});

it('can create a schema from a class', function (): void {
    /** This is the description of the class */
    $class = new class ('John Doe') {
        public function __construct(
            public string $name,
            public int $age = 20,
            protected ?string $email = null,
        ) {}
    };

    $objectSchema = Schema::fromClass($class, publicOnly: true);

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

    // Assert that the from method behaves in the same way as the fromClass method
    expect(Schema::from($class))->toEqual($objectSchema);
});

it('can create a schema from an enum', function (): void {
    /** This is a custom enum for testing */
    enum UserRole: string
    {
        case Admin = 'admin';
        case Editor = 'editor';
        case Viewer = 'viewer';
        case Guest = 'guest';
    }

    $schema = Schema::fromEnum(UserRole::class);

    expect($schema)->toBeInstanceOf(StringSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'string',
        '$schema' => 'https://json-schema.org/draft/2020-12/schema',
        'title' => 'UserRole',
        'description' => 'This is a custom enum for testing',
        'enum' => ['admin', 'editor', 'viewer', 'guest'],
    ]);

    // Assert that the from method behaves in the same way as the fromEnum method
    expect(Schema::from(UserRole::class))->toEqual($schema);
});

it('handles schema version overrides correctly', function (): void {
    // Test the CoalesceRemoveLeft mutations by providing explicit versions
    $customVersion = SchemaVersion::Draft_2019_09;

    $stringSchema = Schema::string('test', $customVersion);
    expect($stringSchema->getVersion())->toBe($customVersion);

    $objectSchema = Schema::object('test', $customVersion);
    expect($objectSchema->getVersion())->toBe($customVersion);

    $arraySchema = Schema::array('test', $customVersion);
    expect($arraySchema->getVersion())->toBe($customVersion);

    $numberSchema = Schema::number('test', $customVersion);
    expect($numberSchema->getVersion())->toBe($customVersion);

    $integerSchema = Schema::integer('test', $customVersion);
    expect($integerSchema->getVersion())->toBe($customVersion);

    $booleanSchema = Schema::boolean('test', $customVersion);
    expect($booleanSchema->getVersion())->toBe($customVersion);

    $nullSchema = Schema::null('test', $customVersion);
    expect($nullSchema->getVersion())->toBe($customVersion);

    $unionSchema = Schema::union([SchemaType::String], 'test', $customVersion);
    expect($unionSchema->getVersion())->toBe($customVersion);

    $mixedSchema = Schema::mixed('test', $customVersion);
    expect($mixedSchema->getVersion())->toBe($customVersion);
});

it('handles fromClass publicOnly parameter correctly', function (): void {
    // Test the TrueToFalse mutation on line 117
    $testClass = new class () {
        public string $publicProp = 'public';

        protected string $protectedProp = 'protected';
    };

    // Test with publicOnly = true (default)
    $objectSchema = Schema::fromClass($testClass, true);
    $publicOnlyArray = $objectSchema->toArray();
    expect($publicOnlyArray['properties'])->toHaveKey('publicProp');
    expect($publicOnlyArray['properties'])->not->toHaveKey('protectedProp');

    // Test with publicOnly = false
    $allPropsSchema = Schema::fromClass($testClass, false);
    $allPropsArray = $allPropsSchema->toArray();
    expect($allPropsArray['properties'])->toHaveKey('publicProp');
    expect($allPropsArray['properties'])->toHaveKey('protectedProp');
});

it('tests from method with various input types', function (): void {
    // Test different branches of the from method

    // Test Closure
    $closure = fn(string $name): null => null;
    expect(Schema::from($closure))->toBeInstanceOf(ObjectSchema::class);

    // Test enum class string
    enum StringTestEnum: string
    {
        case Test = 'test';
    }
    expect(Schema::from(StringTestEnum::class))->toBeInstanceOf(StringSchema::class);

    // Test regular class
    expect(Schema::from(stdClass::class))->toBeInstanceOf(ObjectSchema::class);

    // Test object instance
    expect(Schema::from(new stdClass()))->toBeInstanceOf(ObjectSchema::class);

    // Test array (JSON)
    expect(Schema::from([
        'type' => 'string',
    ]))->toBeInstanceOf(StringSchema::class);

    // Test JSON string
    expect(Schema::from('{"type": "boolean"}'))->toBeInstanceOf(BooleanSchema::class);

    // Test unsupported type
    expect(fn(): JsonSchema => Schema::from(42))
        ->toThrow(SchemaException::class, 'Unsupported value type');
});

it('tests version parameter handling in conversion methods', function (): void {
    // Test CoalesceRemoveLeft mutations in conversion methods
    $customVersion = SchemaVersion::Draft_2020_12;

    $closure = fn(string $name): null => null;
    $objectSchema = Schema::fromClosure($closure, $customVersion);
    expect($objectSchema->getVersion())->toBe($customVersion);

    enum IntTestEnum: int
    {
        case One = 1;
    }
    $enumSchema = Schema::fromEnum(IntTestEnum::class, $customVersion);
    expect($enumSchema->getVersion())->toBe($customVersion);

    $classSchema = Schema::fromClass(stdClass::class, true, $customVersion);
    expect($classSchema->getVersion())->toBe($customVersion);

    $jsonSchema = Schema::fromJson('{"type": "number"}', $customVersion);
    expect($jsonSchema->getVersion())->toBe($customVersion);

    // Test version parameter in from method
    $fromSchema = Schema::from($closure, $customVersion);
    expect($fromSchema->getVersion())->toBe($customVersion);
});

it('tests default version handling', function (): void {
    // Test default version behavior
    expect(Schema::getDefaultVersion())->toBe(SchemaVersion::Draft_2020_12);

    // Test setting custom default
    Schema::setDefaultVersion(SchemaVersion::Draft_2019_09);
    expect(Schema::getDefaultVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Test that schemas use the default when no version specified
    $stringSchema = Schema::string('test');
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Reset to original default
    Schema::resetDefaultVersion();
    expect(Schema::getDefaultVersion())->toBe(SchemaVersion::Draft_2020_12);
});

it('tests specific enum boolean logic edge case', function (): void {
    // Test with a string that exists as a class but is not an enum
    expect(Schema::from('DateTime'))->toBeInstanceOf(ObjectSchema::class);

    expect(fn(): JsonSchema => Schema::from('NonExistentClass'))
        ->toThrow(SchemaException::class, 'Unsupported value type');

    expect(fn(): JsonSchema => Schema::from('invalid json'))
        ->toThrow(SchemaException::class, 'Unsupported value type');
});

it('tests fromClass default parameter behavior', function (): void {
    // Test the default publicOnly
    $testClass = new class () {
        public string $publicProp = 'public';

        private string $privateProp = 'private';

        protected function getPrivateProp(): string
        {
            return $this->privateProp;
        }
    };

    // Test that the default behavior (not specifying publicOnly) is the same as publicOnly = true
    $objectSchema = Schema::fromClass($testClass);
    $explicitTrueSchema = Schema::fromClass($testClass, true);

    expect($objectSchema->toArray())->toBe($explicitTrueSchema->toArray());

    // Verify that only public properties are included by default
    $schemaArray = $objectSchema->toArray();
    expect($schemaArray['properties'])->toHaveKey('publicProp');
    expect($schemaArray['properties'])->not->toHaveKey('privateProp');

    // Test that explicitly setting false gives different results
    $falseSchema = Schema::fromClass($testClass, false);
    $falseSchemaArray = $falseSchema->toArray();
    expect($falseSchemaArray['properties'])->toHaveKey('publicProp');
    expect($falseSchemaArray['properties'])->toHaveKey('privateProp');
    expect($falseSchema->toArray())->not->toBe($objectSchema->toArray());
});

it('tests enum type checking edge case', function (): void {
    // Test with something that exists and is a subclass of BackedEnum but the logic is wrong
    enum EdgeCaseEnum: string
    {
        case Test = 'test';
    }

    // This should work normally
    $jsonSchema = Schema::from(EdgeCaseEnum::class);
    expect($jsonSchema)->toBeInstanceOf(StringSchema::class);

    // Test with a regular class string to ensure it goes to the class branch
    $classResult = Schema::from('stdClass');
    expect($classResult)->toBeInstanceOf(ObjectSchema::class);

    // Test edge case: What if we have a string that would pass enum_exists but fail is_subclass_of?
    // This exercises the boolean logic more thoroughly
    expect(fn(): JsonSchema => Schema::from('NotAnEnum'))
        ->toThrow(SchemaException::class, 'Unsupported value type');
});

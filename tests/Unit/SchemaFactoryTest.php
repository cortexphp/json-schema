<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use stdClass;
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Contracts\Schema;
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
use Cortex\JsonSchema\Exceptions\SchemaException;

covers(SchemaFactory::class);

it('can create different schema types', function (): void {
    // Test array schema creation
    expect(SchemaFactory::array('items'))->toBeInstanceOf(ArraySchema::class);

    // Test boolean schema creation
    expect(SchemaFactory::boolean('active'))->toBeInstanceOf(BooleanSchema::class);

    // Test integer schema creation
    expect(SchemaFactory::integer('count'))->toBeInstanceOf(IntegerSchema::class);

    // Test null schema creation
    expect(SchemaFactory::null('deleted_at'))->toBeInstanceOf(NullSchema::class);

    // Test number schema creation
    expect(SchemaFactory::number('price'))->toBeInstanceOf(NumberSchema::class);

    // Test object schema creation
    expect(SchemaFactory::object('user'))->toBeInstanceOf(ObjectSchema::class);

    // Test string schema creation
    expect(SchemaFactory::string('name'))->toBeInstanceOf(StringSchema::class);

    // Test union schema creation
    expect(SchemaFactory::union([SchemaType::String, SchemaType::Integer]))->toBeInstanceOf(UnionSchema::class);

    // Test mixed schema creation
    expect(SchemaFactory::mixed())->toBeInstanceOf(UnionSchema::class);
});

it('can create schemas with default metadata', function (): void {
    $stringSchema = SchemaFactory::string('title')
        ->description('Description')
        ->readOnly()
        ->writeOnly();

    $schemaArray = $stringSchema->toArray();

    expect($schemaArray)->toHaveKey('$schema', 'http://json-schema.org/draft-07/schema#');
    expect($schemaArray)->toHaveKey('title', 'title');
    expect($schemaArray)->toHaveKey('description', 'Description');
    expect($schemaArray)->toHaveKey('readOnly', true);
    expect($schemaArray)->toHaveKey('writeOnly', true);
});

it('can create a schema from a closure', function (): void {
    $closure = function (string $name, array $fooArray, ?int $age = null): void {};
    $objectSchema = SchemaFactory::fromClosure($closure);

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
        'type' => 'object',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
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
    expect(SchemaFactory::from($closure))->toEqual($objectSchema);
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

    $objectSchema = SchemaFactory::fromClass($class, publicOnly: true);

    expect($objectSchema)->toBeInstanceOf(ObjectSchema::class);
    expect($objectSchema->toArray())->toBe([
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

    // Assert that the from method behaves in the same way as the fromClass method
    expect(SchemaFactory::from($class))->toEqual($objectSchema);
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

    $schema = SchemaFactory::fromEnum(UserRole::class);

    expect($schema)->toBeInstanceOf(StringSchema::class);
    expect($schema->toArray())->toBe([
        'type' => 'string',
        '$schema' => 'http://json-schema.org/draft-07/schema#',
        'title' => 'UserRole',
        'description' => 'This is a custom enum for testing',
        'enum' => ['admin', 'editor', 'viewer', 'guest'],
    ]);

    // Assert that the from method behaves in the same way as the fromEnum method
    expect(SchemaFactory::from(UserRole::class))->toEqual($schema);
});

it('handles schema version overrides correctly', function (): void {
    // Test the CoalesceRemoveLeft mutations by providing explicit versions
    $customVersion = SchemaVersion::Draft_2019_09;

    $stringSchema = SchemaFactory::string('test', $customVersion);
    expect($stringSchema->getVersion())->toBe($customVersion);

    $objectSchema = SchemaFactory::object('test', $customVersion);
    expect($objectSchema->getVersion())->toBe($customVersion);

    $arraySchema = SchemaFactory::array('test', $customVersion);
    expect($arraySchema->getVersion())->toBe($customVersion);

    $numberSchema = SchemaFactory::number('test', $customVersion);
    expect($numberSchema->getVersion())->toBe($customVersion);

    $integerSchema = SchemaFactory::integer('test', $customVersion);
    expect($integerSchema->getVersion())->toBe($customVersion);

    $booleanSchema = SchemaFactory::boolean('test', $customVersion);
    expect($booleanSchema->getVersion())->toBe($customVersion);

    $nullSchema = SchemaFactory::null('test', $customVersion);
    expect($nullSchema->getVersion())->toBe($customVersion);

    $unionSchema = SchemaFactory::union([SchemaType::String], 'test', $customVersion);
    expect($unionSchema->getVersion())->toBe($customVersion);

    $mixedSchema = SchemaFactory::mixed('test', $customVersion);
    expect($mixedSchema->getVersion())->toBe($customVersion);
});

it('handles fromClass publicOnly parameter correctly', function (): void {
    // Test the TrueToFalse mutation on line 117
    $testClass = new class () {
        public string $publicProp = 'public';

        protected string $protectedProp = 'protected';
    };

    // Test with publicOnly = true (default)
    $objectSchema = SchemaFactory::fromClass($testClass, true);
    $publicOnlyArray = $objectSchema->toArray();
    expect($publicOnlyArray['properties'])->toHaveKey('publicProp');
    expect($publicOnlyArray['properties'])->not->toHaveKey('protectedProp');

    // Test with publicOnly = false
    $allPropsSchema = SchemaFactory::fromClass($testClass, false);
    $allPropsArray = $allPropsSchema->toArray();
    expect($allPropsArray['properties'])->toHaveKey('publicProp');
    expect($allPropsArray['properties'])->toHaveKey('protectedProp');
});

it('tests from method with various input types', function (): void {
    // Test different branches of the from method

    // Test Closure
    $closure = fn(string $name): null => null;
    expect(SchemaFactory::from($closure))->toBeInstanceOf(ObjectSchema::class);

    // Test enum class string
    enum StringTestEnum: string
    {
        case Test = 'test';
    }
    expect(SchemaFactory::from(StringTestEnum::class))->toBeInstanceOf(StringSchema::class);

    // Test regular class
    expect(SchemaFactory::from(stdClass::class))->toBeInstanceOf(ObjectSchema::class);

    // Test object instance
    expect(SchemaFactory::from(new stdClass()))->toBeInstanceOf(ObjectSchema::class);

    // Test array (JSON)
    expect(SchemaFactory::from([
        'type' => 'string',
    ]))->toBeInstanceOf(StringSchema::class);

    // Test JSON string
    expect(SchemaFactory::from('{"type": "boolean"}'))->toBeInstanceOf(BooleanSchema::class);

    // Test unsupported type
    expect(fn(): Schema => SchemaFactory::from(42))
        ->toThrow(SchemaException::class, 'Unsupported value type');
});

it('tests version parameter handling in conversion methods', function (): void {
    // Test CoalesceRemoveLeft mutations in conversion methods
    $customVersion = SchemaVersion::Draft_2020_12;

    $closure = fn(string $name): null => null;
    $objectSchema = SchemaFactory::fromClosure($closure, $customVersion);
    expect($objectSchema->getVersion())->toBe($customVersion);

    enum IntTestEnum: int
    {
        case One = 1;
    }
    $enumSchema = SchemaFactory::fromEnum(IntTestEnum::class, $customVersion);
    expect($enumSchema->getVersion())->toBe($customVersion);

    $classSchema = SchemaFactory::fromClass(stdClass::class, true, $customVersion);
    expect($classSchema->getVersion())->toBe($customVersion);

    $jsonSchema = SchemaFactory::fromJson('{"type": "number"}', $customVersion);
    expect($jsonSchema->getVersion())->toBe($customVersion);

    // Test version parameter in from method
    $fromSchema = SchemaFactory::from($closure, $customVersion);
    expect($fromSchema->getVersion())->toBe($customVersion);
});

it('tests default version handling', function (): void {
    // Test default version behavior
    expect(SchemaFactory::getDefaultVersion())->toBe(SchemaVersion::Draft_07);

    // Test setting custom default
    SchemaFactory::setDefaultVersion(SchemaVersion::Draft_2019_09);
    expect(SchemaFactory::getDefaultVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Test that schemas use the default when no version specified
    $stringSchema = SchemaFactory::string('test');
    expect($stringSchema->getVersion())->toBe(SchemaVersion::Draft_2019_09);

    // Reset to original default
    SchemaFactory::resetDefaultVersion();
    expect(SchemaFactory::getDefaultVersion())->toBe(SchemaVersion::Draft_07);
});

it('tests specific enum boolean logic edge case', function (): void {
    // Test with a string that exists as a class but is not an enum
    expect(SchemaFactory::from('DateTime'))->toBeInstanceOf(ObjectSchema::class);

    expect(fn(): Schema => SchemaFactory::from('NonExistentClass'))
        ->toThrow(SchemaException::class, 'Unsupported value type');

    expect(fn(): Schema => SchemaFactory::from('invalid json'))
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
    $objectSchema = SchemaFactory::fromClass($testClass);
    $explicitTrueSchema = SchemaFactory::fromClass($testClass, true);

    expect($objectSchema->toArray())->toBe($explicitTrueSchema->toArray());

    // Verify that only public properties are included by default
    $schemaArray = $objectSchema->toArray();
    expect($schemaArray['properties'])->toHaveKey('publicProp');
    expect($schemaArray['properties'])->not->toHaveKey('privateProp');

    // Test that explicitly setting false gives different results
    $falseSchema = SchemaFactory::fromClass($testClass, false);
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
    $schema = SchemaFactory::from(EdgeCaseEnum::class);
    expect($schema)->toBeInstanceOf(StringSchema::class);

    // Test with a regular class string to ensure it goes to the class branch
    $classResult = SchemaFactory::from('stdClass');
    expect($classResult)->toBeInstanceOf(ObjectSchema::class);

    // Test edge case: What if we have a string that would pass enum_exists but fail is_subclass_of?
    // This exercises the boolean logic more thoroughly
    expect(fn(): Schema => SchemaFactory::from('NotAnEnum'))
        ->toThrow(SchemaException::class, 'Unsupported value type');
});

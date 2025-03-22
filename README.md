![banner](https://github.com/user-attachments/assets/ec631b0d-103b-46c4-864e-d5b85eb17597)

# Fluently build and validate JSON Schemas

[![Latest Version](https://img.shields.io/packagist/v/cortexphp/json-schema.svg?style=flat-square&logo=composer)](https://packagist.org/packages/cortexphp/json-schema)
![GitHub Actions Test Workflow Status](https://img.shields.io/github/actions/workflow/status/cortexphp/json-schema/run-tests.yml?style=flat-square&logo=github)
![GitHub License](https://img.shields.io/github/license/cortexphp/json-schema?style=flat-square&logo=github)

[What is JSON Schema?](https://json-schema.org/overview/what-is-jsonschema)

Support is limited to [draft-07](https://json-schema.org/draft-07) at the moment.

## Features

- 🏗️ **Fluent Builder API** - Build JSON Schemas using an intuitive fluent interface
- 📝 **Draft-07 Support** - Full support for JSON Schema Draft-07 specification
- ✅ **Validation** - Validate data against schemas with detailed error messages
- 🤝 **Conditional Schemas** - Support for if/then/else, allOf, anyOf, and not conditions
- 🔄 **Reflection** - Generate schemas from PHP classes and closures
- 💪 **Type Safety** - Built with PHP 8.3+ features and strict typing

## Why?

I found myself looking for a nice, fluent way to build JSON Schemas, but couldn't find anything that fit my needs.

There are many use cases, but the most prevalent right now is usage around LLMs, in particular structured outputs and tool calling.

In fact I'm building an AI framework currently that uses this package to generate JSON Schemas in lots of scenarios. More to come on that soon!

## Requirements

- PHP 8.3+

## Installation

```bash
composer require cortexphp/json-schema
```

## Usage

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

// Create a basic user schema using the SchemaFactory
$schema = SchemaFactory::object('user')
    ->description('User schema')
    ->properties(
        SchemaFactory::string('name')
            ->minLength(2)
            ->maxLength(100)
            ->required(),
        SchemaFactory::string('email')
            ->format(SchemaFormat::Email)
            ->required(),
        SchemaFactory::integer('age')
            ->minimum(18)
            ->maximum(150),
        SchemaFactory::boolean('active')
            ->default(true),
        SchemaFactory::object('settings')
            ->additionalProperties(false)
            ->properties(
                SchemaFactory::string('theme')
                    ->enum(['light', 'dark']),
            ),
    );
```

You can also use the objects directly instead of the factory methods.

```php
$schema = (new ObjectSchema('user'))
    ->description('User schema')
    ->properties(
        (new StringSchema('name'))
            ->minLength(2)
            ->maxLength(100)
            ->required(),
        (new StringSchema('email'))
            ->format(SchemaFormat::Email)
            ->required(),
        (new IntegerSchema('age'))
            ->minimum(18)
            ->maximum(150),
        (new BooleanSchema('active'))
            ->default(true),
        (new ObjectSchema('settings'))
            ->additionalProperties(false)
            ->properties(
                (new StringSchema('theme'))
                    ->enum(['light', 'dark']),
            ),
    );
```

```php
// Convert to array
$schema->toArray();

// Convert to JSON string
$schema->toJson();
$schema->toJson(JSON_PRETTY_PRINT);

$data = [
    'name' => 'John Doe',
    'email' => 'foo', // invalid email
    'age' => 16,
    'active' => true,
    'settings' => [
        'theme' => 'dark',
    ],
];

// Validate data against the schema
try {
    $schema->validate($data);
} catch (SchemaException $e) {
    echo $e->getMessage(); // "The data must match the 'email' format"
}

// Or just get a boolean
$schema->isValid($data); // false
```

## Available Schema Types

### String Schema

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::string('name')
    ->minLength(2)
    ->maxLength(100)
    ->pattern('^[A-Za-z]+$')
    ->readOnly();
```

```php
$schema->isValid('John Doe'); // true
$schema->isValid('John Doe123'); // false (contains numbers)
$schema->isValid('J'); // false (too short)
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "string",
    "title": "name",
    "minLength": 2,
    "maxLength": 100,
    "pattern": "^[A-Za-z]+$",
    "readOnly": true
}
```

</details>

---

### Number Schema

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::number('price')
    ->minimum(0)
    ->maximum(1000)
    ->multipleOf(0.01);
```

```php
$schema->isValid(100); // true
$schema->isValid(1000.01); // false (too high)
$schema->isValid(0.01); // true
$schema->isValid(1.011); // false (not a multiple of 0.01)
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "number",
    "title": "price",
    "minimum": 0,
    "maximum": 1000,
    "multipleOf": 0.01
}
```

</details>

---

### Integer Schema

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::integer('age')
    ->exclusiveMinimum(0)
    ->exclusiveMaximum(150)
    ->multipleOf(1);
```

```php
$schema->isValid(18); // true
$schema->isValid(150); // false (too high)
$schema->isValid(0); // false (too low)
$schema->isValid(150.01); // false (not an integer)
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "integer",
    "title": "age",
    "exclusiveMinimum": 0,
    "exclusiveMaximum": 150,
    "multipleOf": 1
}
```

</details>

---

### Boolean Schema

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::boolean('active')
    ->default(true)
    ->readOnly();
```

```php
$schema->isValid(true); // true
$schema->isValid(false); // true
$schema->isValid(null); // false
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "boolean",
    "title": "active",
    "default": true,
    "readOnly": true
}
```

</details>

---

### Null Schema

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::null('deleted_at');
```

```php
$schema->isValid(null); // true
$schema->isValid(true); // false
$schema->isValid(false); // false
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "null",
    "title": "deleted_at"
}
```

</details>

---

### Array Schema

```php
use Cortex\JsonSchema\SchemaFactory;

// Simple array of strings
$schema = SchemaFactory::array('tags')
    ->items(SchemaFactory::string())
    ->minItems(1)
    ->maxItems(3)
    ->uniqueItems(true);
```

```php
$schema->isValid(['foo', 'bar']); // true
$schema->isValid(['foo', 'foo']); // false (not unique)
$schema->isValid([]); // false (too few items)
$schema->isValid(['foo', 'bar', 'baz', 'qux']); // false (too many items)
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "array",
    "title": "tags",
    "items": {
        "type": "string"
    },
    "minItems": 1,
    "maxItems": 3,
    "uniqueItems": true
}
```

</details>

Arrays also support validation of specific items using `contains`:

```php
use Cortex\JsonSchema\SchemaFactory;

// Array must contain at least one number between 10 and 20
$schema = SchemaFactory::array('numbers')
    ->contains(
        SchemaFactory::number()
            ->minimum(10)
            ->maximum(20)
    )
    ->minContains(2) // must contain at least 2 such numbers
    ->maxContains(3); // must contain at most 3 such numbers
```

```php
$schema->isValid([15, 12, 18]); // true (contains 3 numbers between 10-20)
$schema->isValid([15, 5, 25]); // false (only contains 1 number between 10-20)
$schema->isValid([15, 12, 18, 19]); // false (contains 4 numbers between 10-20)
```

You can also validate tuple-like arrays with different schemas for specific positions:

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::array('coordinates')
    ->items(
        SchemaFactory::number()->description('latitude'),
        SchemaFactory::number()->description('longitude'),
    );
```

```php
$schema->isValid([51.5074, -0.1278]); // true (valid lat/long)
$schema->isValid(['invalid', -0.1278]); // false (first item must be number)
```

---

### Object Schema

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

$schema = SchemaFactory::object('user')
    ->properties(
        SchemaFactory::string('name')->required(),
        SchemaFactory::string('email')->format(SchemaFormat::Email)->required(),
        SchemaFactory::object('settings')->properties(
            SchemaFactory::string('theme')->enum(['light', 'dark'])
        ),
    )
    ->additionalProperties(false);
```

```php
$schema->isValid([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]); // true

$schema->isValid([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'settings' => [
        'theme' => 'dark',
    ],
]); // true

$schema->isValid([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'settings' => [
        'theme' => 'dark',
    ],
    'foo' => 'bar',
]); // false (additional properties)
```

Objects support additional validation features:

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::object('config')
    // Validate property names against a pattern
    ->propertyNames(
        SchemaFactory::string()->pattern('^[a-zA-Z]+$')
    )
    // Control number of properties
    ->minProperties(1)
    ->maxProperties(10)
    // Control additional properties
    ->additionalProperties(false); // Disallow additional properties

// Or validate additional properties against a schema
$schema = SchemaFactory::object('config')
    ->properties(
        SchemaFactory::string('name')->required(),
        SchemaFactory::string('type')->required(),
    )
    ->additionalProperties(
        SchemaFactory::string()->minLength(3)
    );
```

```php
// Property names must be alphabetic
$schema->isValid(['123' => 'value']); // false
$schema->isValid(['validKey' => 'value']); // true

// Additional properties must match schema
$schema->isValid([
    'name' => 'config1',
    'type' => 'test',
    'extra' => 'valid', // valid: string with length >= 3
]); // true

$schema->isValid([
    'name' => 'config1',
    'type' => 'test',
    'extra' => 'no', // invalid: string too short
]); // false

$schema->isValid([
    'name' => 'config1',
    'type' => 'test',
    'extra' => 123, // invalid: wrong type
]); // false
```

Objects also support pattern-based property validation using `patternProperties`:

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::object('config')
    // Add a single pattern property
    ->patternProperty('^prefix_',
        SchemaFactory::string()->minLength(5)
    )
    // Add multiple pattern properties
    ->patternProperties([
        '^[A-Z][a-z]+$' => SchemaFactory::string(), // CamelCase properties
        '^\d+$' => SchemaFactory::number(),         // Numeric properties
    ]);

// Valid data
$schema->isValid([
    'prefix_hello' => 'world123',  // Matches ^prefix_ and meets minLength
    'Name' => 'John',              // Matches ^[A-Z][a-z]+$
    '123' => 42,                   // Matches ^\d+$
]); // true

// Invalid data
$schema->isValid([
    'prefix_hi' => 'hi',           // Too short for minLength
    'invalid' => 'no pattern',     // Doesn't match any pattern
    '123' => 'not a number',       // Wrong type for pattern
]); // false
```

Pattern properties can be combined with regular properties and `additionalProperties`:

```php
$schema = SchemaFactory::object('user')
    ->properties(
        SchemaFactory::string('name')->required(),
        SchemaFactory::integer('age')->required(),
    )
    ->patternProperty('^custom_', SchemaFactory::string())
    ->additionalProperties(false);

// Valid:
$schema->isValid([
    'name' => 'John',
    'age' => 30,
    'custom_field' => 'value',  // Matches pattern
]);

// Invalid (property doesn't match pattern):
$schema->isValid([
    'name' => 'John',
    'age' => 30,
    'invalid_field' => 'value',
]);
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "title": "user",
    "properties": {
        "name": {
            "type": "string"
        },
        "age": {
            "type": "integer"
        }
    },
    "patternProperties": {
        "^custom_": {
            "type": "string"
        }
    },
    "required": ["name", "age"],
    "additionalProperties": false
}
```

</details>

---

### Union Schema

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaType;

$schema = SchemaFactory::union([SchemaType::String, SchemaType::Integer], 'id')
    ->description('ID can be either a string or an integer')
    ->enum(['abc123', 'def456', 1, 2, 3])
    ->nullable();
```

```php
$schema->isValid('abc123'); // true
$schema->isValid(1); // true
$schema->isValid(null); // true (because it's nullable)
$schema->isValid(true); // false (not a string or integer)
$schema->isValid('invalid'); // false (not in enum)
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["string", "integer", "null"],
    "title": "id",
    "description": "ID can be either a string or an integer",
    "enum": ["abc123", "def456", 1, 2, 3]
}
```

</details>

---

### String Formats

Strings can be validated against various formats:

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

$schema = SchemaFactory::object('user')
    ->properties(
        SchemaFactory::string('email')->format(SchemaFormat::Email),
        SchemaFactory::string('website')->format(SchemaFormat::Uri),
        SchemaFactory::string('hostname')->format(SchemaFormat::Hostname),
        SchemaFactory::string('ipv4')->format(SchemaFormat::Ipv4),
        SchemaFactory::string('ipv6')->format(SchemaFormat::Ipv6),
        SchemaFactory::string('date')->format(SchemaFormat::Date),
        SchemaFactory::string('time')->format(SchemaFormat::Time),
        SchemaFactory::string('date_time')->format(SchemaFormat::DateTime),
        SchemaFactory::string('duration')->format(SchemaFormat::Duration),
        SchemaFactory::string('json_pointer')->format(SchemaFormat::JsonPointer),
        SchemaFactory::string('relative_json_pointer')->format(SchemaFormat::RelativeJsonPointer),
        SchemaFactory::string('uri_template')->format(SchemaFormat::UriTemplate),
        SchemaFactory::string('idn_email')->format(SchemaFormat::IdnEmail),
        SchemaFactory::string('idn_hostname')->format(SchemaFormat::Hostname),
        SchemaFactory::string('iri')->format(SchemaFormat::Iri),
        SchemaFactory::string('iri_reference')->format(SchemaFormat::IriReference),
    );
```

---

### Conditional Validation

The schema specification supports several types of conditional validation:

```php
use Cortex\JsonSchema\SchemaFactory;

// if/then/else condition
$schema = SchemaFactory::object('user')
    ->properties(
        SchemaFactory::string('type')->enum(['personal', 'business']),
        SchemaFactory::string('company_name'),
        SchemaFactory::string('tax_id'),
    )
    ->if(
        SchemaFactory::object()->properties(
            SchemaFactory::string('type')->const('business'),
        ),
    )
    ->then(
        SchemaFactory::object()->properties(
            SchemaFactory::string('company_name')->required(),
            SchemaFactory::string('tax_id')->required(),
        ),
    )
    ->else(
        SchemaFactory::object()->properties(
            SchemaFactory::string('company_name')->const(null),
            SchemaFactory::string('tax_id')->const(null),
        ),
    );

// allOf - all schemas must match
$schema = SchemaFactory::object()
    ->allOf(
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('name')->required(),
            ),
        SchemaFactory::object()
            ->properties(
                SchemaFactory::integer('age')
                    ->minimum(18)
                    ->required(),
            ),
    );

// anyOf - at least one schema must match
$schema = SchemaFactory::object('payment')
    ->anyOf(
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('credit_card')
                    ->pattern('^\d{16}$')
                    ->required(),
            ),
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('bank_transfer')
                    ->pattern('^\w{8,}$')
                    ->required(),
            ),
    );

// oneOf - exactly one schema must match
$schema = SchemaFactory::object('contact')
    ->oneOf(
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('email')
                    ->format(SchemaFormat::Email)
                    ->required(),
            ),
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('phone')
                    ->pattern('^\+\d{10,}$')
                    ->required(),
            ),
    );

// not - schema must not match
$schema = SchemaFactory::string('status')
    ->not(
        SchemaFactory::string()
            ->enum(['deleted', 'banned']),
    );
```

---

### Schema Definitions & References

You can define reusable schema components using definitions and reference them using `$ref`:

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::object('user')
    // Define a reusable address schema
    ->addDefinition(
        'address',
        SchemaFactory::object()
            ->properties(
                SchemaFactory::string('street')->required(),
                SchemaFactory::string('city')->required(),
                SchemaFactory::string('country')->required(),
            ),
    )
    // Use the address schema multiple times via $ref
    ->properties(
        SchemaFactory::string('name')->required(),
        SchemaFactory::object('billing_address')
            ->ref('#/definitions/address')
            ->required(),
        SchemaFactory::object('shipping_address')
            ->ref('#/definitions/address')
            ->required(),
    );
```

You can also add multiple definitions at once:

```php
$schema = SchemaFactory::object('user')
    ->addDefinitions([
        'address' => SchemaFactory::object()
            ->properties(
                SchemaFactory::string('street')->required(),
                SchemaFactory::string('city')->required(),
            ),
        'contact' => SchemaFactory::object()
            ->properties(
                SchemaFactory::string('email')
                    ->format(SchemaFormat::Email)
                    ->required(),
                SchemaFactory::string('phone'),
            ),
    ]);
```

The resulting JSON Schema will include both the definitions and references:

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "title": "user",
    "definitions": {
        "address": {
            "type": "object",
            "properties": {
                "street": {
                    "type": "string"
                },
                "city": {
                    "type": "string"
                }
            },
            "required": ["street", "city"]
        }
    },
    "properties": {
        "name": {
            "type": "string"
        },
        "billing_address": {
            "$ref": "#/definitions/address"
        },
        "shipping_address": {
            "$ref": "#/definitions/address"
        }
    },
    "required": ["name", "billing_address", "shipping_address"]
}
```

</details>

---

## Validation

The validate method throws a `SchemaException` when validation fails:

```php
use Cortex\JsonSchema\Exceptions\SchemaException;

try {
    $schema->validate($data);
} catch (SchemaException $e) {
    echo $e->getMessage(); // "The data must match the 'email' format"
}
```

## Common Schema Properties

All schema types support these common properties:

```php
$schema
    ->title('Schema Title')
    ->description('Schema description')
    ->default('default value')
    ->examples(['example1', 'example2'])
    ->comment('Schema comment')
    ->readOnly()
    ->writeOnly();
```

## Converting to JSON Schema

This uses reflection to infer the schema from the parameters and docblocks.

### From a Closure

```php
use Cortex\JsonSchema\SchemaFactory;

/**
 * This is the description of the closure
 *
 * @param string $name The name of the user
 * @param array $meta The meta data of the user
 * @param ?int $age The age of the user
 */
$closure = function (string $name, array $meta, ?int $age = null): void {};

// Build the schema from the closure
$schema = SchemaFactory::fromClosure($closure);

// Convert to JSON Schema
$schema->toJson();
```

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "description": "This is the description of the closure",
    "properties": {
        "name": {
            "type": "string",
            "description": "The name of the user"
        },
        "meta": {
            "type": "array",
            "description": "The meta data of the user"
        },
        "age": {
            "type": ["integer", "null"],
            "description": "The age of the user"
        }
    },
    "required": ["name", "meta"]
}
```

### From a Class

```php
use Cortex\JsonSchema\SchemaFactory;

/**
 * This is the description of the class
 */
class User
{
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
}

// Build the schema from the class
$schema = SchemaFactory::fromClass(User::class);

// Convert to JSON Schema
$schema->toJson();
```

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "title": "User",
    "description": "This is the description of the class",
    "properties": {
        "name": {
            "type": "string",
            "description": "The name of the user"
        },
        "age": {
            "type": [
                "integer",
                "null"
            ],
            "description": "The age of the user",
            "default": null
        },
        "height": {
            "type": "number",
            "description": "The height of the user in meters",
            "default": 1.7
        }
    },
    "required": ["name"]
}
```

### From an Backed Enum

```php
use Cortex\JsonSchema\SchemaFactory;

/**
 * This is the description of the enum
 */
enum PostType: int
{
    case Article = 1;
    case News = 2;
    case Tutorial = 3;
}

// Build the schema from the enum
$schema = SchemaFactory::fromEnum(PostType::class);

// Convert to JSON Schema
$schema->toJson();
```

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "description": "This is the description of the enum",
    "properties": {
        "PostType": {
            "type": "integer",
            "enum": [1, 2, 3]
        }
    },
    "required": ["PostType"]
}
```

## Credits

- [Sean Tymon](https://github.com/tymondesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

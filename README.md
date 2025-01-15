# Fluently build and validate JSON Schemas

[![Latest Version](https://img.shields.io/packagist/v/cortexphp/json-schema.svg?style=flat-square&logo=composer)](https://packagist.org/packages/cortexphp/json-schema)
![GitHub License](https://img.shields.io/github/license/cortexphp/json-schema?style=flat-square&logo=github)

[What is JSON Schema?](https://json-schema.org/overview/what-is-jsonschema)

At the moment support is limited to [draft-07](https://json-schema.org/draft-07) since that is probably the most widely adopted version.

## Installation

Minimum PHP version: 8.3

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
            ->properties([
                SchemaFactory::string('theme')
                    ->enum(['light', 'dark']),
            ]),
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

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "title": "user",
    "properties": {
        "name": {
            "type": "string",
            "title": "name"
        },
        "email": {
            "type": "string",
            "title": "email"
        },
        "settings": {
            "type": "object",
            "title": "settings",
            "properties": {
                "theme": {
                    "type": "string",
                    "title": "theme"
                }
            }
        }
    },
    "required": ["name", "email"],
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

TODO


## Credits

- [Sean Tymon](https://github.com/tymondesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

# json-schema

A PHP library for fluently building and validating JSON Schemas.

## Installation

```bash
composer require cortexphp/json-schema
```

## Usage

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

// Create a basic user schema
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
                SchemaFactory::string('theme')->enum(['light', 'dark']),
                SchemaFactory::boolean('notifications')
            ]),
    );

// Convert to array
$schema->toArray();

// Convert to JSON string
$schema->toJson();

// Validate data against the schema
try {
    $schema->validate([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 16,
        'active' => true,
        'settings' => [
            'theme' => 'dark',
            'notifications' => true,
        ],
    ]);
} catch (SchemaException $e) {
    echo $e->getMessage(); // "The data must match the 'email' format"
}

// Validate data against the schema
$schema->isValid($data);
```

## Available Schema Types

### String Schema

```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

$schema = SchemaFactory::string('name')
    ->minLength(2)
    ->maxLength(100)
    ->pattern('^[A-Za-z]+$')
    ->nullable()
    ->readOnly();
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["string", "null"],
    "title": "name",
    "minLength": 2,
    "maxLength": 100,
    "pattern": "^[A-Za-z]+$",
    "readOnly": true
}
```
</details>


```php
use Cortex\JsonSchema\SchemaFactory;
use Cortex\JsonSchema\Enums\SchemaFormat;

$schema = SchemaFactory::string('email')
    ->format(SchemaFormat::Email)
    ->nullable()
```
<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["string", "null"],
    "format": "email"
}
```
</details>


### Number Schema

```php
SchemaFactory::number('price')
    ->minimum(0)
    ->maximum(1000)
    ->exclusiveMinimum(0)
    ->exclusiveMaximum(1000)
    ->multipleOf(0.01)
    ->nullable();
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["number", "null"],
    "title": "price",
    "minimum": 0,
    "maximum": 1000,
    "exclusiveMinimum": 0,
    "exclusiveMaximum": 1000,
    "multipleOf": 0.01
}
```
</details>

### Integer Schema

```php
SchemaFactory::integer('age')
    ->minimum(0)
    ->maximum(120)
    ->exclusiveMinimum(0)
    ->exclusiveMaximum(120)
    ->multipleOf(1)
    ->nullable();
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["integer", "null"],
    "title": "age",
    "minimum": 0,
    "maximum": 120,
    "exclusiveMinimum": 0,
    "exclusiveMaximum": 120,
    "multipleOf": 1
}
```
</details>

### Boolean Schema

```php
SchemaFactory::boolean('active')
    ->default(true)
    ->nullable()
    ->readOnly();
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": ["boolean", "null"],
    "title": "active",
    "default": true,
    "readOnly": true
}
```
</details>

### Null Schema

```php
SchemaFactory::null('deleted_at')
    ->readOnly();
```

<details>
<summary>View JSON Schema</summary>

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "null",
    "title": "deleted_at",
    "readOnly": true
}
```
</details>

### Array Schema

```php
// Simple array of strings
SchemaFactory::array('tags')
    ->items(SchemaFactory::string())
    ->minItems(1)
    ->maxItems(10)
    ->uniqueItems(true);

// Tuple validation (fixed array format)
SchemaFactory::array('coordinates')
    ->prefixItems([
        SchemaFactory::number('latitude')
            ->minimum(-90)
            ->maximum(90),
        SchemaFactory::number('longitude')
            ->minimum(-180)
            ->maximum(180)
    ])
    ->minItems(2)
    ->maxItems(2);
```

<details>
<summary>View JSON Schema</summary>

```json
{
    // Simple array of strings
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "array",
    "title": "tags",
    "items": {
        "type": "string"
    },
    "minItems": 1,
    "maxItems": 10,
    "uniqueItems": true
}

{
    // Tuple validation
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "array",
    "title": "coordinates",
    "prefixItems": [
        {
            "type": "number",
            "title": "latitude",
            "minimum": -90,
            "maximum": 90
        },
        {
            "type": "number",
            "title": "longitude",
            "minimum": -180,
            "maximum": 180
        }
    ],
    "minItems": 2,
    "maxItems": 2
}
```
</details>

### Object Schema

```php
SchemaFactory::object('user')
    ->properties(
        SchemaFactory::string('name')->required(),
        SchemaFactory::string('email')->required(),
        SchemaFactory::object('settings')->properties(
            SchemaFactory::string('theme')
        ),
    )
    ->minProperties(1)
    ->maxProperties(10)
    ->additionalProperties(false);
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
    "minProperties": 1,
    "maxProperties": 10,
    "additionalProperties": false
}
```
</details>

## Validation

The library throws a `SchemaException` when validation fails:

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
    ->readOnly()
    ->writeOnly();
```

## Converting to JSON Schema

You can convert any schema to a JSON Schema array or JSON string:

```php
// Convert to array
$jsonSchemaArray = $schema->toArray();

// Convert to JSON string
$jsonSchemaString = $schema->toJson();
$jsonSchemaString = $schema->toJson(JSON_PRETTY_PRINT); // with pretty printing
```

This will output a valid JSON Schema that can be used with any JSON Schema validator.

Example JSON output:
```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "title": "user",
    "description": "User schema",
    "required": ["name", "email"],
    "properties": {
        "name": {
            "type": "string",
            "minLength": 2,
            "maxLength": 100
        },
        "email": {
            "type": "string",
            "format": "email"
        }
    }
}
```

## Testing

```bash
composer test
```

## Credits

- [Sean Tymon](https://github.com/tymondesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

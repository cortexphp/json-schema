# json-schema

A PHP library for fluently building and validating JSON Schemas.

## Installation

```bash
composer require cortex/json-schema
```

## Usage

```php
use Cortex\JsonSchema\SchemaFactory;

// Create a basic user schema
$schema = SchemaFactory::object('user')
    ->description('User schema')
    ->properties(
        SchemaFactory::string('name')
            ->minLength(2)
            ->maxLength(100)
            ->required(),
        SchemaFactory::string('email')
            ->format('email')
            ->required(),
        SchemaFactory::integer('age')
            ->minimum(0)
            ->maximum(120),
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

// Validate data
$schema->validate([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'active' => true,
    'settings' => [
        'theme' => 'dark',
        'notifications' => true
    ],
]);
```

## Available Schema Types

### String Schema

```php
use Cortex\JsonSchema\Enums\SchemaFormat;

SchemaFactory::string('name')
    ->minLength(2)
    ->maxLength(100)
    ->pattern('^[A-Za-z]+$')
    ->format(SchemaFormat::Email)
    ->nullable()
    ->readOnly()
    ->writeOnly();
```

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

### Boolean Schema

```php
SchemaFactory::boolean('active')
    ->default(true)
    ->nullable()
    ->readOnly();
```

### Null Schema

```php
SchemaFactory::null('deleted_at')
    ->readOnly();
```

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
// or with pretty printing
$jsonSchemaString = $schema->toJson(JSON_PRETTY_PRINT);
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

# json-schema

A PHP library for fluently building and validating JSON Schemas.

## Installation

```bash
composer require cortex/json-schema
```

## Usage

```php
use Cortex\JsonSchema\SchemaFactory;

$schema = SchemaFactory::object('user')
    ->description('User schema')
    ->properties(
        SchemaFactory::string('name'),
        SchemaFactory::string('email'),
    );

$schema->toArray();
```

```json
{
    "type": "object",
    "title": "user",
    "description": "User schema",
    "properties": {
        "name": {
            "type": "string"
        },
        "email": {
            "type": "string"
        }
    }
}
```

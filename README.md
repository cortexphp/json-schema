# json-schema

A PHP library for fluently building and validating JSON Schemas.

## Installation

```bash
composer require cortex/json-schema
```

## Usage

```php
$schema = ObjectSchema::make('user')
    ->description('User schema')
    ->properties(
        StringSchema::make('name'),
        StringSchema::make('email'),
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

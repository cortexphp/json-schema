# Fluently build and validate JSON Schemas

[![Latest Version](https://img.shields.io/packagist/v/cortexphp/json-schema.svg?style=flat-square&logo=composer)](https://packagist.org/packages/cortexphp/json-schema)
![GitHub Actions Test Workflow Status](https://img.shields.io/github/actions/workflow/status/cortexphp/json-schema/run-tests.yml?style=flat-square&logo=github)
![GitHub License](https://img.shields.io/github/license/cortexphp/json-schema?style=flat-square&logo=github)

[What is JSON Schema?](https://json-schema.org/overview/what-is-jsonschema)

## Features

- 🏗️ **Fluent Builder API** - Build JSON Schemas using an intuitive fluent interface
- 📝 **Multi-Version Support** - Support for JSON Schema Draft-06, Draft-07, Draft 2019-09, and Draft 2020-12
- ✅ **Validation** - Validate data against schemas with detailed error messages
- 🤝 **Conditional Schemas** - Support for if/then/else, allOf, anyOf, and not conditions
- 🔄 **Reflection** - Generate schemas from PHP Classes, Enums and Closures
- 💪 **Type Safety** - Built with PHP 8.3+ features and strict typing
- 🔍 **Version-Aware Features** - Automatic validation of version-specific features with helpful error messages

## JSON Schema Version Support

This package supports multiple JSON Schema specification versions with automatic feature validation:

### Supported Versions

- **Draft 2020-12** - (Default) Latest version with `prefixItems`, dynamic references, and format vocabularies
- **Draft 2019-09** - Adds advanced features like `$defs`, `unevaluatedProperties`, `deprecated`
- **Draft-07** (2018) - Legacy version with broad tool compatibility
- **Draft-06** (2017) - Legacy version for maximum compatibility with older tooling

## Requirements

- PHP 8.3+

## Installation

```bash
composer require cortexphp/json-schema
```

## Quick Start

```php
use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaFormat;

// Create a schema
$schema = Schema::object('user')
    ->description('User schema')
    ->properties(
        Schema::string('name')
            ->minLength(2)
            ->maxLength(100)
            ->required(),
        Schema::string('email')
            ->format(SchemaFormat::Email)
            ->required(),
        Schema::integer('age')
            ->minimum(18)
            ->maximum(150),
        Schema::boolean('active')
            ->default(true),
        Schema::object('settings')
            ->additionalProperties(false)
            ->properties(
                Schema::string('theme')
                    ->enum(['light', 'dark']),
            ),
    );

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30,
    'active' => true,
    'settings' => [
        'theme' => 'light',
    ],
];

if ($schema->isValid($data)) {
    echo "Valid!";
} else {
    try {
        $schema->validate($data);
    } catch (\Cortex\JsonSchema\Exceptions\SchemaException $e) {
        echo $e->getMessage();
    }
}

// Convert to array
$schema->toArray();

// Convert to JSON string
echo $schema->toJson(JSON_PRETTY_PRINT);
```

## Documentation

📚 **[View Full Documentation →](https://docs.cortexphp.com/json-schema)**

## Credits

- [Sean Tymon](https://github.com/tymondesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

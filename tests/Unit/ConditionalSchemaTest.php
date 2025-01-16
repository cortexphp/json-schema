<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use ArrayObject;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\SchemaFactory as Schema;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can create a schema with if/then/else conditions', function (): void {
    $schema = Schema::object('user')
        ->properties(
            Schema::string('type')->enum(['personal', 'business']),
            Schema::string('company_name'),
            Schema::string('tax_id'),
        )
        ->if(
            Schema::object()->properties(
                Schema::string('type')->const('business'),
            ),
        )
        ->then(
            Schema::object()->properties(
                Schema::string('company_name')->required(),
                Schema::string('tax_id')->required(),
            ),
        )
        ->else(
            Schema::object()->properties(
                Schema::string('company_name')->const(null),
                Schema::string('tax_id')->const(null),
            ),
        );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('if');
    expect($schemaArray)->toHaveKey('then');
    expect($schemaArray)->toHaveKey('else');
    expect($schemaArray['if'])->toHaveKey('properties.type.const', 'business');

    // Validation tests
    // Business type requires company_name and tax_id
    expect(fn() => $schema->validate([
        'type' => 'business',
    ]))->toThrow(SchemaException::class, "The data is not valid on 'then' branch");

    expect(fn() => $schema->validate([
        'type' => 'business',
        'company_name' => 'Acme Inc',
        'tax_id' => '123456789',
    ]))->not->toThrow(SchemaException::class);

    // Personal type should not allow company_name and tax_id
    expect(fn() => $schema->validate([
        'type' => 'personal',
        'company_name' => 'Acme Inc',
    ]))->toThrow(SchemaException::class, "const contains a value that doesn't match the type keyword");

    expect(fn() => $schema->validate([
        'type' => 'personal',
    ]))->not->toThrow(SchemaException::class);
});

it('can create a schema with allOf condition', function (): void {
    $schema = Schema::object()
        ->properties(
            Schema::string('street_address'),
            Schema::string('country')
                ->default('United States of America')
                ->enum(['United States of America', 'Canada', 'Netherlands']),
        )
        ->allOf(
            Schema::object()
                ->if(
                    Schema::object()->properties(
                        Schema::string('country')->const('United States of America'),
                    ),
                )
                ->then(
                    Schema::object()->properties(
                        Schema::string('postal_code')->pattern('[0-9]{5}(-[0-9]{4})?'),
                    ),
                ),
            Schema::object()
                ->if(
                    Schema::object()
                        ->properties(
                            Schema::string('country')->const('Canada'),
                        )
                        ->required(),
                )
                ->then(
                    Schema::object()->properties(
                        Schema::string('postal_code')->pattern('[A-Z]\d[A-Z] \d[A-Z]\d'),
                    ),
                ),
            Schema::object()
                ->if(
                    Schema::object()
                        ->properties(
                            Schema::string('country')->const('Netherlands'),
                        )
                        ->required(),
                )
                ->then(
                    Schema::object()->properties(
                        Schema::string('postal_code')->pattern('[0-9]{4} [A-Z]{2}'),
                    ),
                ),
        );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('allOf');
    expect($schemaArray['allOf'])->toHaveCount(3);

    // Test US address validation
    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'United States of America',
        'postal_code' => '12345',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'United States of America',
        'postal_code' => '12345-6789',
    ]))->not->toThrow(SchemaException::class);

    // Test Canadian address validation
    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'Canada',
        'postal_code' => 'A1B 2C3',
    ]))->not->toThrow(SchemaException::class);

    // Test Netherlands address validation
    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'Netherlands',
        'postal_code' => '1234 AB',
    ]))->not->toThrow(SchemaException::class);

    // Test invalid postal codes
    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'United States of America',
        'postal_code' => '1234',
    ]))->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'Canada',
        'postal_code' => '12345',
    ]))->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'street_address' => '123 Main St',
        'country' => 'Netherlands',
        'postal_code' => 'AB12 CD',
    ]))->toThrow(SchemaException::class);
});

it('can create a schema with anyOf condition', function (): void {
    $schema = Schema::object('payment')
        ->anyOf(
            Schema::object()->properties(
                Schema::string('credit_card')
                    ->pattern('^\d{16}$')
                    ->required(),
            ),
            Schema::object()->properties(
                Schema::string('bank_transfer')
                    ->pattern('^\w{8,}$')
                    ->required(),
            ),
        );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('anyOf');
    expect($schemaArray['anyOf'])->toHaveCount(2);
    expect($schemaArray['anyOf'][0])->toHaveKey('required', ['credit_card']);
    expect($schemaArray['anyOf'][1])->toHaveKey('required', ['bank_transfer']);

    // Validation tests
    expect(fn() => $schema->validate([
        'credit_card' => '4111111111111111',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'bank_transfer' => 'TRANSFER123',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'bank_transfer' => 'TRANSFER123',
        'credit_card' => '4111111111111111',
    ]))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate([
        'credit_card' => 'invalid',
    ]))->toThrow(SchemaException::class, 'The data should match at least one schema');

    expect(fn() => $schema->validate(new ArrayObject()))->toThrow(SchemaException::class, 'The data should match at least one schema');
});

it('can create a schema with oneOf condition', function (): void {
    $schema = Schema::object('contact')
        ->oneOf(
            Schema::object()->properties(
                Schema::string('email')->format(SchemaFormat::Email)->required(),
                Schema::string('phone')->const(null),
            ),
            Schema::object()->properties(
                Schema::string('phone')->pattern('^\+\d{10,}$')->required(),
                Schema::string('email')->const(null),
            ),
        );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('oneOf');
    expect($schemaArray['oneOf'])->toHaveCount(2);
    expect($schemaArray['oneOf'][0])->toHaveKey('required', ['email']);
    expect($schemaArray['oneOf'][1])->toHaveKey('required', ['phone']);

    // Validation tests
    // Only email is valid
    expect(fn() => $schema->validate([
        'email' => 'test@example.com',
    ]))->not->toThrow(SchemaException::class);

    // Only phone is valid
    expect(fn() => $schema->validate([
        'phone' => '+1234567890',
    ]))->not->toThrow(SchemaException::class);

    // Both email and phone is invalid (must be exactly one)
    expect(fn() => $schema->validate([
        'email' => 'test@example.com',
        'phone' => '+1234567890',
    ]))->toThrow(SchemaException::class);

    // Neither email nor phone is invalid
    expect(fn() => $schema->validate([]))->toThrow(SchemaException::class);
});

it('can create a schema with not condition', function (): void {
    $schema = Schema::string('status')->not(
        Schema::string()->enum(['deleted', 'banned']),
    );

    $schemaArray = $schema->toArray();

    expect($schemaArray)->toHaveKey('not');
    expect($schemaArray['not'])->toHaveKey('enum', ['deleted', 'banned']);

    // Validation tests
    expect(fn() => $schema->validate('active'))->not->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('inactive'))->not->toThrow(SchemaException::class);

    expect(fn() => $schema->validate('deleted'))->toThrow(SchemaException::class);
    expect(fn() => $schema->validate('banned'))->toThrow(SchemaException::class);
});

it('throws exception when setting then without if', function (): void {
    expect(fn(): ObjectSchema => Schema::object('test')->then(Schema::object()))->toThrow(
        SchemaException::class,
        'Cannot set then condition without if condition',
    );
});

it('throws exception when setting else without if', function (): void {
    expect(fn(): ObjectSchema => Schema::object('test')->else(Schema::object()))->toThrow(
        SchemaException::class,
        'Cannot set else condition without if condition',
    );
});

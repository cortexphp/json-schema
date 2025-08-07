<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit\Targets;

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can add a single definition to a schema', function (): void {
    $objectSchema = Schema::object('user')
        ->addDefinition(
            'address',
            Schema::object()
                ->properties(
                    Schema::string('street')->required(),
                    Schema::string('city')->required(),
                    Schema::string('country')->required(),
                ),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('$defs.address');
    expect($schemaArray['$defs']['address'])->toHaveKey('type', 'object');
    expect($schemaArray['$defs']['address'])->toHaveKey('required', ['street', 'city', 'country']);
});

it('can add multiple definitions to a schema', function (): void {
    $objectSchema = Schema::object('user')
        ->addDefinitions([
            'address' => Schema::object()
                ->properties(
                    Schema::string('street')->required(),
                    Schema::string('city')->required(),
                ),
            'contact' => Schema::object()
                ->properties(
                    Schema::string('email')
                        ->format(SchemaFormat::Email)
                        ->required(),
                    Schema::string('phone'),
                ),
        ]);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('$defs.address');
    expect($schemaArray)->toHaveKey('$defs.contact');
    expect($schemaArray['$defs']['address'])->toHaveKey('required', ['street', 'city']);
    expect($schemaArray['$defs']['contact'])->toHaveKey('required', ['email']);
});

it('can reference a definition in a schema property', function (): void {
    $objectSchema = Schema::object('user')
        ->addDefinition(
            'address',
            Schema::object()
                ->properties(
                    Schema::string('street')->required(),
                    Schema::string('city')->required(),
                ),
        )
        ->properties(
            Schema::string('name')->required(),
            Schema::object('billing_address')
                ->ref('#/$defs/address'),
            Schema::object('shipping_address')
                ->ref('#/$defs/address'),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('$defs.address');
    expect($schemaArray)->toHaveKey('properties.billing_address.$ref', '#/$defs/address');
    expect($schemaArray)->toHaveKey('properties.shipping_address.$ref', '#/$defs/address');
});

it('validates data against a schema with referenced definitions', function (): void {
    $objectSchema = Schema::object('user')
        ->addDefinition(
            'address',
            Schema::object()
                ->properties(
                    Schema::string('street')->required(),
                    Schema::string('city')->required(),
                ),
        )
        ->properties(
            Schema::string('name')->required(),
            Schema::object('billing_address')
                ->ref('#/$defs/address')
                ->required(),
            Schema::object('shipping_address')
                ->ref('#/$defs/address')
                ->required(),
        );

    // Test valid data
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'billing_address' => [
            'street' => '123 Main St',
            'city' => 'New York',
        ],
        'shipping_address' => [
            'street' => '456 Market St',
            'city' => 'San Francisco',
        ],
    ]))->not->toThrow(SchemaException::class);

    // Test missing required field in referenced schema
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'billing_address' => [
            'street' => '123 Main St',
            // missing city
        ],
        'shipping_address' => [
            'street' => '456 Market St',
            'city' => 'San Francisco',
        ],
    ]))->toThrow(SchemaException::class);

    // Test invalid type in referenced schema
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'billing_address' => [
            'street' => 123, // should be string
            'city' => 'New York',
        ],
        'shipping_address' => [
            'street' => '456 Market St',
            'city' => 'San Francisco',
        ],
    ]))->toThrow(SchemaException::class);

    // Test missing required referenced object
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'billing_address' => [
            'street' => '123 Main St',
            'city' => 'New York',
        ],
        // missing shipping_address
    ]))->toThrow(SchemaException::class);
});

it('validates data against a schema with multiple referenced definitions', function (): void {
    $objectSchema = Schema::object('user')
        ->addDefinitions([
            'contact' => Schema::object()
                ->properties(
                    Schema::string('email')
                        ->format(SchemaFormat::Email)
                        ->required(),
                    Schema::string('phone'),
                ),
            'address' => Schema::object()
                ->properties(
                    Schema::string('street')->required(),
                    Schema::string('city')->required(),
                ),
        ])
        ->properties(
            Schema::string('name')->required(),
            Schema::object('primary_contact')
                ->ref('#/$defs/contact')
                ->required(),
            Schema::object('address')
                ->ref('#/$defs/address')
                ->required(),
        );

    // Test valid data
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'primary_contact' => [
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ],
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
        ],
    ]))->not->toThrow(SchemaException::class);

    // Test valid data with optional fields omitted
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'primary_contact' => [
            'email' => 'john@example.com',
            // phone is optional
        ],
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
        ],
    ]))->not->toThrow(SchemaException::class);

    // Test invalid email in contact definition
    expect(fn() => $objectSchema->validate([
        'name' => 'John Doe',
        'primary_contact' => [
            'email' => 'not-an-email',
            'phone' => '+1234567890',
        ],
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
        ],
    ]))->toThrow(SchemaException::class);
});

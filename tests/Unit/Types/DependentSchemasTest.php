<?php

declare(strict_types=1);

use Cortex\JsonSchema\Schema;
use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\Types\ObjectSchema;
use Cortex\JsonSchema\Enums\SchemaVersion;
use Cortex\JsonSchema\Exceptions\SchemaException;

it('can set a single dependent schema', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('credit_card'),
        )
        ->dependentSchema(
            'credit_card',
            Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray['dependentSchemas'])->toHaveKey('credit_card');
    expect($schemaArray['dependentSchemas']['credit_card'])->toBe([
        'type' => 'object',
        'properties' => [
            'billing_address' => [
                'type' => 'string',
            ],
        ],
        'required' => ['billing_address'],
    ]);

    // Test basic validation
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeTrue();

    // Note: dependentSchemas validation requires a full JSON Schema validator
    // This package generates schemas but doesn't implement the complex dependent logic
});

it('can set multiple dependent schemas at once', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('credit_card'),
            Schema::string('phone'),
        )
        ->dependentSchemas([
            'credit_card' => Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
            'phone' => Schema::object()->properties(
                Schema::string('phone_verified')->enum(['yes', 'no'])->required(),
            ),
        ]);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray['dependentSchemas'])->toHaveKey('credit_card');
    expect($schemaArray['dependentSchemas'])->toHaveKey('phone');

    expect($schemaArray['dependentSchemas']['credit_card'])->toBe([
        'type' => 'object',
        'properties' => [
            'billing_address' => [
                'type' => 'string',
            ],
        ],
        'required' => ['billing_address'],
    ]);

    expect($schemaArray['dependentSchemas']['phone'])->toBe([
        'type' => 'object',
        'properties' => [
            'phone_verified' => [
                'type' => 'string',
                'enum' => ['yes', 'no'],
            ],
        ],
        'required' => ['phone_verified'],
    ]);
});

it('can add dependent schemas one by one', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->dependentSchema(
            'credit_card',
            Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
        )
        ->dependentSchema(
            'shipping_address',
            Schema::object()->properties(
                Schema::string('shipping_method')->required(),
            ),
        );

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray['dependentSchemas'])->toHaveKeys(['credit_card', 'shipping_address']);
});

it('throws exception when using dependentSchemas with Draft 07', function (): void {
    expect(
        fn(): ObjectSchema => Schema::object('user', SchemaVersion::Draft_07)
            ->dependentSchema('credit_card', Schema::object()),
    )->toThrow(
        SchemaException::class,
        'Feature "Dependent schemas (split from dependencies)" is not supported in Draft 7. Minimum version required: Draft 2019-09.',
    );
});

it('works with Draft 2019-09', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->dependentSchema('credit_card', Schema::object());

    expect($objectSchema->toArray())->toHaveKey('dependentSchemas');
    expect($objectSchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2019-09/schema');
});

it('works with Draft 2020-12', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2020_12)
        ->properties(
            Schema::string('name')->required(),
        )
        ->dependentSchema('credit_card', Schema::object());

    expect($objectSchema->toArray())->toHaveKey('dependentSchemas');
    expect($objectSchema->toArray())->toHaveKey('$schema', 'https://json-schema.org/draft/2020-12/schema');
});

it('detects dependentSchemas feature correctly', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->dependentSchema('credit_card', Schema::object());

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->toContain('dependentSchemas');
});

it('does not include dependentSchemas feature when not used', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        );

    // Access the protected method via reflection to test feature detection
    $reflection = new ReflectionClass($objectSchema);
    $reflectionMethod = $reflection->getMethod('getUsedFeatures');

    $features = $reflectionMethod->invoke($objectSchema);

    $featureValues = array_map(fn($feature) => $feature->value, $features);
    expect($featureValues)->not->toContain('dependentSchemas');
});

it('can combine with other object properties', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
            Schema::string('credit_card'),
        )
        ->additionalProperties(false)
        ->dependentSchema(
            'credit_card',
            Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
        )
        ->minProperties(2)
        ->maxProperties(10);

    $schemaArray = $objectSchema->toArray();

    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('required', ['name', 'email']);
    expect($schemaArray)->toHaveKey('additionalProperties', false);
    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray)->toHaveKey('minProperties', 2);
    expect($schemaArray)->toHaveKey('maxProperties', 10);
});

it('validates version during schema output', function (): void {
    // Create schema with Draft 2019-09
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
        )
        ->dependentSchema('credit_card', Schema::object());

    // Change version to Draft 07 after setting dependentSchemas
    $objectSchema->version(SchemaVersion::Draft_07);

    // Should throw when trying to output
    expect(fn(): array => $objectSchema->toArray())
        ->toThrow(
            SchemaException::class,
            'Feature "Dependent schemas (split from dependencies)" is not supported in Draft 7',
        );
});

it('generates correct schema structure for complex scenarios', function (): void {
    // Complex schema with multiple dependent schemas
    $objectSchema = Schema::object('registration', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
            Schema::string('payment_method')->enum(['credit_card', 'paypal', 'bank_transfer']),
            Schema::boolean('is_premium'),
        )
        ->dependentSchemas([
            'payment_method' => Schema::object()
                ->if(Schema::object()->properties(
                    Schema::string('payment_method')->const('credit_card'),
                ))
                ->then(Schema::object()->properties(
                    Schema::string('card_number')->required(),
                    Schema::string('cvv')->required(),
                )),
            'is_premium' => Schema::object()
                ->if(Schema::object()->properties(
                    Schema::boolean('is_premium')->const(true),
                ))
                ->then(Schema::object()->properties(
                    Schema::string('premium_tier')->enum(['gold', 'platinum'])->required(),
                )),
        ]);

    $schemaArray = $objectSchema->toArray();

    // Verify schema structure
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('required', ['name', 'email']);
    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray['dependentSchemas'])->toHaveKeys(['payment_method', 'is_premium']);

    // Test basic validation (defined properties)
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
    ]))->toBeTrue();

    // Required property validation still works
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeFalse();

    expect(fn() => $objectSchema->validate([
        'name' => 'John',
    ]))->toThrow(SchemaException::class);
});

it('generates correct dependent schema JSON output', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('credit_card'),
            Schema::string('billing_address'),
        )
        ->dependentSchema(
            'credit_card',
            Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
        );

    // Test that the dependent schema is correctly generated in JSON output
    $schemaArray = $objectSchema->toArray();

    // Verify main schema structure
    expect($schemaArray)->toHaveKey('type', 'object');
    expect($schemaArray)->toHaveKey('title', 'user');
    expect($schemaArray)->toHaveKey('properties');
    expect($schemaArray)->toHaveKey('required', ['name']);

    // Verify dependent schema structure
    expect($schemaArray)->toHaveKey('dependentSchemas');
    expect($schemaArray['dependentSchemas'])->toHaveKey('credit_card');
    expect($schemaArray['dependentSchemas']['credit_card'])->toBe([
        'type' => 'object',
        'properties' => [
            'billing_address' => [
                'type' => 'string',
            ],
        ],
        'required' => ['billing_address'],
    ]);

    // Test basic validation for data that meets base requirements
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeTrue();

    expect($objectSchema->isValid([
        'name' => 'John',
        'credit_card' => '1234-5678-9000-0000',
        'billing_address' => '123 Main St',
    ]))->toBeTrue();

    // Note: This package generates correct JSON Schema with dependentSchemas
    // Full dependent schema validation requires an external JSON Schema validator
});

it('validates multiple dependent schemas independently', function (): void {
    $objectSchema = Schema::object('registration', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
            Schema::string('credit_card'),
            Schema::string('shipping_address'),
        )
        ->dependentSchemas([
            'credit_card' => Schema::object()->properties(
                Schema::string('billing_address')->required(),
            ),
            'shipping_address' => Schema::object()->properties(
                Schema::string('shipping_method')->required(),
            ),
        ]);

    // Valid: only required fields
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
    ]))->toBeTrue();

    // Valid: with credit_card but no shipping_address
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'credit_card' => '1234-5678-9000-0000',
        'billing_address' => '123 Main St',
    ]))->toBeTrue();

    // Valid: with shipping_address but no credit_card
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'shipping_address' => '456 Oak Ave',
        'shipping_method' => 'express',
    ]))->toBeTrue();

    // Valid: with both dependent properties and their requirements
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'credit_card' => '1234-5678-9000-0000',
        'billing_address' => '123 Main St',
        'shipping_address' => '456 Oak Ave',
        'shipping_method' => 'express',
    ]))->toBeTrue();
});

it('validates conditional dependent schemas with if-then-else', function (): void {
    $objectSchema = Schema::object('payment', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('payment_method')->enum(['credit_card', 'paypal', 'bank_transfer']),
        )
        ->dependentSchema(
            'payment_method',
            Schema::object()
                ->if(Schema::object()->properties(
                    Schema::string('payment_method')->const('credit_card'),
                ))
                ->then(Schema::object()->properties(
                    Schema::string('card_number')->pattern('^\d{4}-\d{4}-\d{4}-\d{4}$')->required(),
                    Schema::string('cvv')->pattern('^\d{3}$')->required(),
                ))
                ->else(Schema::object()->properties(
                    Schema::string('account_info')->required(),
                )),
        );

    // Valid: no payment_method, dependent schema doesn't apply
    expect($objectSchema->isValid([
        'name' => 'John',
    ]))->toBeTrue();

    // Valid: with credit_card payment method and required fields
    expect($objectSchema->isValid([
        'name' => 'John',
        'payment_method' => 'credit_card',
        'card_number' => '1234-5678-9000-0000',
        'cvv' => '123',
    ]))->toBeTrue();

    // Valid: with paypal payment method and account_info
    expect($objectSchema->isValid([
        'name' => 'John',
        'payment_method' => 'paypal',
        'account_info' => 'user@paypal.com',
    ]))->toBeTrue();

    // Invalid: wrong card number format (basic validation still works)
    expect($objectSchema->isValid([
        'name' => 'John',
        'payment_method' => 'credit_card',
        'card_number' => 'invalid-format',
        'cvv' => '123',
    ]))->toBeFalse(); // Pattern validation fails

    // Invalid: wrong CVV format
    expect($objectSchema->isValid([
        'name' => 'John',
        'payment_method' => 'credit_card',
        'card_number' => '1234-5678-9000-0000',
        'cvv' => '12345', // Wrong pattern
    ]))->toBeFalse();
});

it('validates nested dependent schemas with complex conditions', function (): void {
    $objectSchema = Schema::object('user', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('name')->required(),
            Schema::string('email')->required(),
            Schema::boolean('is_premium'),
            Schema::string('subscription_type')->enum(['monthly', 'yearly']),
        )
        ->dependentSchemas([
            'is_premium' => Schema::object()
                ->if(Schema::object()->properties(
                    Schema::boolean('is_premium')->const(true),
                ))
                ->then(Schema::object()->properties(
                    Schema::string('subscription_type')->required(),
                    Schema::string('premium_tier')->enum(['gold', 'platinum'])->required(),
                )),
            'subscription_type' => Schema::object()->properties(
                Schema::integer('billing_cycle_day')->minimum(1)->maximum(31)->required(),
            ),
        ]);

    // Valid: basic user without premium features
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
    ]))->toBeTrue();

    // Valid: non-premium user
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'is_premium' => false,
    ]))->toBeTrue();

    // Valid: premium user with all required fields
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'is_premium' => true,
        'subscription_type' => 'monthly',
        'premium_tier' => 'gold',
        'billing_cycle_day' => 15,
    ]))->toBeTrue();

    // Valid: user with subscription but not premium
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'subscription_type' => 'yearly',
        'billing_cycle_day' => 1,
    ]))->toBeTrue();

    // Invalid: billing cycle day out of range
    expect($objectSchema->isValid([
        'name' => 'John',
        'email' => 'john@example.com',
        'subscription_type' => 'monthly',
        'billing_cycle_day' => 32, // Out of range
    ]))->toBeFalse();
});

it('validates dependent schemas combined with other validation rules', function (): void {
    $objectSchema = Schema::object('order', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('order_id')->required(),
            Schema::string('customer_email')->format(SchemaFormat::Email)->required(),
            Schema::number('amount')->minimum(0.01)->required(),
            Schema::boolean('requires_shipping'),
            Schema::boolean('express_delivery'),
            // Include dependent properties in main schema for additionalProperties: false to work
            Schema::string('shipping_address'),
            Schema::string('shipping_method'),
            Schema::number('express_fee'),
        )
        ->minProperties(3)
        ->dependentSchemas([
            'requires_shipping' => Schema::object()
                ->if(Schema::object()->properties(
                    Schema::boolean('requires_shipping')->const(true),
                ))
                ->then(Schema::object()->properties(
                    Schema::string('shipping_address')->minLength(10)->required(),
                    Schema::string('shipping_method')->enum(['standard', 'express'])->required(),
                )),
            'express_delivery' => Schema::object()
                ->if(Schema::object()->properties(
                    Schema::boolean('express_delivery')->const(true),
                ))
                ->then(Schema::object()->properties(
                    Schema::number('express_fee')->minimum(5.00)->required(),
                )),
        ]);

    // Valid: basic order without shipping
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 99.99,
    ]))->toBeTrue();

    // Invalid: amount too low
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 0.00, // Below minimum
    ]))->toBeFalse();

    // Invalid: invalid email format
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'invalid-email',
        'amount' => 99.99,
    ]))->toBeFalse();

    // Valid: order with shipping (now that properties are defined)
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 99.99,
        'requires_shipping' => true,
        'shipping_address' => '123 Main Street, City, State 12345',
        'shipping_method' => 'standard',
    ]))->toBeTrue();

    // Valid: order with express delivery
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 99.99,
        'express_delivery' => true,
        'express_fee' => 15.00,
    ]))->toBeTrue();

    // Invalid: express fee too low
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 99.99,
        'express_delivery' => true,
        'express_fee' => 2.00, // Below minimum
    ]))->toBeFalse();

    // Invalid: shipping address too short
    expect($objectSchema->isValid([
        'order_id' => 'ORD-12345',
        'customer_email' => 'customer@example.com',
        'amount' => 99.99,
        'requires_shipping' => true,
        'shipping_address' => 'Short', // Too short
        'shipping_method' => 'standard',
    ]))->toBeFalse();
});

it('throws validation exceptions with detailed messages for dependent schema violations', function (): void {
    $objectSchema = Schema::object('registration', SchemaVersion::Draft_2019_09)
        ->properties(
            Schema::string('username')->minLength(3)->required(),
            Schema::string('password')->minLength(8)->required(),
            Schema::string('payment_method')->enum(['credit_card', 'paypal']),
        )
        ->dependentSchema(
            'payment_method',
            Schema::object()->properties(
                Schema::string('billing_email')->format(SchemaFormat::Email)->required(),
            ),
        );

    // Valid data should not throw
    expect(function () use ($objectSchema): void {
        $objectSchema->validate([
            'username' => 'johndoe',
            'password' => 'secretpassword123',
        ]);
    })->not->toThrow(SchemaException::class);

    // Invalid: username too short
    expect(function () use ($objectSchema): void {
        try {
            $objectSchema->validate([
                'username' => 'jo', // Too short
                'password' => 'secretpassword123',
            ]);
        } catch (SchemaException $schemaException) {
            expect($schemaException->getErrors())->toHaveCount(1)->toHaveKey('/username');
            expect($schemaException->getErrors()['/username'])->toContain('Minimum string length is 3, found 2');

            throw $schemaException;
        }
    })->toThrow(SchemaException::class, 'The properties must match schema: username');

    // Invalid: password too short
    expect(function () use ($objectSchema): void {
        try {
            $objectSchema->validate([
                'username' => 'johndoe',
                'password' => 'short', // Too short
            ]);
        } catch (SchemaException $schemaException) {
            expect($schemaException->getErrors())->toHaveCount(1)->toHaveKey('/password');
            expect($schemaException->getErrors()['/password'])->toContain('Minimum string length is 8, found 5');

            throw $schemaException;
        }
    })->toThrow(SchemaException::class, 'The properties must match schema: password');

    // Invalid: invalid email format in billing_email
    expect(function () use ($objectSchema): void {
        try {
            $objectSchema->validate([
                'username' => 'johndoe',
                'password' => 'secretpassword123',
                'payment_method' => 'credit_card',
                'billing_email' => 'invalid-email-format', // Invalid email
            ]);
        } catch (SchemaException $schemaException) {
            expect($schemaException->getErrors())->toHaveCount(1)->toHaveKey('/billing_email');
            expect($schemaException->getErrors()['/billing_email'])->toContain(
                "The data must match the 'email' format",
            );

            throw $schemaException;
        }
    })->toThrow(
        SchemaException::class,
        "The object must match dependency schema defined on property 'payment_method'",
    );

    // Valid: with proper billing email
    expect(function () use ($objectSchema): void {
        $objectSchema->validate([
            'username' => 'johndoe',
            'password' => 'secretpassword123',
            'payment_method' => 'credit_card',
            'billing_email' => 'billing@example.com',
        ]);
    })->not->toThrow(SchemaException::class);
});

<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests\Unit;

use Cortex\JsonSchema\Enums\SchemaFormat;
use Cortex\JsonSchema\SchemaFactory as Schema;

it('can create an object schema', function (): void {
    $schema = Schema::object('user')
        ->description('User schema')
        ->properties(
            Schema::string('name'),
            Schema::string('email')
                ->format(SchemaFormat::Email),
            Schema::string('dob')
                ->format(SchemaFormat::Date),
            Schema::integer('age')
                ->minimum(18)
                ->maximum(150)
                ->readOnly(),
            Schema::boolean('is_active'),
            Schema::string('deleted_at')
                ->format(SchemaFormat::DateTime)
                ->nullable(),
        );

    var_dump($schema->toArray());

    expect($schema->toArray())->toHaveKey('description', 'User schema');
});

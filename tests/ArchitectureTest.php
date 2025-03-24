<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests;

use Throwable;
use Cortex\JsonSchema\Contracts\Schema;
use Cortex\JsonSchema\Contracts\Converter;

arch()->preset()->php();
arch()->preset()->security();

arch()->expect('Cortex\JsonSchema\Contracts')->toBeInterfaces();
arch()->expect('Cortex\JsonSchema\Enums')->toBeEnums();
arch()->expect('Cortex\JsonSchema\Exceptions')->toImplement(Throwable::class);
arch()->expect('Cortex\JsonSchema\Converters')->classes()->toImplement(Converter::class);
arch()->expect('Cortex\JsonSchema\Types')->classes()->toImplement(Schema::class);

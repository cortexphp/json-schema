<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Tests;

use Throwable;

arch()->preset()->php();
arch()->preset()->security();

arch()->expect('Cortex\JsonSchema\Contracts')->toBeInterfaces();
arch()->expect('Cortex\JsonSchema\Enums')->toBeEnums();
arch()->expect('Cortex\JsonSchema\Exceptions')->toExtend(Throwable::class);

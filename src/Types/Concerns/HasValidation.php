<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Types\Concerns;

use Opis\JsonSchema\Helper;
use InvalidArgumentException;
use Opis\JsonSchema\Validator;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Cortex\JsonSchema\Exceptions\SchemaException;
use Opis\JsonSchema\Exceptions\SchemaException as OpisSchemaException;

/** @mixin \Cortex\JsonSchema\Contracts\Schema */
trait HasValidation
{
    /**
     * Validate the given value against the schema.
     *
     * @throws \Cortex\JsonSchema\Exceptions\SchemaException
     */
    public function validate(mixed $value): void
    {
        $validator = new Validator();

        try {
            $result = $validator->validate(
                Helper::toJSON($value),
                Helper::toJSON($this->toArray()),
            );
        } catch (OpisSchemaException|InvalidArgumentException $e) {
            throw new SchemaException($e->getMessage(), $e->getCode(), $e);
        }

        $error = $result->error();

        if ($error !== null) {
            throw new SchemaException(
                (new ErrorFormatter())->formatErrorMessage($error),
            );
        }
    }

    /**
     * Determine if the given value is valid against the schema.
     */
    public function isValid(mixed $value): bool
    {
        try {
            $this->validate($value);

            return true;
        } catch (SchemaException) {
            return false;
        }
    }
}

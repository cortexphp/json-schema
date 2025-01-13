<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Exceptions;

use Exception;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;

class SchemaException extends Exception
{
    protected ValidationError $error;

    public static function failedValidation(ValidationError $error): self
    {
        $exception = new self(
            (new ErrorFormatter())->formatErrorMessage($error),
        );

        $exception->setError($error);

        return $exception;
    }

    public function setError(ValidationError $error): void
    {
        $this->error = $error;
    }

    public function getError(): ValidationError
    {
        return $this->error;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return (new ErrorFormatter())->format($this->error);
    }
}

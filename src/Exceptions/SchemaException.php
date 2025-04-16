<?php

declare(strict_types=1);

namespace Cortex\JsonSchema\Exceptions;

use Exception;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;

class SchemaException extends Exception
{
    protected ValidationError $error;

    public static function failedValidation(ValidationError $validationError): self
    {
        $exception = new self(
            (new ErrorFormatter())->formatErrorMessage($validationError),
        );

        $exception->setError($validationError);

        return $exception;
    }

    public function setError(ValidationError $validationError): void
    {
        $this->error = $validationError;
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

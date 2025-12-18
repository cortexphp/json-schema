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
     * @return array<string, string|array<string, string>>
     */
    public function getErrors(): array
    {
        $errorFormatter = new ErrorFormatter();

        /** @var array<string, string|array<string, string>> $errors */
        $errors = $errorFormatter->format($this->error);

        return $errors;
    }

    /**
     * Get the first property path that failed validation.
     * Returns the property path without the leading slash (e.g., 'email' instead of '/email').
     */
    public function getProperty(): ?string
    {
        $errors = $this->getErrors();

        if (empty($errors)) {
            return null;
        }

        $firstPath = array_key_first($errors);

        // Remove leading slash for cleaner output
        return ltrim((string) $firstPath, '/');
    }

    /**
     * Get the first property path that failed validation with the leading slash.
     * Returns the full path as used in the errors array (e.g., '/email').
     */
    public function getPropertyPath(): ?string
    {
        $errors = $this->getErrors();

        if (empty($errors)) {
            return null;
        }

        return (string) array_key_first($errors);
    }
}

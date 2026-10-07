<?php

declare(strict_types=1);

namespace app\exceptions;

/**
 * Input failed validation. Carries field errors for the API envelope.
 */
final class ValidationException extends DomainException
{
    /**
     * @param array<string, list<string>|string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed.',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        $normalized = [];
        foreach ($this->errors as $attribute => $messages) {
            $normalized[(string) $attribute] = array_values((array) $messages);
        }

        return $normalized;
    }
}

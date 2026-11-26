<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

/**
 * A value typed in the feedback form is not valid; the field says which.
 */
final class InvalidFeedback extends FeedbackError
{
    private function __construct(
        public readonly FeedbackField $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * The error for one field.
     */
    public static function of(FeedbackField $field, string $reason): self
    {
        return new self($field, "Invalid {$field->value}: {$reason}.");
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Feedback;

/**
 * A feedback message as the form sends it: the subject's identifier and the text.
 */
final readonly class FeedbackInput
{
    public function __construct(
        public string $subject,
        public string $message,
    ) {}
}

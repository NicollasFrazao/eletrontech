<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

/**
 * A message a person sends to the administrators about the application: a subject and a text of up to
 * 500 characters, as the 2015 form allowed.
 */
final readonly class Feedback
{
    /**
     * The longest message, in characters.
     */
    private const int MESSAGE_LENGTH = 500;

    private function __construct(
        public FeedbackSubject $subject,
        public string $message,
    ) {}

    /**
     * Reads the message as typed: the subject by its identifier, the text without outer spaces.
     *
     * @throws InvalidFeedback When the subject is not one of the list, or the text is empty or too long.
     */
    public static function from(string $subject, string $message): self
    {
        $chosen = FeedbackSubject::tryFrom($subject)
            ?? throw InvalidFeedback::of(FeedbackField::Subject, 'one of the listed subjects');
        $message = trim($message);

        if ($message === '' || mb_strlen($message) > self::MESSAGE_LENGTH) {
            throw InvalidFeedback::of(FeedbackField::Message, 'from 1 to 500 characters');
        }

        return new self($chosen, $message);
    }
}

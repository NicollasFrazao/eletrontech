<?php

declare(strict_types=1);

namespace Tests\Support\Feedback;

use App\Domain\Account\AccountId;
use App\Domain\Feedback\Feedback;
use App\Domain\Feedback\FeedbackBox;
use DateTimeImmutable;

/**
 * The feedback box in memory, for the unit tests: each message with its author and moment.
 */
final class InMemoryFeedbackBox implements FeedbackBox
{
    /**
     * @var list<array{author: AccountId, feedback: Feedback, sentAt: DateTimeImmutable}>
     */
    public array $messages = [];

    public function add(AccountId $author, Feedback $feedback, DateTimeImmutable $sentAt): void
    {
        $this->messages[] = ['author' => $author, 'feedback' => $feedback, 'sentAt' => $sentAt];
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Feedback\Feedback;
use App\Domain\Feedback\FeedbackBox;
use App\Infrastructure\Persistence\Eloquent\FeedbackRecord;
use DateTimeImmutable;

/**
 * The feedback messages kept in `feedback`.
 */
final readonly class EloquentFeedbackBox implements FeedbackBox
{
    /**
     * Writes one row for the message.
     */
    public function add(AccountId $author, Feedback $feedback, DateTimeImmutable $sentAt): void
    {
        FeedbackRecord::query()->create([
            'user_id' => $author->value,
            'subject' => $feedback->subject->value,
            'message' => $feedback->message,
            'sent_at' => $sentAt,
        ]);
    }
}

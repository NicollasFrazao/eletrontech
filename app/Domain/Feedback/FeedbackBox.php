<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

use App\Domain\Account\AccountId;
use DateTimeImmutable;

/**
 * Where the feedback messages are kept for the administrators to read.
 */
interface FeedbackBox
{
    /**
     * Keeps a message with the account that sent it and when.
     */
    public function add(AccountId $author, Feedback $feedback, DateTimeImmutable $sentAt): void;
}

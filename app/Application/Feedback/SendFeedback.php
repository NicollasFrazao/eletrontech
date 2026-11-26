<?php

declare(strict_types=1);

namespace App\Application\Feedback;

use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Feedback\Feedback;
use App\Domain\Feedback\FeedbackBox;
use App\Domain\Feedback\InvalidFeedback;

/**
 * Sends a person's feedback to the administrators: it is kept with their account and the moment it was sent.
 */
final readonly class SendFeedback
{
    public function __construct(
        private FeedbackBox $box,
        private Clock $clock,
    ) {}

    /**
     * Keeps the message.
     *
     * @throws InvalidFeedback When a value is not valid; nothing is kept.
     */
    public function handle(AccountId $author, FeedbackInput $input): void
    {
        $this->box->add($author, Feedback::from($input->subject, $input->message), $this->clock->now());
    }
}

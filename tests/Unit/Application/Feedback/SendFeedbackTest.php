<?php

declare(strict_types=1);

use App\Application\Feedback\FeedbackInput;
use App\Application\Feedback\SendFeedback;
use App\Domain\Account\AccountId;
use App\Domain\Feedback\FeedbackSubject;
use App\Domain\Feedback\InvalidFeedback;
use Tests\Support\Account\FixedClock;
use Tests\Support\Feedback\InMemoryFeedbackBox;

it('keeps the message with its author and the moment it was sent', function (): void {
    $box = new InMemoryFeedbackBox;
    $clock = new FixedClock(new DateTimeImmutable('2026-11-29 15:45:00'));

    new SendFeedback($box, $clock)->handle(new AccountId(7), new FeedbackInput('bug', 'The export button does nothing.'));

    expect($box->messages)->toHaveCount(1)
        ->and($box->messages[0]['author']->value)->toBe(7)
        ->and($box->messages[0]['feedback']->subject)->toBe(FeedbackSubject::Bug)
        ->and($box->messages[0]['feedback']->message)->toBe('The export button does nothing.')
        ->and($box->messages[0]['sentAt']->format('Y-m-d H:i:s'))->toBe('2026-11-29 15:45:00');
});

it('keeps nothing when a value is refused', function (): void {
    $box = new InMemoryFeedbackBox;

    expect(fn () => new SendFeedback($box, new FixedClock)->handle(new AccountId(7), new FeedbackInput('bug', '')))
        ->toThrow(InvalidFeedback::class);
    expect($box->messages)->toBe([]);
});

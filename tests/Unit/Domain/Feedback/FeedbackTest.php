<?php

declare(strict_types=1);

use App\Domain\Feedback\Feedback;
use App\Domain\Feedback\FeedbackField;
use App\Domain\Feedback\FeedbackSubject;
use App\Domain\Feedback\InvalidFeedback;

it('reads a subject by its identifier and the message without outer spaces', function (): void {
    $feedback = Feedback::from('suggestion', "  A dark theme for the calculators, please.\n");

    expect($feedback->subject)->toBe(FeedbackSubject::Suggestion)
        ->and($feedback->message)->toBe('A dark theme for the calculators, please.');
});

it('offers the seven subjects of the 2015 form', function (): void {
    expect(array_map(static fn (FeedbackSubject $subject): string => $subject->value, FeedbackSubject::cases()))
        ->toBe(['question', 'praise', 'bug', 'interface', 'performance', 'suggestion', 'other']);
});

it('accepts a message of 1 and of 500 characters', function (string $message): void {
    expect(Feedback::from('other', $message)->message)->toBe($message);
})->with([
    'one' => ['a'],
    'five hundred, multi-byte' => [str_repeat('ç', 500)],
]);

it('refuses an unknown subject, and an empty or too long message, naming the field', function (
    string $subject,
    string $message,
    FeedbackField $field,
): void {
    expect(fn (): Feedback => Feedback::from($subject, $message))
        ->toThrow(function (InvalidFeedback $error) use ($field): void {
            expect($error->field)->toBe($field);
        });
})->with([
    'no subject' => ['', 'Hello', FeedbackField::Subject],
    'unknown subject' => ['complaint', 'Hello', FeedbackField::Subject],
    'empty message' => ['question', '', FeedbackField::Message],
    'only spaces' => ['question', "   \n ", FeedbackField::Message],
    '501 characters' => ['question', str_repeat('a', 501), FeedbackField::Message],
]);

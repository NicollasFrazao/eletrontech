<?php

declare(strict_types=1);

use App\Domain\Agenda\EventDetails;
use App\Domain\Agenda\EventField;
use App\Domain\Agenda\EventId;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\InvalidEvent;

it('reads the details as typed, with a date and time from a form or written out', function (string $moment): void {
    $details = EventDetails::from('  Site visit ', $moment, '  Bring the meter ');

    expect($details->title)->toBe('Site visit')
        ->and($details->startsAt)->toEqual(new DateTimeImmutable('2026-12-01 14:30'))
        ->and($details->description)->toBe('Bring the meter');
})->with(['2026-12-01 14:30', '2026-12-01T14:30']);

it('refuses each value it cannot accept, naming the field', function (
    string $title,
    string $moment,
    string $description,
    EventField $field,
): void {
    try {
        EventDetails::from($title, $moment, $description);
    } catch (InvalidEvent $error) {
        expect($error->field)->toBe($field);

        return;
    }

    throw new LogicException('The event was accepted.');
})->with([
    'no title' => [' ', '2026-12-01 14:30', '', EventField::Title],
    'long title' => [str_repeat('a', 51), '2026-12-01 14:30', '', EventField::Title],
    'no moment' => ['Visit', '', '', EventField::Moment],
    'a day that does not exist' => ['Visit', '2026-02-30 10:00', '', EventField::Moment],
    'an hour that does not exist' => ['Visit', '2026-12-01 25:00', '', EventField::Moment],
    'a date without time' => ['Visit', '2026-12-01', '', EventField::Moment],
    'long description' => ['Visit', '2026-12-01 14:30', str_repeat('a', 501), EventField::Description],
]);

it('has no event with an id below 1', function (): void {
    new EventId(0);
})->throws(EventNotFound::class);

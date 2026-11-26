<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

use DateTimeImmutable;

/**
 * What a person writes about an event (the 2015 `tb_evento`): a title of up to 50 characters, the date
 * and time, and a description of up to 500.
 */
final readonly class EventDetails
{
    /**
     * The longest title, in characters.
     */
    private const int TITLE_LENGTH = 50;

    /**
     * The longest description, in characters.
     */
    private const int DESCRIPTION_LENGTH = 500;

    private function __construct(
        public string $title,
        public DateTimeImmutable $startsAt,
        public string $description,
    ) {}

    /**
     * Reads the details as typed: the title and description without outer spaces, the moment as
     * "YYYY-MM-DD HH:MM".
     *
     * @throws InvalidEvent When the title is empty or too long, the moment is not a valid date and time,
     *                      or the description is too long.
     */
    public static function from(string $title, string $startsAt, string $description): self
    {
        $title = trim($title);
        $description = trim($description);

        if ($title === '' || mb_strlen($title) > self::TITLE_LENGTH) {
            throw InvalidEvent::of(EventField::Title, 'from 1 to 50 characters');
        }

        if (mb_strlen($description) > self::DESCRIPTION_LENGTH) {
            throw InvalidEvent::of(EventField::Description, 'at most 500 characters');
        }

        return new self($title, self::moment($startsAt), $description);
    }

    /**
     * The date and time typed, which must exist on the calendar.
     *
     * @throws InvalidEvent When it is not a valid "YYYY-MM-DD HH:MM".
     */
    private static function moment(string $text): DateTimeImmutable
    {
        $moment = DateTimeImmutable::createFromFormat('!Y-m-d H:i', str_replace('T', ' ', trim($text)));

        if ($moment === false || $moment->format('Y-m-d H:i') !== str_replace('T', ' ', trim($text))) {
            throw InvalidEvent::of(EventField::Moment, 'a date and time such as 2026-12-01 14:30');
        }

        return $moment;
    }
}

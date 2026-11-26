<?php

declare(strict_types=1);

namespace App\Application\Agenda;

use App\Domain\Account\AccountId;
use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventId;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\EventRepository;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * The owner's events: one month, a search, or one event.
 */
final readonly class ShowAgenda
{
    public function __construct(
        private EventRepository $events,
    ) {}

    /**
     * The events of a month ("YYYY-MM"), by date and time.
     *
     * @return list<Event>
     *
     * @throws InvalidArgumentException When the month is not "YYYY-MM".
     */
    public function month(AccountId $owner, string $month): array
    {
        $first = DateTimeImmutable::createFromFormat('!Y-m', $month);

        if ($first === false || $first->format('Y-m') !== $month) {
            throw new InvalidArgumentException("\"{$month}\" is not a month.");
        }

        return $this->events->between($owner, $first, $first->modify('first day of next month'));
    }

    /**
     * The events whose title or description holds the term; none for a blank term.
     *
     * @return list<Event>
     */
    public function search(AccountId $owner, string $term): array
    {
        $term = trim($term);

        return $term === '' ? [] : $this->events->search($owner, $term);
    }

    /**
     * One of the owner's events.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function event(AccountId $owner, int $id): Event
    {
        return $this->events->get($owner, new EventId($id));
    }
}

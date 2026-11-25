<?php

declare(strict_types=1);

namespace Tests\Support\Agenda;

use App\Domain\Account\AccountId;
use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventDetails;
use App\Domain\Agenda\EventId;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\EventRepository;
use DateTimeImmutable;

/**
 * Events kept in memory with their owners.
 */
final class InMemoryEventRepository implements EventRepository
{
    /**
     * @var array<int, array{int, Event}> Owner and event, keyed by id.
     */
    private array $events = [];

    public function add(AccountId $owner, EventDetails $details): EventId
    {
        $id = new EventId(count($this->events) + 1);
        $this->events[$id->value] = [$owner->value, new Event($id, $details)];

        return $id;
    }

    public function get(AccountId $owner, EventId $id): Event
    {
        [$holder, $event] = $this->events[$id->value] ?? [0, null];

        return $holder === $owner->value && $event !== null ? $event : throw EventNotFound::withId($id->value);
    }

    public function update(AccountId $owner, EventId $id, EventDetails $details): void
    {
        $this->get($owner, $id);
        $this->events[$id->value] = [$owner->value, new Event($id, $details)];
    }

    public function delete(AccountId $owner, EventId $id): void
    {
        $this->get($owner, $id);
        unset($this->events[$id->value]);
    }

    public function between(AccountId $owner, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return array_values(array_filter(
            $this->all($owner),
            static fn (Event $event): bool => $event->details->startsAt >= $from && $event->details->startsAt < $to,
        ));
    }

    public function search(AccountId $owner, string $term): array
    {
        return array_values(array_filter(
            $this->all($owner),
            static fn (Event $event): bool => mb_stripos($event->details->title.' '.$event->details->description, $term) !== false,
        ));
    }

    public function all(AccountId $owner): array
    {
        $events = [];

        foreach ($this->events as [$holder, $event]) {
            if ($holder === $owner->value) {
                $events[] = $event;
            }
        }

        usort($events, static fn (Event $a, Event $b): int => $a->details->startsAt <=> $b->details->startsAt);

        return $events;
    }
}

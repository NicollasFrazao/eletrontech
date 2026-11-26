<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventDetails;
use App\Domain\Agenda\EventId;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\EventRepository;
use App\Domain\Agenda\InvalidEvent;
use App\Infrastructure\Persistence\Eloquent\EventRecord;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use UnexpectedValueException;

/**
 * The events kept in `events`; every query is limited to the owner's rows.
 */
final readonly class EloquentEventRepository implements EventRepository
{
    /**
     * Writes the new row.
     *
     * @throws UnexpectedValueException When the database gives the row an id below 1.
     */
    public function add(AccountId $owner, EventDetails $details): EventId
    {
        $record = EventRecord::query()->create(['user_id' => $owner->value, ...$this->columns($details)]);

        try {
            return new EventId($record->id);
        } catch (EventNotFound $error) {
            throw new UnexpectedValueException('The database gave an event an id below 1.', 0, $error);
        }
    }

    /**
     * The owner's event with that id.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function get(AccountId $owner, EventId $id): Event
    {
        return $this->event($this->record($owner, $id));
    }

    /**
     * Rewrites the owner's row.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function update(AccountId $owner, EventId $id, EventDetails $details): void
    {
        $this->record($owner, $id)->update($this->columns($details));
    }

    /**
     * Deletes the owner's row.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function delete(AccountId $owner, EventId $id): void
    {
        $this->record($owner, $id)->delete();
    }

    /**
     * The owner's events in the period, by moment.
     *
     * @return list<Event>
     */
    public function between(AccountId $owner, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->events($this->owned($owner)->where('starts_at', '>=', $from)->where('starts_at', '<', $to));
    }

    /**
     * The owner's events whose title or description holds the term, by moment.
     *
     * @return list<Event>
     */
    public function search(AccountId $owner, string $term): array
    {
        $pattern = '%'.addcslashes($term, '\\%_').'%';

        return $this->events($this->owned($owner)->where(
            static fn (Builder $query): Builder => $query->whereLike('title', $pattern)->orWhereLike('description', $pattern),
        ));
    }

    /**
     * Every event of the owner, by moment.
     *
     * @return list<Event>
     */
    public function all(AccountId $owner): array
    {
        return $this->events($this->owned($owner));
    }

    /**
     * The owner's rows.
     *
     * @return Builder<EventRecord>
     */
    private function owned(AccountId $owner): Builder
    {
        return EventRecord::query()->where('user_id', $owner->value);
    }

    /**
     * The owner's row with that id.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    private function record(AccountId $owner, EventId $id): EventRecord
    {
        return $this->owned($owner)->find($id->value) ?? throw EventNotFound::withId($id->value);
    }

    /**
     * The events of the rows, by moment then by id.
     *
     * @param  Builder<EventRecord>  $query
     *
     * @return list<Event>
     */
    private function events(Builder $query): array
    {
        return array_values($query->orderBy('starts_at')->orderBy('id')->get()->map($this->event(...))->all());
    }

    /**
     * The columns of the details.
     *
     * @return array{title: string, starts_at: DateTimeImmutable, description: string}
     */
    private function columns(EventDetails $details): array
    {
        return ['title' => $details->title, 'starts_at' => $details->startsAt, 'description' => $details->description];
    }

    /**
     * The event a row stands for.
     *
     * @throws UnexpectedValueException When the row holds a value no form writes.
     */
    private function event(EventRecord $record): Event
    {
        try {
            return new Event(
                new EventId($record->id),
                EventDetails::from($record->title, $record->starts_at->format('Y-m-d H:i'), $record->description),
            );
        } catch (InvalidEvent|EventNotFound $error) {
            throw new UnexpectedValueException("The event {$record->id} holds a value no form writes.", 0, $error);
        }
    }
}

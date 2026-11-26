<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

use App\Domain\Account\AccountId;
use DateTimeImmutable;

/**
 * Each person's events. Every method takes the owner: another person's event is not found.
 */
interface EventRepository
{
    /**
     * Stores a new event of the owner.
     */
    public function add(AccountId $owner, EventDetails $details): EventId;

    /**
     * One of the owner's events.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function get(AccountId $owner, EventId $id): Event;

    /**
     * Replaces the details of one of the owner's events.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function update(AccountId $owner, EventId $id, EventDetails $details): void;

    /**
     * Deletes one of the owner's events.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function delete(AccountId $owner, EventId $id): void;

    /**
     * The owner's events from one moment (included) to another (excluded), by date and time.
     *
     * @return list<Event>
     */
    public function between(AccountId $owner, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * The owner's events whose title or description holds the term, ignoring case, by date and time.
     *
     * @return list<Event>
     */
    public function search(AccountId $owner, string $term): array;

    /**
     * Every event of the owner, by date and time.
     *
     * @return list<Event>
     */
    public function all(AccountId $owner): array;
}

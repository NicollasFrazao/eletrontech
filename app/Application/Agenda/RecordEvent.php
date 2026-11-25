<?php

declare(strict_types=1);

namespace App\Application\Agenda;

use App\Domain\Account\AccountId;
use App\Domain\Agenda\EventDetails;
use App\Domain\Agenda\EventId;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\EventRepository;
use App\Domain\Agenda\InvalidEvent;

/**
 * Writes an event of the owner: a new one, or new details for one they have.
 */
final readonly class RecordEvent
{
    public function __construct(
        private EventRepository $events,
    ) {}

    /**
     * Stores a new event.
     *
     * @throws InvalidEvent When a value is not valid.
     */
    public function add(AccountId $owner, EventInput $input): EventId
    {
        return $this->events->add($owner, $this->details($input));
    }

    /**
     * Replaces the details of one of the owner's events.
     *
     * @throws InvalidEvent When a value is not valid.
     * @throws EventNotFound When it is not the owner's.
     */
    public function update(AccountId $owner, int $id, EventInput $input): void
    {
        $this->events->update($owner, new EventId($id), $this->details($input));
    }

    /**
     * Deletes one of the owner's events.
     *
     * @throws EventNotFound When it is not the owner's.
     */
    public function delete(AccountId $owner, int $id): void
    {
        $this->events->delete($owner, new EventId($id));
    }

    /**
     * The details the input describes.
     *
     * @throws InvalidEvent When a value is not valid.
     */
    private function details(EventInput $input): EventDetails
    {
        return EventDetails::from($input->title, $input->startsAt, $input->description);
    }
}

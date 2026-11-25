<?php

declare(strict_types=1);

use App\Application\Agenda\EventInput;
use App\Application\Agenda\ExportEvents;
use App\Application\Agenda\RecordEvent;
use App\Application\Agenda\ShowAgenda;
use App\Application\Files\FileWriter;
use App\Domain\Account\AccountId;
use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\ExportFormat;
use App\Domain\Files\FileId;
use App\Domain\Files\NameTaken;
use Tests\Support\Account\FixedClock;
use Tests\Support\Agenda\InMemoryEventRepository;
use Tests\Support\Agenda\TitlesDocument;
use Tests\Support\Files\InMemoryFileIndex;
use Tests\Support\Files\InMemoryFileStore;

/**
 * The titles of the events.
 *
 * @param  list<Event>  $events
 *
 * @return list<string>
 */
function titles(array $events): array
{
    return array_map(static fn (Event $event): string => $event->details->title, $events);
}

it('adds, edits and deletes the owner\'s events, and finds no other person\'s', function (): void {
    $events = new InMemoryEventRepository;
    $record = new RecordEvent($events);
    $ana = new AccountId(1);
    $id = $record->add($ana, new EventInput('Visit', '2026-12-01 14:30', ''))->value;

    $record->update($ana, $id, new EventInput('Site visit', '2026-12-02 09:00', 'Meter'));

    expect(new ShowAgenda($events)->event($ana, $id)->details->title)->toBe('Site visit')
        ->and(static fn () => $record->update(new AccountId(2), $id, new EventInput('Mine', '2026-12-02 09:00', '')))
        ->toThrow(EventNotFound::class)
        ->and(static fn () => $record->delete(new AccountId(2), $id))->toThrow(EventNotFound::class);

    $record->delete($ana, $id);

    expect(static fn (): Event => new ShowAgenda($events)->event($ana, $id))->toThrow(EventNotFound::class);
});

it('shows a month and a search, by date and time', function (): void {
    $events = new InMemoryEventRepository;
    $record = new RecordEvent($events);
    $ana = new AccountId(1);
    $record->add($ana, new EventInput('Second', '2026-12-31 23:59', 'panel'));
    $record->add($ana, new EventInput('First', '2026-12-01 00:00', ''));
    $record->add($ana, new EventInput('January', '2027-01-01 00:00', 'Panel'));
    $record->add(new AccountId(2), new EventInput('Bia', '2026-12-10 10:00', 'panel'));
    $agenda = new ShowAgenda($events);

    expect(titles($agenda->month($ana, '2026-12')))->toBe(['First', 'Second'])
        ->and(titles($agenda->search($ana, 'PANEL')))->toBe(['Second', 'January'])
        ->and($agenda->search($ana, '  '))->toBe([])
        ->and(static fn (): array => $agenda->month($ana, '2026-13'))->toThrow(InvalidArgumentException::class);
});

it('exports the events to a file in the folder "Events", once a second', function (): void {
    $events = new InMemoryEventRepository;
    $index = new InMemoryFileIndex;
    $store = new InMemoryFileStore;
    $ana = new AccountId(1);
    new RecordEvent($events)->add($ana, new EventInput('Visit', '2026-12-01 14:30', ''));
    $export = new ExportEvents($events, new TitlesDocument, new FileWriter($index, $store), new FixedClock);

    $file = $index->file($ana, $export->handle($ana, ExportFormat::Text));
    $pdf = $index->file($ana, $export->handle($ana, ExportFormat::Pdf));

    expect($file->entry->name->value)->toBe('events-2026-11-25-100000.txt')
        ->and($index->folder($ana, $file->entry->folder ?? throw new LogicException)->name->value)->toBe('Events')
        ->and($store->read($file->entry->contentKey))->toBe('txt:Visit')
        ->and($pdf->entry->name->value)->toBe('events-2026-11-25-100000.pdf')
        ->and(static fn (): FileId => $export->handle($ana, ExportFormat::Text))->toThrow(NameTaken::class);
});

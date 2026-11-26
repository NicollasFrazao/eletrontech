<?php

declare(strict_types=1);

namespace Tests\Support\Agenda;

use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventDocument;
use App\Domain\Agenda\ExportFormat;

/**
 * A "document" that lists the format and the titles, so a unit test reads what was exported.
 */
final class TitlesDocument implements EventDocument
{
    public function render(array $events, ExportFormat $format): string
    {
        return $format->value.':'.implode(',', array_map(static fn (Event $event): string => $event->details->title, $events));
    }
}

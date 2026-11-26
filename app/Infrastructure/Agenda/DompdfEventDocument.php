<?php

declare(strict_types=1);

namespace App\Infrastructure\Agenda;

use App\Domain\Agenda\Event;
use App\Domain\Agenda\EventDocument;
use App\Domain\Agenda\ExportFormat;
use Dompdf\Dompdf;
use Illuminate\Contracts\View\Factory;

/**
 * The events as a PDF made with Dompdf from a Blade page (`exports/events`), or as plain text. Dompdf
 * replaces the mPDF of 2015: it is LGPL, so it fits the MIT licence, and it reads no remote files.
 */
final readonly class DompdfEventDocument implements EventDocument
{
    public function __construct(
        private Factory $views,
    ) {}

    /**
     * The document's bytes.
     *
     * @param  list<Event>  $events
     */
    public function render(array $events, ExportFormat $format): string
    {
        return $format === ExportFormat::Pdf ? $this->pdf($events) : $this->text($events);
    }

    /**
     * A PDF of the events.
     *
     * @param  list<Event>  $events
     */
    private function pdf(array $events): string
    {
        $pdf = new Dompdf(['isRemoteEnabled' => false, 'isPhpEnabled' => false]);
        $pdf->loadHtml($this->views->make('exports.events', ['events' => $events])->render());
        $pdf->render();

        return $pdf->output();
    }

    /**
     * The events as text, one block each.
     *
     * @param  list<Event>  $events
     */
    private function text(array $events): string
    {
        $blocks = array_map(
            static fn (Event $event): string => $event->details->startsAt->format('Y-m-d H:i').' - '.$event->details->title
                .($event->details->description === '' ? '' : "\n".$event->details->description),
            $events,
        );

        return implode("\n\n", $blocks)."\n";
    }
}

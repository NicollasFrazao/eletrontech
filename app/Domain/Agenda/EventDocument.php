<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * Writes a person's events as a document to keep (2015 exported them as PDF or TXT with mPDF).
 */
interface EventDocument
{
    /**
     * The content of the document with the events, in the format.
     *
     * @param  list<Event>  $events
     */
    public function render(array $events, ExportFormat $format): string;
}

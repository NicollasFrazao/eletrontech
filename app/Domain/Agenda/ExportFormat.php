<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

/**
 * The formats the events can be exported to.
 */
enum ExportFormat: string
{
    case Pdf = 'pdf';
    case Text = 'txt';
}

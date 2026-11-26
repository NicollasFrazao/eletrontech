<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Feedback;

use Filament\Resources\Pages\ListRecords;

/**
 * The list of the feedback messages, newest first.
 */
final class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;
}

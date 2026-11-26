<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Tools;

use Filament\Resources\Pages\ListRecords;

/**
 * The list of the tools, searched by name or identifier.
 */
final class ListTools extends ListRecords
{
    protected static string $resource = ToolResource::class;
}

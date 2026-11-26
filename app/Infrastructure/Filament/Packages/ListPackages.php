<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Packages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Override;

/**
 * The list of the free and ready-made packages, with the button that makes a new one.
 */
final class ListPackages extends ListRecords
{
    protected static string $resource = PackageResource::class;

    /**
     * The create button.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

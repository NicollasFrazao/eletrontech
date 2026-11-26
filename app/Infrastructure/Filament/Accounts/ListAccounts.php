<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Accounts;

use Filament\Resources\Pages\ListRecords;

/**
 * The list of open accounts, with the search box and the actions of each row.
 */
final class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;
}

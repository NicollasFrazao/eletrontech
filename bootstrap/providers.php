<?php

declare(strict_types=1);

use App\Providers\AccountServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilesServiceProvider;
use App\Providers\LicensingServiceProvider;

return [
    AppServiceProvider::class,
    AccountServiceProvider::class,
    LicensingServiceProvider::class,
    FilesServiceProvider::class,
    AdminPanelProvider::class,
];

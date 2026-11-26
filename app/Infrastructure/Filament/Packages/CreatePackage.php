<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Packages;

use App\Application\Administration\CreatePackage as CreatePackageUseCase;
use App\Domain\Licensing\LicensingError;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Override;

/**
 * The page that adds a ready-made package to the shop, through the CreatePackage use case.
 */
final class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    /**
     * Stores the package through the use case; a refused value is shown on its field.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException When a value is refused.
     */
    #[Override]
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $id = App::make(CreatePackageUseCase::class)->handle(PackageForm::input($data));
        } catch (LicensingError $refused) {
            throw PackageForm::refused($refused);
        }

        return PackageRecord::query()->findOrFail($id->value);
    }
}

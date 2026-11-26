<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Packages;

use App\Application\Administration\ChangePackage;
use App\Domain\Licensing\LicensingError;
use App\Domain\Licensing\UnknownPackage;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use LogicException;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The edit page of a package: its name, its tools and, for a ready-made one, its price and days, saved
 * by the ChangePackage use case.
 */
final class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    /**
     * The row's values with the tools to tick, the everyday ones left out.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     *
     * @throws LogicException When the record is not a package's row.
     */
    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'tools' => PackageForm::pickedTools($this->package($this->getRecord()))];
    }

    /**
     * Saves the package through the use case; a refused value is shown on its field.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException When a value is refused.
     * @throws NotFoundHttpException When the package is not one the panel changes.
     * @throws LogicException When the record is not a package's row.
     */
    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $package = $this->package($record);

        try {
            App::make(ChangePackage::class)->handle($package->id, PackageForm::input($data));
        } catch (UnknownPackage $unknown) {
            throw new NotFoundHttpException(message: $unknown->getMessage(), previous: $unknown);
        } catch (LicensingError $refused) {
            throw PackageForm::refused($refused);
        }

        return $package->refresh();
    }

    /**
     * The record as a package's row.
     *
     * @throws LogicException When it is not one.
     */
    private function package(Model $record): PackageRecord
    {
        if (! $record instanceof PackageRecord) {
            throw new LogicException('The package pages edit the rows of packages only.');
        }

        return $record;
    }
}

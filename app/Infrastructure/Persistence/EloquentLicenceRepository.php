<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\UnknownPackage;
use App\Infrastructure\Persistence\Eloquent\LicenceRecord;
use UnexpectedValueException;

/**
 * The licences kept in `licences`, one row per person.
 */
final readonly class EloquentLicenceRepository implements LicenceRepository
{
    public function __construct(
        private PackageRepository $packages,
    ) {}

    /**
     * The person's licence with its package, or null.
     *
     * @throws UnexpectedValueException When the row's package is gone, which the foreign key prevents.
     */
    public function forHolder(AccountId $holder): ?Licence
    {
        $record = LicenceRecord::query()->find($holder->value);

        if ($record === null) {
            return null;
        }

        try {
            $package = $this->packages->get(new PackageId($record->package_id));
        } catch (UnknownPackage $error) {
            throw new UnexpectedValueException("The licence of {$holder->value} is for a missing package.", 0, $error);
        }

        return new Licence($holder, $package, $record->starts_at->toDateTimeImmutable(), $record->ends_at->toDateTimeImmutable());
    }

    /**
     * Writes the person's row, replacing the previous licence.
     */
    public function save(Licence $licence): void
    {
        LicenceRecord::query()->updateOrCreate(
            ['user_id' => $licence->holder->value],
            [
                'package_id' => $licence->package->id->value,
                'starts_at' => $licence->startsAt,
                'ends_at' => $licence->endsAt,
            ],
        );
    }
}

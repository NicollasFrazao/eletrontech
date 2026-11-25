<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\PackageNotForSale;
use App\Domain\Licensing\PackageRepository;
use UnexpectedValueException;

/**
 * Gives a new account its first licence: the free package for seven days (as in 2015).
 */
final readonly class GrantInitialLicence
{
    public function __construct(
        private PackageRepository $packages,
        private LicenceRepository $licences,
        private Clock $clock,
    ) {}

    /**
     * Stores the initial licence of the account.
     *
     * @throws UnexpectedValueException When the storage's free package is not of the free kind.
     */
    public function handle(AccountId $account): void
    {
        try {
            $licence = Licence::initial($account, $this->packages->free(), $this->clock->now());
        } catch (PackageNotForSale $error) {
            throw new UnexpectedValueException('The free package of the storage is not free.', 0, $error);
        }

        $this->licences->save($licence);
    }
}

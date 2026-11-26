<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Application\Licensing\BuyPackage;
use App\Application\Licensing\PurchaseConfirmation;
use App\Application\Licensing\PurchaseInput;
use App\Domain\Account\AccountId;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use App\Infrastructure\Persistence\Eloquent\User;
use Tests\Support\Account\SignedUp;

/**
 * Accounts with a licence, made through the real use cases, for the feature tests.
 */
final class Licensed
{
    /**
     * A verified account (ana@example.com, "correct horse") that bought the Golden package, every tool,
     * for the days given.
     */
    public static function golden(int $days = 30): User
    {
        $user = SignedUp::verified();
        self::buy($user, self::packageId('Golden'), $days);

        return $user;
    }

    /**
     * The id of the package with that name.
     */
    public static function packageId(string $name): int
    {
        return PackageRecord::query()->where('name', $name)->whereNull('owner_id')->sole()->id;
    }

    /**
     * Buys a ready-made package for the account.
     */
    public static function buy(User $user, int $package, int $days): void
    {
        app(BuyPackage::class)->handle(new PurchaseInput(
            new AccountId($user->id),
            $package,
            [],
            new PurchaseConfirmation($days, 'correct horse'),
        ));
    }
}

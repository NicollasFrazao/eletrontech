<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Account\ActivityRecorder;
use App\Application\Account\RegisterAccount;
use App\Application\Account\SignUp;
use App\Application\Licensing\AuthoriseToolUse;
use App\Application\Licensing\BuyPackage;
use App\Application\Licensing\GrantInitialLicence;
use App\Application\Licensing\PasswordConfirmation;
use App\Application\Licensing\RecordToolUse;
use App\Application\Licensing\ShowLicence;
use App\Application\Licensing\ShowShop;
use App\Application\Licensing\ToolAccess;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\Clock;
use App\Domain\Account\PasswordHasher;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\ToolUseLog;
use App\Infrastructure\Persistence\EloquentLicenceRepository;
use App\Infrastructure\Persistence\EloquentPackageRepository;
use App\Infrastructure\Persistence\EloquentToolRepository;
use App\Infrastructure\Persistence\EloquentToolUseLog;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use UnexpectedValueException;

/**
 * Composition root of the packages and licences: binds the ports of `App\Domain\Licensing`, builds the
 * use cases, and defines the `use-tool` gate every tool route checks.
 */
final class LicensingServiceProvider extends ServiceProvider
{
    /**
     * The ability a tool route requires, with the tool's identifier: `can:use-tool,calculator`.
     */
    public const string USE_TOOL = 'use-tool';

    /**
     * Binds the storage of tools, packages, licences and uses, and the use cases.
     */
    public function register(): void
    {
        $this->app->bind(ToolRepository::class, EloquentToolRepository::class);
        $this->app->bind(ToolUseLog::class, EloquentToolUseLog::class);
        $this->app->bind(
            PackageRepository::class,
            static fn (Application $app): PackageRepository => new EloquentPackageRepository($app->make(ConnectionInterface::class)),
        );
        $this->app->bind(
            LicenceRepository::class,
            static fn (Application $app): LicenceRepository => new EloquentLicenceRepository($app->make(PackageRepository::class)),
        );

        $this->app->bind(GrantInitialLicence::class, static fn (Application $app): GrantInitialLicence => new GrantInitialLicence(
            $app->make(PackageRepository::class),
            $app->make(LicenceRepository::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(SignUp::class, static fn (Application $app): SignUp => new SignUp(
            $app->make(RegisterAccount::class),
            $app->make(GrantInitialLicence::class),
        ));
        $this->app->bind(BuyPackage::class, static fn (Application $app): BuyPackage => new BuyPackage(
            new PasswordConfirmation($app->make(AccountRepository::class), $app->make(PasswordHasher::class)),
            $app->make(PackageRepository::class),
            $app->make(LicenceRepository::class),
            $app->make(ActivityRecorder::class),
        ));
        $this->app->bind(AuthoriseToolUse::class, static fn (Application $app): AuthoriseToolUse => new AuthoriseToolUse(
            $app->make(LicenceRepository::class),
            $app->make(ToolRepository::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(RecordToolUse::class, static fn (Application $app): RecordToolUse => new RecordToolUse(
            $app->make(ToolUseLog::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(ShowLicence::class, static fn (Application $app): ShowLicence => new ShowLicence(
            $app->make(LicenceRepository::class),
            $app->make(ToolRepository::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(ShowShop::class, static fn (Application $app): ShowShop => new ShowShop(
            $app->make(PackageRepository::class),
            $app->make(ToolRepository::class),
        ));
    }

    /**
     * Defines `use-tool`: allowed while the person's licence runs and covers the tool; an unknown tool
     * is not found rather than forbidden.
     */
    public function boot(): void
    {
        Gate::define(self::USE_TOOL, function (Authenticatable $user, string $tool): Response {
            $access = $this->app->make(AuthoriseToolUse::class)->handle(new AccountId($this->id($user)), $tool);

            return match ($access) {
                ToolAccess::Allowed => Response::allow(),
                ToolAccess::NotLicensed => Response::deny(__('shop.errors.not_licensed')),
                ToolAccess::UnknownTool => Response::denyAsNotFound(),
            };
        });
    }

    /**
     * The integer id the session guard keeps for the signed-in person.
     *
     * @throws UnexpectedValueException When the guard's user has no integer id.
     */
    private function id(Authenticatable $user): int
    {
        $id = $user->getAuthIdentifier();

        return is_int($id) ? $id : throw new UnexpectedValueException('The signed-in user has no integer id.');
    }
}

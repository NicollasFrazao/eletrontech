<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Account\AccountCloser;
use App\Application\Account\ActivityRecorder;
use App\Application\Account\ChangePassword;
use App\Application\Account\DeactivateAccount;
use App\Application\Account\PasswordWriter;
use App\Application\Account\SignIn;
use App\Application\Account\UpdatePreferences;
use App\Application\Account\UpdateProfile;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\Clock;
use App\Domain\Account\ImageStore;
use App\Domain\Account\PasswordHasher;
use App\Http\Controllers\Account\ProfileActions;
use App\Infrastructure\Account\FrameworkPasswordHasher;
use App\Infrastructure\Account\GdImageStore;
use App\Infrastructure\Account\SystemClock;
use App\Infrastructure\Persistence\EloquentAccountRepository;
use App\Infrastructure\Persistence\EloquentActivityLog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * Composition root of the accounts: binds the ports of `App\Domain\Account` to their implementations.
 *
 * Kept apart from AppServiceProvider so each provider stays within the coupling limit of PHPMD.
 */
final class AccountServiceProvider extends ServiceProvider
{
    /**
     * Binds the account storage, the activity log, the hasher, the clock and the picture store.
     */
    public function register(): void
    {
        $this->app->bind(AccountRepository::class, EloquentAccountRepository::class);
        $this->app->bind(ActivityLog::class, EloquentActivityLog::class);
        $this->app->bind(
            PasswordHasher::class,
            static fn (Application $app): PasswordHasher => new FrameworkPasswordHasher($app->make(Hasher::class)),
        );
        $this->app->bind(Clock::class, SystemClock::class);
        // The `local` disk is private (storage/app/private); resolved when the port is, so a test's
        // Storage::fake() takes its place.
        $this->app->bind(ImageStore::class, static fn (): ImageStore => new GdImageStore(Storage::disk('local')));

        // The services the use cases share, and the use cases that take them.
        $this->app->bind(AccountCloser::class, static fn (Application $app): AccountCloser => new AccountCloser(
            $app->make(AccountRepository::class),
            $app->make(ImageStore::class),
            $app->make(ActivityLog::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(PasswordWriter::class, static fn (Application $app): PasswordWriter => new PasswordWriter(
            $app->make(AccountRepository::class),
            $app->make(PasswordHasher::class),
            $app->make(ActivityLog::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(SignIn::class, static fn (Application $app): SignIn => new SignIn(
            $app->make(AccountRepository::class),
            $app->make(PasswordHasher::class),
            $app->make(ActivityRecorder::class),
            $app->make(AccountCloser::class),
        ));
        $this->app->bind(ActivityRecorder::class, static fn (Application $app): ActivityRecorder => new ActivityRecorder(
            $app->make(ActivityLog::class),
            $app->make(Clock::class),
        ));
        $this->app->bind(ProfileActions::class, static fn (Application $app): ProfileActions => new ProfileActions(
            $app->make(UpdateProfile::class),
            $app->make(ChangePassword::class),
            $app->make(UpdatePreferences::class),
            $app->make(DeactivateAccount::class),
        ));
    }
}

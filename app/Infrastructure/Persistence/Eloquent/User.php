<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Administration\AuthoriseAdministration;
use App\Domain\Account\AccountId;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

/**
 * The row of an account in `users`, which the framework's guard, password broker and email
 * verification read; EloquentAccountRepository maps it to the domain's Account.
 *
 * @property int $id
 * @property string $name
 * @property ?string $cpf
 * @property ?string $phone_number
 * @property ?string $phone_type
 * @property ?string $email
 * @property ?string $password
 * @property string $role
 * @property ?Carbon $email_verified_at
 * @property bool $is_night_mode
 * @property bool $shows_tutorial
 * @property ?string $photo_path
 * @property ?string $cover_path
 * @property ?Carbon $reactivation_deadline
 * @property ?Carbon $closed_at
 * @property ?string $remember_token
 */
#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use Notifiable;

    /**
     * Whether this account may enter the admin panel; Filament answers 403 when it may not.
     *
     * The decision is AuthoriseAdministration's, so the panel and any later check share one rule.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter") There is one panel, so the rule does not depend on it.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return App::make(AuthoriseAdministration::class)->handle(new AccountId($this->id));
    }

    /**
     * Casts the dates and the preferences. The password is hashed by the domain's PasswordHasher
     * before it reaches the model, so it is not cast again.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'reactivation_deadline' => 'datetime',
            'closed_at' => 'datetime',
            'is_night_mode' => 'boolean',
            'shows_tutorial' => 'boolean',
        ];
    }
}

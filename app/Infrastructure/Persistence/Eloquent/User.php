<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

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
 * @property ?Carbon $email_verified_at
 * @property bool $is_night_mode
 * @property bool $shows_tutorial
 * @property ?string $photo_path
 * @property ?string $cover_path
 * @property ?Carbon $reactivation_deadline
 * @property ?Carbon $closed_at
 * @property ?string $remember_token
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * Keeps the password hash and the remember token out of arrays and JSON.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

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

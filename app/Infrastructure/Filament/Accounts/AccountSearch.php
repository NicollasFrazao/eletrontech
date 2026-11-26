<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Accounts;

use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * How the account list reads a row: the CPF search and the status shown.
 */
final class AccountSearch
{
    /**
     * The status of an account in use.
     */
    public const string ACTIVE = 'active';

    /**
     * The status of an account waiting for its owner to sign in again.
     */
    public const string DEACTIVATED = 'deactivated';

    /**
     * Narrows the search to the CPFs that contain the digits typed: the CPF is stored as eleven
     * digits, so "529.982" finds 52998224725. A text with no digits matches no CPF.
     *
     * @param  Builder<User>  $query
     *
     * @return Builder<User>
     */
    public static function byCpf(Builder $query, string $search): Builder
    {
        $digits = preg_replace('/\D/', '', $search) ?? '';

        return $digits === ''
            ? $query->whereRaw('1 = 0')
            : $query->where('cpf', 'like', "%{$digits}%");
    }

    /**
     * ACTIVE, or DEACTIVATED while the account has a reactivation deadline.
     */
    public static function status(User $user): string
    {
        return $user->reactivation_deadline === null ? self::ACTIVE : self::DEACTIVATED;
    }
}

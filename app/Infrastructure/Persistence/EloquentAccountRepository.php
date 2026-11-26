<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\Access;
use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ContactPhone;
use App\Domain\Account\Cpf;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\HashedPassword;
use App\Domain\Account\ImageKind;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\NewAccount;
use App\Domain\Account\PersonalDetails;
use App\Domain\Account\PersonName;
use App\Domain\Account\PhoneNumber;
use App\Domain\Account\PhoneType;
use App\Domain\Account\Preferences;
use App\Domain\Account\Profile;
use App\Domain\Account\Role;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use UnexpectedValueException;

/**
 * Accounts kept in the framework's `users` table, so the guard, the password broker and the email
 * verification read the same rows. A closed account has `closed_at` set and is never found.
 */
final readonly class EloquentAccountRepository implements AccountRepository
{
    /**
     * The length of a remember-me token, as the framework makes them.
     */
    private const int REMEMBER_TOKEN_LENGTH = 60;

    /**
     * Writes the new account's row; the columns' defaults give the preferences.
     */
    public function register(NewAccount $account): AccountId
    {
        $user = new User;
        $this->fill($user, $account->details, $account->access);
        $user->save();

        return new AccountId($user->id);
    }

    /**
     * The open account with that id.
     *
     * @throws AccountNotFound When there is none, or it was closed.
     */
    public function get(AccountId $id): Account
    {
        $user = $this->open()->find($id->value) ?? throw AccountNotFound::withId($id);

        return $this->account($user);
    }

    /**
     * The open account with that email, if any.
     */
    public function findByEmail(EmailAddress $email): ?Account
    {
        $user = $this->open()->where('email', $email->value)->first();

        return $user === null ? null : $this->account($user);
    }

    /**
     * The open account with that CPF, if any.
     */
    public function findByCpf(Cpf $cpf): ?Account
    {
        $user = $this->open()->where('cpf', $cpf->digits)->first();

        return $user === null ? null : $this->account($user);
    }

    /**
     * The role in the row's `role` column, which only the database changes for now.
     *
     * @throws AccountNotFound When there is none, or it was closed.
     */
    public function roleOf(AccountId $id): Role
    {
        $user = $this->open()->find($id->value) ?? throw AccountNotFound::withId($id);

        return Role::from($user->role);
    }

    /**
     * Writes the account; a new password also replaces the remember-me token, so a "keep me signed
     * in" cookie made with the old password stops working.
     */
    public function save(Account $account): void
    {
        $user = $this->open()->findOrFail($account->id->value);
        $this->fill($user, $account->details(), $account->access());
        $user->forceFill([
            'is_night_mode' => $account->profile()->preferences->isNightMode,
            'shows_tutorial' => $account->profile()->preferences->showsTutorial,
            'photo_path' => $account->profile()->imagePath(ImageKind::Photo),
            'cover_path' => $account->profile()->imagePath(ImageKind::Cover),
        ]);

        if ($user->isDirty('password')) {
            $user->setRememberToken(Str::random(self::REMEMBER_TOKEN_LENGTH));
        }

        $user->save();
    }

    /**
     * Erases the columns that identify or reach the person and marks the row closed.
     */
    public function close(AccountId $id): void
    {
        $this->open()->whereKey($id->value)->update([
            'cpf' => null,
            'phone_number' => null,
            'phone_type' => null,
            'email' => null,
            'password' => null,
            'remember_token' => null,
            'photo_path' => null,
            'cover_path' => null,
            'closed_at' => Date::now(),
        ]);
    }

    /**
     * The accounts that are not closed.
     *
     * @return Builder<User>
     */
    private function open(): Builder
    {
        return User::query()->whereNull('closed_at');
    }

    /**
     * Copies the person and the access to the row.
     */
    private function fill(User $user, PersonalDetails $details, Access $access): void
    {
        $user->forceFill([
            'name' => $details->name->value,
            'cpf' => $details->cpf->digits,
            'phone_number' => $details->phone->number->digits,
            'phone_type' => $details->phone->type->value,
            'email' => $access->email->value,
            'password' => $access->password->value,
            'email_verified_at' => $access->emailVerifiedAt,
            'reactivation_deadline' => $access->reactivationDeadline,
        ]);
    }

    /**
     * The domain account a row stands for.
     *
     * @throws UnexpectedValueException When the row holds a value this class does not write.
     */
    private function account(User $user): Account
    {
        try {
            return new Account(
                new AccountId($user->id),
                new PersonalDetails(
                    PersonName::from($user->name),
                    Cpf::from((string) $user->cpf),
                    new ContactPhone(PhoneNumber::from((string) $user->phone_number), PhoneType::from((string) $user->phone_type)),
                ),
                new Access(
                    EmailAddress::from((string) $user->email),
                    new HashedPassword((string) $user->password),
                    $user->email_verified_at?->toDateTimeImmutable(),
                    $user->reactivation_deadline?->toDateTimeImmutable(),
                ),
                new Profile(
                    new Preferences($user->is_night_mode, $user->shows_tutorial),
                    array_filter([
                        ImageKind::Photo->value => $user->photo_path,
                        ImageKind::Cover->value => $user->cover_path,
                    ], static fn (?string $path): bool => $path !== null),
                ),
            );
        } catch (InvalidAccountData $invalid) {
            throw new UnexpectedValueException(
                "The account {$user->id} holds a value no sign-up writes.",
                $invalid->getCode(),
                previous: $invalid,
            );
        }
    }
}

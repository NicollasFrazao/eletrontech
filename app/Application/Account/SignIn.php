<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityType;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\InvalidCredentials;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\SignInEffect;
use SensitiveParameter;

/**
 * Checks an email and password and applies the sign-in to the account: a deactivated account comes
 * back before its deadline and is closed after it.
 */
final readonly class SignIn
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
        private ActivityRecorder $recorder,
        private AccountCloser $closer,
    ) {}

    /**
     * The account signed in to; opening the session is the caller's.
     *
     * @throws InvalidCredentials When the email has no open account, the password is wrong, or the account expired.
     */
    public function handle(string $email, #[SensitiveParameter] string $password): AccountId
    {
        $account = $this->find($email);

        if ($account === null) {
            $this->hasher->spendCheck($password);

            throw InvalidCredentials::create();
        }

        if (! $this->hasher->matches($password, $account->access()->password)) {
            throw InvalidCredentials::create();
        }

        $effect = $account->signIn($this->recorder->now());

        if ($effect === SignInEffect::Expired) {
            $this->closer->close($account);

            throw InvalidCredentials::create();
        }

        if ($effect === SignInEffect::Reactivated) {
            $this->accounts->save($account);
            $this->recorder->record($account->id, ActivityType::Reactivated);
        }

        $this->recorder->record($account->id, ActivityType::SignedIn);

        return $account->id;
    }

    /**
     * The open account of a typed email; a text that is not an email has none.
     */
    private function find(string $email): ?Account
    {
        try {
            return $this->accounts->findByEmail(EmailAddress::from($email));
        } catch (InvalidAccountData) {
            return null;
        }
    }
}

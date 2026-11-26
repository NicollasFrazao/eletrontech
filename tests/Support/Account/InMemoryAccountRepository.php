<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\Cpf;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\NewAccount;
use App\Domain\Account\Preferences;
use App\Domain\Account\Profile;
use App\Domain\Account\Role;

/**
 * Accounts in an array, for the use-case tests; closed ids are remembered to prove they are not found.
 */
final class InMemoryAccountRepository implements AccountRepository
{
    /** @var array<int, Account> */
    private array $accounts = [];

    /** @var list<int> */
    public array $closed = [];

    /** @var array<int, Role> */
    private array $roles = [];

    public function register(NewAccount $account): AccountId
    {
        $id = new AccountId(count($this->accounts) + count($this->closed) + 1);
        $this->accounts[$id->value] = new Account(
            $id,
            $account->details,
            $account->access,
            new Profile(new Preferences(false, true), []),
        );

        return $id;
    }

    public function get(AccountId $id): Account
    {
        return isset($this->accounts[$id->value])
            ? clone $this->accounts[$id->value]
            : throw AccountNotFound::withId($id);
    }

    public function findByEmail(EmailAddress $email): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->access()->email->value === $email->value) {
                return clone $account;
            }
        }

        return null;
    }

    public function findByCpf(Cpf $cpf): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->details()->cpf->digits === $cpf->digits) {
                return clone $account;
            }
        }

        return null;
    }

    public function roleOf(AccountId $id): Role
    {
        return isset($this->accounts[$id->value])
            ? $this->roles[$id->value] ?? Role::Member
            : throw AccountNotFound::withId($id);
    }

    public function grant(AccountId $id, Role $role): void
    {
        $this->roles[$id->value] = $role;
    }

    public function save(Account $account): void
    {
        $this->accounts[$account->id->value] = clone $account;
    }

    public function close(AccountId $id): void
    {
        unset($this->accounts[$id->value]);
        $this->closed[] = $id->value;
    }
}

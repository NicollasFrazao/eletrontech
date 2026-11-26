<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;

/**
 * Decides whether a person may enter the admin panel: the one check behind it.
 */
final readonly class AuthoriseAdministration
{
    public function __construct(private AccountRepository $accounts) {}

    /**
     * Allowed for an open account whose role administers and whose email is confirmed; a closed or
     * unknown account is refused, not an error.
     */
    public function handle(AccountId $id): bool
    {
        try {
            $account = $this->accounts->get($id);
            $role = $this->accounts->roleOf($id);
        } catch (AccountNotFound) {
            return false;
        }

        return $role->mayAdminister($account->access());
    }
}

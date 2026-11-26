<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Application\Account\AccountCloser;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\OwnAccountRefused;

/**
 * Deletes someone's account from the admin panel (the 2015 `Excluir.php`): it is closed as an expired
 * one is, so what identifies the person is erased and the name and the activity log stay.
 */
final readonly class CloseAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private AccountCloser $closer,
    ) {}

    /**
     * Closes the account.
     *
     * @throws OwnAccountRefused When the administrator names their own account.
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $administrator, AccountId $id): void
    {
        if ($administrator->value === $id->value) {
            throw OwnAccountRefused::for($id);
        }

        $this->closer->close($this->accounts->get($id));
    }
}

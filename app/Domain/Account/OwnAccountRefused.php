<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * An administrator asked to deactivate or close the account they are signed in with, which would lock
 * them out of the panel they are using.
 */
final class OwnAccountRefused extends AccountError
{
    /**
     * The error, naming the account.
     */
    public static function for(AccountId $id): self
    {
        return new self("The administrator {$id->value} cannot deactivate or close their own account.");
    }
}

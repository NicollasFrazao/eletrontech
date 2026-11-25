<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PersonName;

/**
 * Changes the owner's name and phone. The CPF is fixed, and the email is not changed here (a new
 * address would need its own confirmation).
 */
final readonly class UpdateProfile
{
    public function __construct(
        private AccountRepository $accounts,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Stores the new name and phone.
     *
     * @throws AccountNotFound When the account is not open.
     * @throws InvalidAccountData When a value is not valid.
     */
    public function handle(AccountId $id, ProfileDetailsInput $input): void
    {
        $name = PersonName::from($input->name);
        $phone = ContactPhones::from($input->phoneNumber, $input->phoneType);

        $account = $this->accounts->get($id);
        $account->updateDetails($name, $phone);
        $this->accounts->save($account);
        $this->activity->record($id, ActivityType::ProfileUpdated, $this->clock->now());
    }
}

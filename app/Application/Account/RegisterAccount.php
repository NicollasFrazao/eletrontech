<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Access;
use App\Domain\Account\Account;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityType;
use App\Domain\Account\AlreadyRegistered;
use App\Domain\Account\Cpf;
use App\Domain\Account\EmailAddress;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\NewAccount;
use App\Domain\Account\PasswordHasher;
use App\Domain\Account\PersonalDetails;
use App\Domain\Account\PersonName;
use App\Domain\Account\PlainPassword;

/**
 * Signs a person up: checks every value, refuses an email or CPF an open account holds, and closes first
 * an account that holds them but whose reactivation deadline has passed (as the 2015 sign-up did).
 */
final readonly class RegisterAccount
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $hasher,
        private ActivityRecorder $recorder,
        private AccountCloser $closer,
    ) {}

    /**
     * Registers the account and records it; the email is not confirmed yet.
     *
     * @throws InvalidAccountData When a value is not valid.
     * @throws AlreadyRegistered When an open account holds the email or the CPF.
     */
    public function handle(RegistrationInput $input): AccountId
    {
        $details = new PersonalDetails(
            PersonName::from($input->person->name),
            Cpf::from($input->person->cpf),
            ContactPhones::from($input->person->phoneNumber, $input->person->phoneType),
        );
        $email = EmailAddress::from($input->email);
        $password = PlainPassword::from($input->password);

        $this->release($this->accounts->findByEmail($email), AlreadyRegistered::email());
        $this->release($this->accounts->findByCpf($details->cpf), AlreadyRegistered::cpf());

        $id = $this->accounts->register(
            new NewAccount($details, Access::forNewAccount($email, $this->hasher->hash($password))),
        );
        $this->recorder->record($id, ActivityType::Registered);

        return $id;
    }

    /**
     * Closes the account holding a value of the sign-up if its deadline passed; refuses the sign-up otherwise.
     *
     * @throws AlreadyRegistered When the holder is open and not expired.
     */
    private function release(?Account $holder, AlreadyRegistered $refusal): void
    {
        if (! $holder instanceof Account) {
            return;
        }

        if (! $holder->hasExpired($this->recorder->now())) {
            throw $refusal;
        }

        $this->closer->close($holder);
    }
}

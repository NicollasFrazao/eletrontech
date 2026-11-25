<?php

declare(strict_types=1);

namespace Tests\Support\Account;

use App\Application\Account\AccountCloser;
use App\Application\Account\ActivityRecorder;
use App\Application\Account\PasswordWriter;
use App\Application\Account\PersonInput;
use App\Application\Account\RegisterAccount;
use App\Application\Account\RegistrationInput;
use App\Domain\Account\AccountId;

/**
 * The in-memory collaborators of the account use cases, wired together, and a sign-up to start from.
 */
final class AccountKit
{
    public InMemoryAccountRepository $accounts;

    public InMemoryActivityLog $activity;

    public FixedClock $clock;

    public PrefixHasher $hasher;

    public InMemoryImageStore $images;

    public function __construct()
    {
        $this->accounts = new InMemoryAccountRepository;
        $this->activity = new InMemoryActivityLog;
        $this->clock = new FixedClock;
        $this->hasher = new PrefixHasher;
        $this->images = new InMemoryImageStore;
    }

    public function recorder(): ActivityRecorder
    {
        return new ActivityRecorder($this->activity, $this->clock);
    }

    public function closer(): AccountCloser
    {
        return new AccountCloser($this->accounts, $this->images, $this->activity, $this->clock);
    }

    public function passwords(): PasswordWriter
    {
        return new PasswordWriter($this->accounts, $this->hasher, $this->activity, $this->clock);
    }

    public function register(): RegisterAccount
    {
        return new RegisterAccount($this->accounts, $this->hasher, $this->recorder(), $this->closer());
    }

    /**
     * A valid sign-up, with any field replaced.
     */
    public static function input(
        string $email = 'ana@example.com',
        string $cpf = '529.982.247-25',
        string $password = 'correct horse',
    ): RegistrationInput {
        return new RegistrationInput(
            new PersonInput('Ana  Souza ', $cpf, '(13) 99876-5432', 'mobile'),
            $email,
            $password,
        );
    }

    /**
     * Registers the valid sign-up.
     */
    public function registered(string $email = 'ana@example.com', string $cpf = '529.982.247-25'): AccountId
    {
        return $this->register()->handle(self::input($email, $cpf));
    }
}

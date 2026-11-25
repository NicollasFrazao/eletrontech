<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateTimeImmutable;

/**
 * How an account is signed in to and whether it is in use: the email, the password hash, when the
 * email was confirmed, and the reactivation deadline of a deactivated account.
 */
final readonly class Access
{
    /**
     * @param  ?DateTimeImmutable  $emailVerifiedAt  Null until the owner confirms the email address.
     * @param  ?DateTimeImmutable  $reactivationDeadline  Null while the account is active.
     */
    public function __construct(
        public EmailAddress $email,
        public HashedPassword $password,
        public ?DateTimeImmutable $emailVerifiedAt,
        public ?DateTimeImmutable $reactivationDeadline,
    ) {}

    /**
     * The access of a new account: not confirmed, active.
     */
    public static function forNewAccount(EmailAddress $email, HashedPassword $password): self
    {
        return new self($email, $password, null, null);
    }

    /**
     * The same access with another password hash.
     */
    public function withPassword(HashedPassword $password): self
    {
        return new self($this->email, $password, $this->emailVerifiedAt, $this->reactivationDeadline);
    }

    /**
     * The same access, confirmed at that moment unless it was confirmed before.
     */
    public function verifiedAt(DateTimeImmutable $now): self
    {
        return new self($this->email, $this->password, $this->emailVerifiedAt ?? $now, $this->reactivationDeadline);
    }

    /**
     * The same access with another reactivation deadline, or none (active).
     */
    public function withReactivationDeadline(?DateTimeImmutable $deadline): self
    {
        return new self($this->email, $this->password, $this->emailVerifiedAt, $deadline);
    }
}

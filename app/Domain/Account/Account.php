<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DateInterval;
use DateTimeImmutable;

/**
 * A registered person's account: who they are (PersonalDetails), how they sign in and whether it is in
 * use (Access), and how it shows itself (Profile).
 *
 * Deactivating keeps the account for 30 days, as the 2015 version did (`dt_reativar`): signing in
 * before then reactivates it; after then the account is closed.
 */
final class Account
{
    /**
     * How long a deactivated account waits for its owner before it is closed.
     */
    private const string REACTIVATION_PERIOD = 'P30D';

    public function __construct(
        public readonly AccountId $id,
        private PersonalDetails $details,
        private Access $access,
        private Profile $profile,
    ) {}

    /**
     * The person the account belongs to.
     */
    public function details(): PersonalDetails
    {
        return $this->details;
    }

    /**
     * The email, password hash, confirmation and reactivation deadline.
     */
    public function access(): Access
    {
        return $this->access;
    }

    /**
     * The preferences and the pictures.
     */
    public function profile(): Profile
    {
        return $this->profile;
    }

    /**
     * Records that the owner confirmed the email address; confirming again keeps the first date.
     */
    public function verifyEmail(DateTimeImmutable $now): void
    {
        $this->access = $this->access->verifiedAt($now);
    }

    /**
     * Replaces the password hash.
     */
    public function changePassword(HashedPassword $password): void
    {
        $this->access = $this->access->withPassword($password);
    }

    /**
     * Replaces the name and the phone.
     */
    public function updateDetails(PersonName $name, ContactPhone $phone): void
    {
        $this->details = $this->details->withNameAndPhone($name, $phone);
    }

    /**
     * Replaces the preferences and pictures; Profile's `with*` methods build the new one.
     */
    public function replaceProfile(Profile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * Deactivates the account until 30 days from now; deactivating again moves the deadline.
     */
    public function deactivate(DateTimeImmutable $now): void
    {
        $this->access = $this->access->withReactivationDeadline($now->add(new DateInterval(self::REACTIVATION_PERIOD)));
    }

    /**
     * Whether the account is in use, that is not deactivated.
     */
    public function isActive(): bool
    {
        return ! $this->access->reactivationDeadline instanceof DateTimeImmutable;
    }

    /**
     * Applies a sign-in: reactivates a deactivated account before its deadline, and says it expired after.
     */
    public function signIn(DateTimeImmutable $now): SignInEffect
    {
        if ($this->isActive()) {
            return SignInEffect::None;
        }

        if ($this->hasExpired($now)) {
            return SignInEffect::Expired;
        }

        $this->access = $this->access->withReactivationDeadline(null);

        return SignInEffect::Reactivated;
    }

    /**
     * Whether the account was deactivated and its deadline has passed, so it is to be closed.
     */
    public function hasExpired(DateTimeImmutable $now): bool
    {
        return $this->access->reactivationDeadline instanceof DateTimeImmutable && $now > $this->access->reactivationDeadline;
    }
}

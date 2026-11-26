<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * Where accounts are kept. Only open accounts are found; a closed one keeps its name and activity log
 * for the records but nothing that identifies or reaches the person.
 */
interface AccountRepository
{
    /**
     * Stores a new account with the default preferences and no pictures.
     */
    public function register(NewAccount $account): AccountId;

    /**
     * The open account with that id.
     *
     * @throws AccountNotFound When there is none, or it was closed.
     */
    public function get(AccountId $id): Account;

    /**
     * The open account with that email, if any.
     */
    public function findByEmail(EmailAddress $email): ?Account;

    /**
     * The open account with that CPF, if any.
     */
    public function findByCpf(Cpf $cpf): ?Account;

    /**
     * The role of the open account with that id; a new account is a member.
     *
     * @throws AccountNotFound When there is none, or it was closed.
     */
    public function roleOf(AccountId $id): Role;

    /**
     * Writes the account's current state.
     */
    public function save(Account $account): void;

    /**
     * Closes the account: erases its CPF, email, phone, password and picture paths (the CPF and email
     * become free for a new sign-up) and keeps the name. Deleting the picture files is the caller's.
     */
    public function close(AccountId $id): void;
}

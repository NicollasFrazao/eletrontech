<?php

declare(strict_types=1);

namespace App\Domain\Account;

use DomainException;

/**
 * A rule of accounts refused what was asked; the edges catch this to answer the user instead of failing.
 */
abstract class AccountError extends DomainException {}

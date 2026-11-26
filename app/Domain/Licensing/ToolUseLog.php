<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use App\Domain\Account\AccountId;
use DateTimeImmutable;

/**
 * The record of which tool each person used and when (the 2015 `tb_utilizacao`), for the admin panel.
 */
interface ToolUseLog
{
    /**
     * Records one use.
     */
    public function record(AccountId $user, ToolId $tool, DateTimeImmutable $at): void;
}

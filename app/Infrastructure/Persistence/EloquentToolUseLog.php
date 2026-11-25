<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolUseLog;
use App\Infrastructure\Persistence\Eloquent\ToolUseRecord;
use DateTimeImmutable;

/**
 * The tool uses kept in `tool_uses`.
 */
final readonly class EloquentToolUseLog implements ToolUseLog
{
    /**
     * Writes one row for the use.
     */
    public function record(AccountId $user, ToolId $tool, DateTimeImmutable $at): void
    {
        ToolUseRecord::query()->create([
            'user_id' => $user->value,
            'tool_id' => $tool->value,
            'used_at' => $at,
        ]);
    }
}

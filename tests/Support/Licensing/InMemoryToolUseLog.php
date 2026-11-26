<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolUseLog;
use DateTimeImmutable;

/**
 * Tool uses kept in memory, in the order recorded.
 */
final class InMemoryToolUseLog implements ToolUseLog
{
    /**
     * @var list<array{int, string, DateTimeImmutable}> Holder id, tool and time of each use.
     */
    public array $uses = [];

    public function record(AccountId $user, ToolId $tool, DateTimeImmutable $at): void
    {
        $this->uses[] = [$user->value, $tool->value, $at];
    }
}

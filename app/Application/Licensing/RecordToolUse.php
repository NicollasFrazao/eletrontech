<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolUseLog;
use App\Domain\Licensing\UnknownTool;
use InvalidArgumentException;

/**
 * Records that a person used a tool, after the tool answered (the 2015 `tb_utilizacao`).
 */
final readonly class RecordToolUse
{
    public function __construct(
        private ToolUseLog $log,
        private Clock $clock,
    ) {}

    /**
     * Records one use, now. The route's `use-tool` gate has already found the tool.
     *
     * @throws InvalidArgumentException When the identifier cannot be a tool's (a route without the gate).
     */
    public function handle(AccountId $account, string $tool): void
    {
        try {
            $id = ToolId::from($tool);
        } catch (UnknownTool $error) {
            throw new InvalidArgumentException($error->getMessage(), 0, $error);
        }

        $this->log->record($account, $id, $this->clock->now());
    }
}

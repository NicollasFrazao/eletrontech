<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownTool;

/**
 * Decides whether a person may use a tool now: the one check behind the `use-tool` gate.
 */
final readonly class AuthoriseToolUse
{
    public function __construct(
        private LicenceRepository $licences,
        private ToolRepository $tools,
        private Clock $clock,
    ) {}

    /**
     * Allowed when the person's licence is running and its package includes the tool.
     */
    public function handle(AccountId $account, string $tool): ToolAccess
    {
        try {
            $id = ToolId::from($tool);
        } catch (UnknownTool) {
            return ToolAccess::UnknownTool;
        }

        if (! $this->tools->exists($id)) {
            return ToolAccess::UnknownTool;
        }

        $licence = $this->licences->forHolder($account);

        return $licence instanceof Licence && $licence->allows($id, $this->clock->now())
            ? ToolAccess::Allowed
            : ToolAccess::NotLicensed;
    }
}

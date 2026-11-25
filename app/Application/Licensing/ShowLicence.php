<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Account\Clock;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\ToolRepository;

/**
 * A person's licence: the package, its period, the days left and its tools.
 */
final readonly class ShowLicence
{
    public function __construct(
        private LicenceRepository $licences,
        private ToolRepository $tools,
        private Clock $clock,
    ) {}

    /**
     * The licence, or null when the person never had one.
     */
    public function handle(AccountId $account): ?LicenceView
    {
        $licence = $this->licences->forHolder($account);

        if (! $licence instanceof Licence) {
            return null;
        }

        return new LicenceView(
            $licence,
            $licence->remainingDaysAt($this->clock->now()),
            $this->tools->named($licence->package->tools()),
        );
    }
}

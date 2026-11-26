<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;

/**
 * Licences kept in memory, one per holder.
 */
final class InMemoryLicenceRepository implements LicenceRepository
{
    /**
     * @var array<int, Licence> Keyed by holder id.
     */
    private array $licences = [];

    public function forHolder(AccountId $holder): ?Licence
    {
        return $this->licences[$holder->value] ?? null;
    }

    public function save(Licence $licence): void
    {
        $this->licences[$licence->holder->value] = $licence;
    }
}

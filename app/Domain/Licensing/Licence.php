<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use App\Domain\Account\AccountId;
use DateInterval;
use DateTimeImmutable;

/**
 * A person's right to use the tools of one package between two moments (the 2015 `usuario_pacote`). A
 * person has one licence at a time: buying a package replaces it, or extends it when it is the same
 * package and still running.
 */
final readonly class Licence
{
    /**
     * How long the licence of a new account lasts, with the free package.
     */
    private const string INITIAL_PERIOD = 'P7D';

    /**
     * Seconds in a day, to count the days left as 2015 did.
     */
    private const int SECONDS_PER_DAY = 86_400;

    public function __construct(
        public AccountId $holder,
        public Package $package,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt,
    ) {}

    /**
     * The licence a new account starts with: the free package for seven days.
     *
     * @throws PackageNotForSale When the package given is not the free one.
     */
    public static function initial(AccountId $holder, Package $free, DateTimeImmutable $now): self
    {
        if ($free->kind !== PackageKind::Free) {
            throw PackageNotForSale::notFree($free->id);
        }

        return new self($holder, $free, $now, $now->add(new DateInterval(self::INITIAL_PERIOD)));
    }

    /**
     * The licence of a person who buys a package with no licence before: it starts now.
     *
     * @throws PackageNotForSale When the package is the free one, which only a new account gets.
     */
    public static function bought(AccountId $holder, Package $package, UsageDays $days, DateTimeImmutable $now): self
    {
        if ($package->kind === PackageKind::Free) {
            throw PackageNotForSale::free($package->id);
        }

        return new self($holder, $package, $now, $now->add(self::period($days)));
    }

    /**
     * The licence after buying a package for some days: the same package while it runs is extended from
     * its end; anything else starts now and replaces it.
     *
     * @throws PackageNotForSale When the package is the free one, which only a new account gets.
     */
    public function renewedWith(Package $package, UsageDays $days, DateTimeImmutable $now): self
    {
        $bought = self::bought($this->holder, $package, $days, $now);

        if (! $this->isActiveAt($now) || ! $this->package->isSameAs($package)) {
            return $bought;
        }

        return new self($this->holder, $package, $this->startsAt, $this->endsAt->add(self::period($days)));
    }

    /**
     * Whether the licence is running at that moment.
     */
    public function isActiveAt(DateTimeImmutable $now): bool
    {
        return $now >= $this->startsAt && $now < $this->endsAt;
    }

    /**
     * The days left, counting a part of a day as a day (as 2015 did); 0 once it has ended.
     */
    public function remainingDaysAt(DateTimeImmutable $now): int
    {
        $seconds = $this->endsAt->getTimestamp() - $now->getTimestamp();

        return $seconds > 0 ? (int) ceil($seconds / self::SECONDS_PER_DAY) : 0;
    }

    /**
     * Whether the licence lets its holder use the tool at that moment: it is running and its package
     * includes the tool. The one rule behind the `use-tool` gate.
     */
    public function allows(ToolId $tool, DateTimeImmutable $now): bool
    {
        return $this->isActiveAt($now) && $this->package->includes($tool);
    }

    /**
     * The days of a purchase as an interval.
     */
    private static function period(UsageDays $days): DateInterval
    {
        return new DateInterval("P{$days->value}D");
    }
}

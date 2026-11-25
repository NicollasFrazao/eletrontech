<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Application\Account\ActivityRecorder;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\ActivityType;
use App\Domain\Account\WrongPassword;
use App\Domain\Licensing\EmptyCustomPackage;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\Licence;
use App\Domain\Licensing\LicenceRepository;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageNotForSale;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use App\Domain\Licensing\UsageDays;

/**
 * Buys a package for some days, confirmed with the buyer's password; nothing is charged, as in 2015
 * (payments are #20). The licence is replaced, or extended when it is the same package and running. A
 * custom package gets the everyday tools (`Package::ESSENTIALS`) besides the ones picked.
 */
final readonly class BuyPackage
{
    public function __construct(
        private PasswordConfirmation $confirmation,
        private PackageRepository $packages,
        private LicenceRepository $licences,
        private ActivityRecorder $recorder,
    ) {}

    /**
     * Stores the buyer's new licence and records the purchase.
     *
     * @throws WrongPassword When the password is not the buyer's.
     * @throws InvalidUsageDays When the days are outside 7 to 365.
     * @throws UnknownPackage When no ready-made package has the id.
     * @throws PackageNotForSale When the package is the free one.
     * @throws EmptyCustomPackage When a custom package has no tool.
     * @throws UnknownTool When a tool of a custom package does not exist.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function handle(PurchaseInput $input): Licence
    {
        $this->confirmation->confirm($input->buyer, $input->confirmation->password);
        $days = new UsageDays($input->confirmation->days);
        $now = $this->recorder->now();
        $current = $this->licences->forHolder($input->buyer);
        $package = $this->package($input);

        $licence = $current instanceof Licence
            ? $current->renewedWith($package, $days, $now)
            : Licence::bought($input->buyer, $package, $days, $now);

        $this->licences->save($licence);
        $this->recorder->record($input->buyer, ActivityType::PackageBought);

        return $licence;
    }

    /**
     * The package bought: a ready-made one by id, or the buyer's custom package with the tools picked.
     *
     * @throws UnknownPackage When no ready-made or free package has the id.
     * @throws EmptyCustomPackage When a custom package has no tool.
     * @throws UnknownTool When a tool does not exist.
     */
    private function package(PurchaseInput $input): Package
    {
        if ($input->packageId !== null) {
            $package = $this->packages->get(new PackageId($input->packageId));

            return $package->kind === PackageKind::Custom ? throw UnknownPackage::withId($input->packageId) : $package;
        }

        $picked = array_values(array_unique($input->customTools));

        if ($picked === []) {
            throw EmptyCustomPackage::create();
        }

        $tools = array_map(ToolId::from(...), array_values(array_unique([...Package::ESSENTIALS, ...$picked])));

        return $this->packages->customFor($input->buyer, $tools);
    }
}

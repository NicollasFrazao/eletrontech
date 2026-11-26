<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use App\Domain\Account\AccountId;

/**
 * The packages and the tools they are made of.
 */
interface PackageRepository
{
    /**
     * One package with its tools.
     *
     * @throws UnknownPackage When no package has the id, or it is someone else's custom package.
     */
    public function get(PackageId $id): Package;

    /**
     * The package a new account starts with.
     */
    public function free(): Package;

    /**
     * The ready-made packages the shop sells, by name.
     *
     * @return list<Package>
     */
    public function readyMade(): array;

    /**
     * The person's custom package with exactly these tools; it replaces the tools of their custom
     * package, or creates it the first time (a person has at most one, as in 2015).
     *
     * @param  non-empty-list<ToolId>  $tools
     *
     * @throws UnknownTool When a tool does not exist.
     */
    public function customFor(AccountId $owner, array $tools): Package;
}

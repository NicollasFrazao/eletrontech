<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ImageKind;
use App\Domain\Account\ImageStore;

/**
 * Reads the owner's profile photo or cover, which are kept off the public folder.
 */
final readonly class ShowProfileImage
{
    public function __construct(
        private AccountRepository $accounts,
        private ImageStore $images,
    ) {}

    /**
     * The picture's JPEG bytes, or null when the account has none.
     *
     * @throws AccountNotFound When the account is not open.
     */
    public function handle(AccountId $id, ImageKind $kind): ?string
    {
        $path = $this->accounts->get($id)->profile()->imagePath($kind);

        return $path === null ? null : $this->images->read($path);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\AccountRepository;
use App\Domain\Account\ActivityLog;
use App\Domain\Account\ActivityType;
use App\Domain\Account\Clock;
use App\Domain\Account\ImageKind;
use App\Domain\Account\ImageStore;
use App\Domain\Account\UnreadableImage;

/**
 * Replaces the profile photo or the cover with an uploaded picture, and deletes the one it replaces.
 */
final readonly class ReplaceProfileImage
{
    public function __construct(
        private AccountRepository $accounts,
        private ImageStore $images,
        private ActivityLog $activity,
        private Clock $clock,
    ) {}

    /**
     * Stores the picture, scaled to the kind's size.
     *
     * @throws AccountNotFound When the account is not open.
     * @throws UnreadableImage When the upload is not a picture that can be decoded.
     */
    public function handle(AccountId $id, ImageKind $kind, string $contents): void
    {
        $account = $this->accounts->get($id);
        $replaced = $account->profile()->imagePath($kind);
        $account->replaceProfile($account->profile()->withImage($kind, $this->images->store($kind, $contents)));
        $this->accounts->save($account);

        if ($replaced !== null) {
            $this->images->delete($replaced);
        }

        $this->activity->record($id, ActivityType::imageReplaced($kind), $this->clock->now());
    }
}

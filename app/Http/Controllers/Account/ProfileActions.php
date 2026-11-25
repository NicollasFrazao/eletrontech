<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\ChangePassword;
use App\Application\Account\DeactivateAccount;
use App\Application\Account\UpdatePreferences;
use App\Application\Account\UpdateProfile;

/**
 * The use cases that change a profile, injected together into ProfileController.
 */
final readonly class ProfileActions
{
    public function __construct(
        public UpdateProfile $updateProfile,
        public ChangePassword $changePassword,
        public UpdatePreferences $updatePreferences,
        public DeactivateAccount $deactivate,
    ) {}
}

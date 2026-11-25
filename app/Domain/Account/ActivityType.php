<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * The account actions recorded in the activity log, which the admin panel shows (`tb_atividade`).
 */
enum ActivityType: string
{
    case Registered = 'registered';
    case EmailVerified = 'email_verified';
    case SignedIn = 'signed_in';
    case SignedOut = 'signed_out';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case ProfileUpdated = 'profile_updated';
    case PreferencesUpdated = 'preferences_updated';
    case PhotoReplaced = 'photo_replaced';
    case CoverReplaced = 'cover_replaced';
    case Deactivated = 'deactivated';
    case Reactivated = 'reactivated';
    case Closed = 'closed';

    /**
     * The action of replacing one of the two pictures.
     */
    public static function imageReplaced(ImageKind $kind): self
    {
        return match ($kind) {
            ImageKind::Photo => self::PhotoReplaced,
            ImageKind::Cover => self::CoverReplaced,
        };
    }
}

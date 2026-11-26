<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Accounts;

use App\Application\Administration\CloseAccount;
use App\Application\Administration\DeactivateAccount;
use App\Application\Administration\ReactivateAccount;
use App\Domain\Account\AccountError;
use App\Domain\Account\AccountId;
use App\Infrastructure\Persistence\Eloquent\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\App;

/**
 * The account actions of the panel, on the list and on the edit page, each calling its use case.
 *
 * The administrator's own row has no deactivate or delete action: the use cases refuse it too, so a
 * forged request cannot lock the only administrator out.
 */
final class AccountActions
{
    /**
     * Deactivates an active account for 30 days, after a confirmation.
     */
    public static function deactivate(): Action
    {
        return Action::make('deactivate')
            ->label(__('admin.accounts.actions.deactivate'))
            ->icon(Heroicon::OutlinedPauseCircle)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('admin.accounts.confirm.deactivate'))
            ->visible(static fn (User $record): bool => AccountSearch::status($record) === AccountSearch::ACTIVE
                && ! self::isOwn($record))
            ->successNotificationTitle(__('admin.accounts.done.deactivated'))
            ->failureNotificationTitle(__('admin.accounts.done.refused'))
            ->action(static function (Action $action, User $record): void {
                try {
                    App::make(DeactivateAccount::class)->handle(self::administrator(), new AccountId($record->id));
                } catch (AccountError) {
                    $action->failure();

                    return;
                }

                $action->success();
            });
    }

    /**
     * Reactivates a deactivated account.
     */
    public static function reactivate(): Action
    {
        return Action::make('reactivate')
            ->label(__('admin.accounts.actions.reactivate'))
            ->icon(Heroicon::OutlinedPlayCircle)
            ->color('success')
            ->requiresConfirmation()
            ->visible(static fn (User $record): bool => AccountSearch::status($record) === AccountSearch::DEACTIVATED)
            ->successNotificationTitle(__('admin.accounts.done.reactivated'))
            ->failureNotificationTitle(__('admin.accounts.done.refused'))
            ->action(static function (Action $action, User $record): void {
                try {
                    App::make(ReactivateAccount::class)->handle(new AccountId($record->id));
                } catch (AccountError) {
                    $action->failure();

                    return;
                }

                $action->success();
            });
    }

    /**
     * Deletes the account, that is closes it: its CPF, email, phone, password and pictures are erased.
     */
    public static function close(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription(__('admin.accounts.confirm.delete'))
            ->hidden(static fn (User $record): bool => self::isOwn($record))
            ->failureNotificationTitle(__('admin.accounts.done.refused'))
            ->using(static function (User $record): bool {
                try {
                    App::make(CloseAccount::class)->handle(self::administrator(), new AccountId($record->id));
                } catch (AccountError) {
                    return false;
                }

                return true;
            });
    }

    /**
     * Whether the row is the account of the administrator using the panel.
     */
    private static function isOwn(User $record): bool
    {
        return self::administrator()->value === $record->id;
    }

    /**
     * The administrator using the panel; the panel's middleware lets no guest this far.
     */
    private static function administrator(): AccountId
    {
        $id = Filament::auth()->id();

        return new AccountId(is_numeric($id) ? (int) $id : 0);
    }
}

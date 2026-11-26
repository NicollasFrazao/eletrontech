<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Accounts;

use App\Application\Account\ProfileDetailsInput;
use App\Application\Account\UpdateProfile;
use App\Domain\Account\AccountField;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\InvalidAccountData;
use App\Infrastructure\Persistence\Eloquent\User;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use LogicException;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The edit page of an account: the name and the phone are saved by UpdateProfile, the use case of the
 * owner's own profile, so the panel validates them the same way.
 */
final class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    /**
     * Deactivate, reactivate and delete, as on the list.
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            AccountActions::deactivate(),
            AccountActions::reactivate(),
            AccountActions::close(),
        ];
    }

    /**
     * Saves the details through the use case; a refused value is shown on its field.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException When a value is not valid.
     * @throws NotFoundHttpException When the account was closed meanwhile.
     * @throws LogicException When the record is not an account's row.
     */
    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            throw new LogicException('The account pages edit the rows of users only.');
        }

        $input = new ProfileDetailsInput($this->text($data, 'name'), $this->text($data, 'phone_number'), $this->text($data, 'phone_type'));

        try {
            App::make(UpdateProfile::class)->handle(new AccountId($record->id), $input);
        } catch (InvalidAccountData $invalid) {
            throw ValidationException::withMessages([
                $this->formField($invalid->field) => __("accounts.errors.invalid.{$invalid->field->value}"),
            ]);
        } catch (AccountNotFound $closed) {
            throw new NotFoundHttpException(message: $closed->getMessage(), previous: $closed);
        }

        return $record->refresh();
    }

    /**
     * One typed value of the form; a missing one is empty, which the use case refuses.
     *
     * @param  array<string, mixed>  $data
     */
    private function text(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * The form state path of the field that holds an account value.
     */
    private function formField(AccountField $field): string
    {
        return 'data.'.($field === AccountField::Phone ? 'phone_number' : $field->value);
    }
}

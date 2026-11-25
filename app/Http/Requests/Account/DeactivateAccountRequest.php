<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The form that deactivates the account, confirmed with the password.
 */
final class DeactivateAccountRequest extends FormRequest
{
    /**
     * The longest text the field accepts.
     */
    private const int MAX_LENGTH = 255;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['deactivation_password' => ['required', 'string', 'max:'.self::MAX_LENGTH]];
    }
}

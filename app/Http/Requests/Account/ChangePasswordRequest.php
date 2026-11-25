<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The profile form for a new password, confirmed with the current one.
 */
final class ChangePasswordRequest extends FormRequest
{
    /**
     * The longest text the fields accept.
     */
    private const int MAX_LENGTH = 255;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'password' => ['required', 'string', 'max:'.self::MAX_LENGTH, 'confirmed'],
        ];
    }
}

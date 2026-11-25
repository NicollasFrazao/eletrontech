<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The form that asks for a password reset link.
 */
final class ForgotPasswordRequest extends FormRequest
{
    /**
     * The longest email address a mail server accepts.
     */
    private const int MAX_EMAIL_LENGTH = 254;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:'.self::MAX_EMAIL_LENGTH]];
    }
}

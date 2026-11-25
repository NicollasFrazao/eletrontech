<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Application\Account\PersonInput;
use App\Application\Account\RegistrationInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The sign-up form. The shape is checked here; whether the CPF, the phone and the rest are valid is the
 * domain's answer.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * The longest text any of the fields accepts.
     */
    private const int MAX_LENGTH = 255;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $text = ['required', 'string', 'max:'.self::MAX_LENGTH];

        return [
            'name' => $text,
            'cpf' => $text,
            'phone_number' => $text,
            'phone_type' => $text,
            'email' => $text,
            'password' => [...$text, 'confirmed'],
        ];
    }

    /**
     * The input for the use case.
     */
    public function toInput(): RegistrationInput
    {
        return new RegistrationInput(
            new PersonInput(
                $this->string('name')->toString(),
                $this->string('cpf')->toString(),
                $this->string('phone_number')->toString(),
                $this->string('phone_type')->toString(),
            ),
            $this->string('email')->toString(),
            $this->string('password')->toString(),
        );
    }
}

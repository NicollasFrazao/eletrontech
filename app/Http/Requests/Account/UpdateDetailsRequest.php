<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Application\Account\ProfileDetailsInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The profile form for the name and the phone.
 */
final class UpdateDetailsRequest extends FormRequest
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
        $text = ['required', 'string', 'max:'.self::MAX_LENGTH];

        return ['name' => $text, 'phone_number' => $text, 'phone_type' => $text];
    }

    /**
     * The input for the use case.
     */
    public function toInput(): ProfileDetailsInput
    {
        return new ProfileDetailsInput(
            $this->string('name')->toString(),
            $this->string('phone_number')->toString(),
            $this->string('phone_type')->toString(),
        );
    }
}

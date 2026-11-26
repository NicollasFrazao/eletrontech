<?php

declare(strict_types=1);

namespace App\Http\Requests\Shop;

use App\Application\Licensing\PurchaseConfirmation;
use App\Application\Licensing\PurchaseInput;
use App\Http\Controllers\Account\SignedInAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The purchase form: a ready-made package or the tools of a custom one, the days and the password. The
 * buyer is the session's, never a field.
 */
final class PurchaseRequest extends FormRequest
{
    /**
     * The longest text a field accepts.
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
            'package_id' => ['nullable', 'integer'],
            'tools' => ['required_without:package_id', 'array'],
            'tools.*' => ['string', 'max:'.self::MAX_LENGTH],
            'days' => ['required', 'integer'],
            'purchase_password' => ['required', 'string', 'max:'.self::MAX_LENGTH],
        ];
    }

    /**
     * The input of the purchase use case.
     *
     * @throws AuthenticationException When nobody is signed in.
     */
    public function toInput(): PurchaseInput
    {
        $tools = $this->array('tools');

        return new PurchaseInput(
            SignedInAccount::id($this),
            $this->filled('package_id') ? $this->integer('package_id') : null,
            array_values(array_filter($tools, is_string(...))),
            new PurchaseConfirmation($this->integer('days'), $this->string('purchase_password')->toString()),
        );
    }
}

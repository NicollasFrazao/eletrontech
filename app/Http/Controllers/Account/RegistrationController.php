<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\RegisterAccount;
use App\Domain\Account\AlreadyRegistered;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PhoneType;
use App\Http\Requests\Account\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * The sign-up page: registers the account, signs the person in and sends the confirmation email.
 */
final readonly class RegistrationController
{
    public function __construct(
        private RegisterAccount $register,
    ) {}

    /**
     * The empty form.
     */
    public function create(): View
    {
        return view('accounts.register', ['phoneTypes' => PhoneType::cases()]);
    }

    /**
     * Registers the account and asks the person to confirm the email.
     *
     * @throws ValidationException When a value is refused or the email or CPF is taken.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        try {
            $id = $this->register->handle($request->toInput());
        } catch (InvalidAccountData|AlreadyRegistered $error) {
            throw AccountErrors::toValidation($error);
        }

        Auth::loginUsingId($id->value);
        $request->session()->regenerate();
        $request->user()?->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}

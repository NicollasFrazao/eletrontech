<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\ResetPassword;
use App\Domain\Account\AccountId;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\InvalidAccountData;
use App\Http\Requests\Account\ForgotPasswordRequest;
use App\Http\Requests\Account\ResetPasswordRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

/**
 * Password recovery by an emailed link, through the framework's password broker (tokens hashed and
 * expiring). 2015 emailed the password itself back.
 */
final readonly class PasswordResetController
{
    public function __construct(
        private ResetPassword $reset,
    ) {}

    /**
     * The form that asks for a link.
     */
    public function request(): View
    {
        return view('accounts.forgot-password');
    }

    /**
     * Sends a link when the email has an open account; the answer is the same either way, so the form
     * does not tell which emails have an account.
     */
    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink(['email' => mb_strtolower(trim($request->string('email')->toString()))]);

        return back()->with('status', __('accounts.status.reset_link'));
    }

    /**
     * The form a link opens.
     */
    public function edit(string $token): View
    {
        return view('accounts.reset-password', ['token' => $token, 'email' => request()->string('email')]);
    }

    /**
     * Sets the new password when the link is valid.
     *
     * @throws ValidationException When the new password is refused, or the link is invalid or expired.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $password = $request->string('password')->toString();

        try {
            $status = Password::reset(
                [
                    'email' => mb_strtolower(trim($request->string('email')->toString())),
                    'token' => $request->string('token')->toString(),
                    'password' => $password,
                ],
                fn (Authenticatable $user) => $this->resetFor($user, $password),
            );
        } catch (InvalidAccountData $error) {
            throw AccountErrors::toValidation($error);
        }

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('accounts.errors.reset_link')]);
        }

        return redirect()->route('login')->with('status', __('accounts.status.password_reset'));
    }

    /**
     * Stores the new password of the account the link belongs to.
     *
     * @throws InvalidAccountData When the new password is refused.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    private function resetFor(Authenticatable $user, string $password): void
    {
        $id = $user->getAuthIdentifier();

        if (! is_int($id)) {
            throw new UnexpectedValueException('The password broker handed over a user without an integer id.');
        }

        $this->reset->handle(new AccountId($id), $password);
    }
}

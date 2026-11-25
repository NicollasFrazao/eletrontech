<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\ViewProfile;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\InvalidAccountData;
use App\Domain\Account\PhoneType;
use App\Domain\Account\WrongPassword;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\DeactivateAccountRequest;
use App\Http\Requests\Account\UpdateDetailsRequest;
use App\Http\Requests\Account\UpdatePreferencesRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * The profile page of the signed-in owner: details, password, preferences, recent activity and
 * deactivation. Every action works on the session's account; none takes an account id from the request
 * (2015's pages did).
 */
final readonly class ProfileController
{
    /**
     * The use cases of the page, grouped so the controller takes one dependency per concern.
     */
    public function __construct(
        private ViewProfile $view,
        private ProfileActions $actions,
    ) {}

    /**
     * The profile page.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function show(Request $request): View
    {
        return view('accounts.profile', [
            'profile' => $this->view->handle(SignedInAccount::id($request)),
            'phoneTypes' => PhoneType::cases(),
        ]);
    }

    /**
     * Changes the name and the phone.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     * @throws ValidationException When a value is refused.
     */
    public function updateDetails(UpdateDetailsRequest $request): RedirectResponse
    {
        try {
            $this->actions->updateProfile->handle(SignedInAccount::id($request), $request->toInput());
        } catch (InvalidAccountData $error) {
            throw AccountErrors::toValidation($error);
        }

        return $this->saved('details');
    }

    /**
     * Changes the password, confirmed with the current one.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     * @throws ValidationException When the current password is wrong or the new one is refused.
     */
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        try {
            $this->actions->changePassword->handle(
                SignedInAccount::id($request),
                $request->string('current_password')->toString(),
                $request->string('password')->toString(),
            );
        } catch (WrongPassword) {
            throw ValidationException::withMessages(['current_password' => __('accounts.errors.wrong_password')]);
        } catch (InvalidAccountData $error) {
            throw AccountErrors::toValidation($error);
        }

        return $this->saved('password');
    }

    /**
     * Changes the night mode and the tutorial toggle.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $this->actions->updatePreferences->handle(SignedInAccount::id($request), $request->toPreferences());

        return $this->saved('preferences');
    }

    /**
     * Deactivates the account for 30 days and signs out.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     * @throws ValidationException When the password is wrong.
     */
    public function deactivate(DeactivateAccountRequest $request): RedirectResponse
    {
        try {
            $this->actions->deactivate->handle(
                SignedInAccount::id($request),
                $request->string('deactivation_password')->toString(),
            );
        } catch (WrongPassword) {
            throw ValidationException::withMessages(['deactivation_password' => __('accounts.errors.wrong_password')]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('accounts.status.deactivated'));
    }

    /**
     * Back to the profile, saying which part was saved.
     */
    private function saved(string $part): RedirectResponse
    {
        return redirect()->route('profile')->with('status', __("accounts.status.saved.{$part}"));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\VerifyEmail;
use App\Domain\Account\AccountNotFound;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Email confirmation: the page that asks for it, the signed link that confirms, and a new link.
 *
 * The framework signs the link and checks it belongs to the signed-in account (2015's link carried a
 * Base64 id and date that anyone could forge).
 */
final readonly class EmailVerificationController
{
    public function __construct(
        private VerifyEmail $verify,
    ) {}

    /**
     * The page that asks the person to open the link sent to them.
     */
    public function notice(): View
    {
        return view('accounts.verify-email');
    }

    /**
     * Confirms the email of the signed-in account.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $this->verify->handle(SignedInAccount::id($request));

        return redirect()->route('profile')->with('status', __('accounts.status.verified'));
    }

    /**
     * Sends a new confirmation link.
     */
    public function resend(Request $request): RedirectResponse
    {
        $request->user()?->sendEmailVerificationNotification();

        return back()->with('status', __('accounts.status.link_sent'));
    }
}

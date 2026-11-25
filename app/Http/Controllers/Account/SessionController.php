<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\SignIn;
use App\Application\Account\SignOut;
use App\Domain\Account\InvalidCredentials;
use App\Http\Requests\Account\SignInRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Signing in and out. Sign-in attempts are limited per email and address, which replaces the fixed
 * three-second pause of 2015.
 */
final readonly class SessionController
{
    /**
     * Failed attempts allowed per email and address before the sign-in waits.
     */
    private const int MAX_ATTEMPTS = 5;

    /**
     * How long, in seconds, failed attempts are counted.
     */
    private const int DECAY_SECONDS = 60;

    public function __construct(
        private SignIn $signIn,
        private SignOut $signOut,
    ) {}

    /**
     * The sign-in form.
     */
    public function create(): View
    {
        return view('accounts.login');
    }

    /**
     * Signs in and opens a new session.
     *
     * @throws ValidationException When the attempts are used up or the email or password is wrong.
     */
    public function store(SignInRequest $request): RedirectResponse
    {
        $key = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => __('accounts.errors.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        try {
            $id = $this->signIn->handle($request->string('email')->toString(), $request->string('password')->toString());
        } catch (InvalidCredentials) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('accounts.errors.credentials')]);
        }

        RateLimiter::clear($key);
        Auth::loginUsingId($id->value, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('profile'));
    }

    /**
     * Signs out, ends the session and renews the form token.
     *
     * @throws AuthenticationException When nobody is signed in.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->signOut->handle(SignedInAccount::id($request));
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

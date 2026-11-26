<?php

declare(strict_types=1);

namespace App\Http\Controllers\Feedback;

use App\Application\Feedback\SendFeedback;
use App\Domain\Feedback\FeedbackSubject;
use App\Domain\Feedback\InvalidFeedback;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Feedback\FeedbackRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * The feedback form (the 2015 `Feedback.html`): the signed-in person writes to the administrators, who
 * read the messages in the admin panel.
 */
final readonly class FeedbackController
{
    public function __construct(
        private SendFeedback $send,
    ) {}

    /**
     * The form, with the subjects to choose from.
     */
    public function create(): View
    {
        return view('feedback.create', ['subjects' => FeedbackSubject::cases()]);
    }

    /**
     * Sends the message.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When a value is refused.
     */
    public function store(FeedbackRequest $request): RedirectResponse
    {
        try {
            $this->send->handle(SignedInAccount::id($request), $request->toInput());
        } catch (InvalidFeedback $error) {
            throw ValidationException::withMessages([$error->field->value => __("feedback.errors.{$error->field->value}")]);
        }

        return redirect()->route('feedback')->with('status', __('feedback.status.sent'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Feedback;

use App\Application\Feedback\FeedbackInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The feedback form: a subject and a message. The domain checks the values; this request checks only
 * their shape.
 */
final class FeedbackRequest extends FormRequest
{
    /**
     * The longest text a field accepts; the domain allows less.
     */
    private const int MAX_LENGTH = 1000;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'message' => ['required', 'string', 'max:'.self::MAX_LENGTH],
        ];
    }

    /**
     * The input of the use case that sends the message.
     */
    public function toInput(): FeedbackInput
    {
        return new FeedbackInput($this->string('subject')->toString(), $this->string('message')->toString());
    }
}

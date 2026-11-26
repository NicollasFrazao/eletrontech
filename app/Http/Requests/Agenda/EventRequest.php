<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use App\Application\Agenda\EventInput;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The event form: a title, the date and time, and a description. The domain checks the values; this
 * request checks only their shape.
 */
final class EventRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'starts_at' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'description' => ['nullable', 'string', 'max:'.self::MAX_LENGTH],
        ];
    }

    /**
     * The input of the event use cases.
     */
    public function toInput(): EventInput
    {
        return new EventInput(
            $this->string('title')->toString(),
            $this->string('starts_at')->toString(),
            $this->string('description')->toString(),
        );
    }
}

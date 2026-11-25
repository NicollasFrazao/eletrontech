<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Domain\Account\Preferences;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The profile form for the night mode and the tutorial; an unticked box is not sent, and means off.
 */
final class UpdatePreferencesRequest extends FormRequest
{
    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['is_night_mode' => ['boolean'], 'shows_tutorial' => ['boolean']];
    }

    /**
     * The preferences the form holds.
     */
    public function toPreferences(): Preferences
    {
        return new Preferences($this->boolean('is_night_mode'), $this->boolean('shows_tutorial'));
    }
}

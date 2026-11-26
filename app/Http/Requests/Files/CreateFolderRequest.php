<?php

declare(strict_types=1);

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The new-folder form: a name and the folder it goes in (none for the root).
 */
final class CreateFolderRequest extends FormRequest
{
    /**
     * The longest text the name field accepts; the domain allows 100 characters.
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
            'name' => ['required', 'string', 'max:'.self::MAX_LENGTH],
            'parent' => ['nullable', 'integer'],
        ];
    }

    /**
     * The parent folder, or null for the root.
     */
    public function parent(): ?int
    {
        return $this->filled('parent') ? $this->integer('parent') : null;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The upload of a profile photo or cover: a JPEG, PNG or WebP picture of at most 5 MB and 6000 pixels a side.
 */
final class UploadImageRequest extends FormRequest
{
    /**
     * The largest upload, in kilobytes.
     */
    private const int MAX_KILOBYTES = 5120;

    /**
     * The widest and tallest picture accepted, in pixels.
     */
    private const int MAX_SIDE = 6000;

    /**
     * The shape of the input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimes:jpeg,png,webp',
                'max:'.self::MAX_KILOBYTES,
                'dimensions:max_width='.self::MAX_SIDE.',max_height='.self::MAX_SIDE,
            ],
        ];
    }

    /**
     * The bytes of the uploaded picture.
     */
    public function contents(): string
    {
        $file = $this->file('image');

        return is_array($file) || $file === null ? '' : $file->getContent();
    }
}

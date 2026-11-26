<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use Illuminate\Http\RedirectResponse;

/**
 * A redirect to a folder of the file manager, or to its root.
 */
final class FolderRedirect
{
    /**
     * The redirect to the folder (null for the root), with a status line when one is given.
     */
    public static function to(?int $folder, ?string $status = null): RedirectResponse
    {
        $redirect = $folder === null ? redirect()->route('files.root') : redirect()->route('files.folder', $folder);

        return $status === null ? $redirect : $redirect->with('status', $status);
    }
}

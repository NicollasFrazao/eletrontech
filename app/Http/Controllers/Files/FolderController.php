<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Application\Files\CreateFolder;
use App\Application\Files\DeleteEntry;
use App\Application\Files\DownloadFolder;
use App\Application\Files\ShowFolder;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;
use App\Domain\Files\StorageLimit;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Files\CreateFolderRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The folders of the signed-in person's file manager: open one, create one, download one as zip, delete
 * one. Another person's folder is not found.
 */
final readonly class FolderController
{
    public function __construct(
        private ShowFolder $show,
        private CreateFolder $create,
        private DownloadFolder $download,
        private DeleteEntry $delete,
    ) {}

    /**
     * The root, or one folder, with what it holds.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the folder is not the person's.
     */
    public function show(Request $request, ?int $folder = null): View
    {
        try {
            $view = $this->show->handle(SignedInAccount::id($request), $folder);
        } catch (FolderNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        return view('files.folder', ['folder' => $folder, 'view' => $view, 'quota' => StorageLimit::QUOTA_BYTES]);
    }

    /**
     * Creates a folder and opens it.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When the name is refused or taken, or the parent is not the person's.
     */
    public function store(CreateFolderRequest $request): RedirectResponse
    {
        try {
            $id = $this->create->handle(SignedInAccount::id($request), $request->parent(), $request->string('name')->toString());
        } catch (InvalidEntryName) {
            throw ValidationException::withMessages(['name' => __('files.errors.name')]);
        } catch (NameTaken) {
            throw ValidationException::withMessages(['name' => __('files.errors.taken')]);
        } catch (FolderNotFound) {
            throw ValidationException::withMessages(['parent' => __('files.errors.folder')]);
        }

        return FolderRedirect::to($id->value);
    }

    /**
     * The folder and everything in it as a zip archive.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the folder is not the person's, or a file's content is gone.
     */
    public function download(Request $request, int $folder): Response
    {
        try {
            return Attachment::of($this->download->handle(SignedInAccount::id($request), $folder));
        } catch (FolderNotFound|FileNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }
    }

    /**
     * Deletes the folder with everything in it and goes back to its parent.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the folder is not the person's.
     */
    public function destroy(Request $request, int $folder): RedirectResponse
    {
        $owner = SignedInAccount::id($request);

        try {
            $path = $this->show->handle($owner, $folder)->path;
            $this->delete->folder($owner, $folder);
        } catch (FolderNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        $deleted = $path[array_key_last($path)] ?? null;

        return FolderRedirect::to($deleted?->parent?->value, __('files.status.deleted'));
    }
}

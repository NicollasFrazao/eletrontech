<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Application\Files\DeleteEntry;
use App\Application\Files\DownloadFile;
use App\Application\Files\UploadFile;
use App\Domain\Files\FileNotFound;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\FolderNotFound;
use App\Domain\Files\InvalidEntryName;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use App\Domain\Files\RefusedFileType;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Files\UploadRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The files of the signed-in person's file manager: upload, properties, download, delete. Another
 * person's file is not found.
 */
final readonly class FileController
{
    public function __construct(
        private UploadFile $upload,
        private DownloadFile $download,
        private DeleteEntry $delete,
    ) {}

    /**
     * Stores the upload in the folder and shows the folder.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When the name, the type, the size or the folder is refused.
     */
    public function store(UploadRequest $request): RedirectResponse
    {
        try {
            $this->upload->handle($request->toInput());
        } catch (InvalidEntryName) {
            throw ValidationException::withMessages(['file' => __('files.errors.name')]);
        } catch (NameTaken) {
            throw ValidationException::withMessages(['file' => __('files.errors.taken')]);
        } catch (RefusedFileType) {
            throw ValidationException::withMessages(['file' => __('files.errors.type')]);
        } catch (FileTooLarge) {
            throw ValidationException::withMessages(['file' => __('files.errors.too_large')]);
        } catch (QuotaExceeded) {
            throw ValidationException::withMessages(['file' => __('files.errors.quota')]);
        } catch (FolderNotFound) {
            throw ValidationException::withMessages(['folder' => __('files.errors.folder')]);
        }

        return FolderRedirect::to($request->folder(), __('files.status.uploaded'));
    }

    /**
     * The file's properties.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the file is not the person's.
     */
    public function show(Request $request, int $file): View
    {
        try {
            $properties = $this->download->properties(SignedInAccount::id($request), $file);
        } catch (FileNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        return view('files.properties', ['file' => $properties]);
    }

    /**
     * The file's content as an attachment.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the file is not the person's.
     */
    public function download(Request $request, int $file): Response
    {
        try {
            return Attachment::of($this->download->handle(SignedInAccount::id($request), $file));
        } catch (FileNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }
    }

    /**
     * Deletes the file and goes back to its folder.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the file is not the person's.
     */
    public function destroy(Request $request, int $file): RedirectResponse
    {
        $owner = SignedInAccount::id($request);

        try {
            $folder = $this->download->properties($owner, $file)->entry->folder;
            $this->delete->file($owner, $file);
        } catch (FileNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        return FolderRedirect::to($folder?->value, __('files.status.deleted'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Application\Account\ReplaceProfileImage;
use App\Application\Account\ShowProfileImage;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\ImageKind;
use App\Domain\Account\UnreadableImage;
use App\Http\Requests\Account\UploadImageRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The profile photo and cover of the signed-in owner: the upload, and the picture itself, which is kept
 * off the public folder and served only to its owner.
 */
final readonly class ProfileImageController
{
    public function __construct(
        private ReplaceProfileImage $replace,
        private ShowProfileImage $show,
    ) {}

    /**
     * Replaces the picture of that kind.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     * @throws ValidationException When the upload is not a picture that can be read.
     */
    public function store(UploadImageRequest $request, ImageKind $kind): RedirectResponse
    {
        try {
            $this->replace->handle(SignedInAccount::id($request), $kind, $request->contents());
        } catch (UnreadableImage) {
            throw ValidationException::withMessages(['image' => __('accounts.errors.image')]);
        }

        return redirect()->route('profile')->with('status', __("accounts.status.saved.{$kind->value}"));
    }

    /**
     * The picture of that kind, as JPEG.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     * @throws NotFoundHttpException When the account has no picture of that kind.
     */
    public function show(Request $request, ImageKind $kind): Response
    {
        $jpeg = $this->show->handle(SignedInAccount::id($request), $kind) ?? throw new NotFoundHttpException;

        return new Response($jpeg, Response::HTTP_OK, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

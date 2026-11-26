<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Application\Files\Download;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * A download as an HTTP response that a browser saves and never runs or shows in place.
 */
final class Attachment
{
    /**
     * The response: the kind's media type, `attachment` with the name, and no type sniffing.
     */
    public static function of(Download $download): Response
    {
        $fallback = preg_replace('/[^\x20-\x7E]|[%\/\\\\]/', '_', $download->name) ?? 'download';

        return new Response($download->content, Response::HTTP_OK, [
            'Content-Type' => $download->mediaType,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $download->name, $fallback),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

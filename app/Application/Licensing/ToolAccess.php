<?php

declare(strict_types=1);

namespace App\Application\Licensing;

/**
 * The answer of {@see AuthoriseToolUse}: the tool may be used, the licence does not cover it, or there
 * is no such tool.
 */
enum ToolAccess
{
    case Allowed;
    case NotLicensed;
    case UnknownTool;
}

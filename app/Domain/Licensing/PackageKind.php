<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * How a package is had: the free one comes with a new account, the ready-made ones are bought in the
 * shop, a custom one holds the tools its owner picked.
 */
enum PackageKind: string
{
    case Free = 'free';
    case ReadyMade = 'ready_made';
    case Custom = 'custom';
}

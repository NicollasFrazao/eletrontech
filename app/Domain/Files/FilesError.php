<?php

declare(strict_types=1);

namespace App\Domain\Files;

use DomainException;

/**
 * A rule of the file manager was broken; nothing was stored or removed.
 */
abstract class FilesError extends DomainException {}

<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

use DomainException;

/**
 * A rule of packages and licences was broken; the operation changed nothing.
 */
abstract class LicensingError extends DomainException {}

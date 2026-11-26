<?php

declare(strict_types=1);

namespace App\Domain\Agenda;

use DomainException;

/**
 * A rule of the personal events was broken; nothing was stored.
 */
abstract class AgendaError extends DomainException {}

<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

use DomainException;

/**
 * A rule of the feedback form was broken; nothing was stored.
 */
abstract class FeedbackError extends DomainException {}

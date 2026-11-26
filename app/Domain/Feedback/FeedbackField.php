<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

/**
 * The values of a feedback message a person types, named as the form names them.
 */
enum FeedbackField: string
{
    case Subject = 'subject';
    case Message = 'message';
}

<?php

declare(strict_types=1);

namespace App\Domain\Feedback;

/**
 * What a feedback message is about: the seven subjects of the 2015 form (`Feedback.html`).
 */
enum FeedbackSubject: string
{
    case Question = 'question';
    case Praise = 'praise';
    case Bug = 'bug';
    case Interface = 'interface';
    case Performance = 'performance';
    case Suggestion = 'suggestion';
    case Other = 'other';
}

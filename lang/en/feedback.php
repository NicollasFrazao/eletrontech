<?php

declare(strict_types=1);

return [
    'title' => 'Feedback',
    'intro' => 'Tell us about your experience with EletronTech. Your message reaches the administrators.',
    'choose' => 'Choose a subject',
    'fields' => [
        'subject' => 'Subject',
        'message' => 'Message',
    ],
    'subjects' => [
        'question' => 'Question',
        'praise' => 'Praise',
        'bug' => 'Something went wrong',
        'interface' => 'Interface',
        'performance' => 'Slowness',
        'suggestion' => 'Suggestion',
        'other' => 'Other',
    ],
    'actions' => [
        'send' => 'Send message',
    ],
    'status' => [
        'sent' => 'Thank you! Your message was sent to the administrators.',
    ],
    'errors' => [
        'subject' => 'Choose one of the subjects.',
        'message' => 'Write a message of up to 500 characters.',
    ],
];

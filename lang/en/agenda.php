<?php

declare(strict_types=1);

return [
    'title' => 'My events',
    'empty' => 'No events.',
    'search' => 'Search events',
    'results' => 'Events matching ":term"',
    'fields' => [
        'title' => 'Title',
        'starts_at' => 'Date and time',
        'description' => 'Description',
    ],
    'actions' => [
        'add' => 'Add event',
        'save' => 'Save',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'search' => 'Search',
        'previous' => 'Previous month',
        'next' => 'Next month',
        'export_pdf' => 'Export to PDF',
        'export_txt' => 'Export to TXT',
    ],
    'export' => [
        'title' => 'My events',
    ],
    'status' => [
        'added' => 'Event added.',
        'updated' => 'Event saved.',
        'deleted' => 'Event deleted.',
        'exported' => 'Your events were saved in the folder "Events".',
    ],
    'errors' => [
        'title' => 'Type a title of up to 50 characters.',
        'starts_at' => 'Choose a valid date and time.',
        'description' => 'Use up to 500 characters.',
        'export_again' => 'An export was just made; try again in a second.',
    ],
];

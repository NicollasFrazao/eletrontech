<?php

declare(strict_types=1);

return [
    'title' => 'My files',
    'root' => 'My files',
    'empty' => 'This folder is empty.',
    'usage' => ':used of :quota used',
    'fields' => [
        'name' => 'New folder',
        'file' => 'File to upload (PDF, TXT, CSV, PNG, JPG, GIF, WebP, DOCX, XLSX, ODT, ODS; up to 10 MB)',
    ],
    'actions' => [
        'create' => 'Create folder',
        'upload' => 'Upload',
        'open' => 'Open',
        'download' => 'Download',
        'zip' => 'Download as zip',
        'properties' => 'Properties',
        'delete' => 'Delete',
    ],
    'properties' => [
        'name' => 'Name',
        'type' => 'Type',
        'size' => 'Size',
        'created' => 'Created',
        'bytes' => ':bytes bytes',
    ],
    'status' => [
        'uploaded' => 'File uploaded.',
        'deleted' => 'Deleted.',
    ],
    'errors' => [
        'name' => 'Use up to 100 characters, without / \\ : * ? " < > |.',
        'taken' => 'This folder already holds something with this name.',
        'type' => 'This kind of file is not accepted, or its content does not match its extension.',
        'too_large' => 'The file is larger than 10 MB.',
        'quota' => 'Your files would take more than 100 MB.',
        'folder' => 'This folder does not exist.',
    ],
];

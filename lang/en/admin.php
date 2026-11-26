<?php

declare(strict_types=1);

return [
    'accounts' => [
        'label' => 'Account',
        'plural_label' => 'Accounts',
        'columns' => [
            'id' => 'Id',
            'role' => 'Role',
            'status' => 'Status',
        ],
        'actions' => [
            'deactivate' => 'Deactivate',
            'reactivate' => 'Reactivate',
        ],
        'confirm' => [
            'deactivate' => 'The account is kept for 30 days. Its owner reactivates it by signing in before then; '
                .'after then it is closed and its personal data erased.',
            'delete' => 'The CPF, email, phone, password and pictures of the account are erased now. '
                .'The name and the activity log are kept for the records. This cannot be undone.',
        ],
        'done' => [
            'deactivated' => 'The account was deactivated.',
            'reactivated' => 'The account was reactivated.',
            'refused' => 'This account cannot be changed: it may be your own, or it was closed meanwhile.',
        ],
    ],
    'roles' => [
        'member' => 'Member',
        'administrator' => 'Administrator',
    ],
    'statuses' => [
        'active' => 'Active',
        'deactivated' => 'Deactivated',
    ],
];

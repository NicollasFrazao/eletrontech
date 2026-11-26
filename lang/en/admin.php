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
    'packages' => [
        'label' => 'Package',
        'plural_label' => 'Packages',
        'fields' => [
            'name' => 'Name',
            'kind' => 'Kind',
            'tools' => 'Tools',
            'price' => 'Price',
            'days' => 'Days of use',
        ],
        'help' => [
            'essentials' => 'Every package also carries the basic calculator, the unit converter and the rule of three.',
            'days' => 'The days the price is for, from 7 to 365.',
        ],
        'kinds' => [
            'free' => 'Free',
            'ready_made' => 'Ready-made',
            'custom' => 'Custom',
        ],
        'errors' => [
            'name' => 'The name has from 1 to 100 characters.',
            'tools' => 'Choose at least one tool besides the everyday ones.',
            'price' => 'The price is an amount from 0.00 to 99999.99, with at most two decimal places.',
            'days' => 'The days of use are from 7 to 365.',
            'refused' => 'This package cannot be changed.',
        ],
    ],
    'tools' => [
        'label' => 'Tool',
        'plural_label' => 'Tools',
        'fields' => [
            'id' => 'Identifier',
            'name' => 'Name',
            'packages' => 'Packages',
        ],
        'errors' => [
            'name' => 'The name has from 1 to 100 characters.',
            'taken' => 'Another tool already has this name.',
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

<?php

declare(strict_types=1);

return [
    'title' => [
        'shop' => 'Eletron Tech Shop',
        'custom' => 'Custom package',
        'licence' => 'My licence',
    ],
    'intro' => 'Packages give access to tools for the days you choose. Nothing is charged.',
    'custom_intro' => 'Pick the tools of your own package.',
    'see_more' => 'See more',
    'tools' => 'Tools',
    'fields' => [
        'days' => 'Days of use (:min to :max)',
        'purchase_password' => 'Your password, to confirm',
    ],
    'actions' => [
        'buy' => 'Buy',
        'shop' => 'Go to the shop',
        'custom' => 'Build a custom package',
    ],
    'licence' => [
        'package' => 'Package',
        'period' => 'From :start to :end',
        'remaining' => ':days days left',
        'ended' => 'This licence has ended. Buy a package to use the tools again.',
        'none' => 'You have no licence yet.',
        'renewal' => 'Buying the same package while it runs adds the days to its end; any other package replaces it from today.',
    ],
    'status' => [
        'bought' => 'Purchase complete.',
    ],
    'errors' => [
        'password' => 'The password is wrong.',
        'days' => 'Choose from 7 to 365 days.',
        'package' => 'This package is not for sale.',
        'tools' => 'Pick at least one tool from the list.',
        'not_licensed' => 'Your licence does not cover this tool, or it has ended.',
    ],
];

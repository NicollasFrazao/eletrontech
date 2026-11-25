<?php

declare(strict_types=1);

namespace App\Domain\Account;

/**
 * What a phone number is, as the 2015 sign-up asked (`tp_telefone`).
 */
enum PhoneType: string
{
    case Home = 'home';
    case Mobile = 'mobile';
    case Business = 'business';
}

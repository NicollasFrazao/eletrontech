<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Application\Licensing\ShowLicence;
use App\Http\Controllers\Account\SignedInAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * The licence page (the 2015 `Licenca.php`): the package, its period, the days left and the tools.
 */
final readonly class LicenceController
{
    public function __construct(
        private ShowLicence $licence,
    ) {}

    /**
     * The signed-in person's licence.
     *
     * @throws AuthenticationException When nobody is signed in.
     */
    public function show(Request $request): View
    {
        return view('shop.licence', ['licence' => $this->licence->handle(SignedInAccount::id($request))]);
    }
}

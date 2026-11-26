<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Application\Licensing\BuyPackage;
use App\Domain\Account\AccountNotFound;
use App\Domain\Account\WrongPassword;
use App\Domain\Licensing\EmptyCustomPackage;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\PackageNotForSale;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use App\Http\Requests\Shop\PurchaseRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Buys a package, confirmed with the password; nothing is charged, as in 2015.
 */
final readonly class PurchaseController
{
    public function __construct(
        private BuyPackage $buy,
    ) {}

    /**
     * Buys the package and opens the licence page.
     *
     * @throws ValidationException When the password, the days, the package or a tool is refused.
     * @throws AuthenticationException When nobody is signed in.
     * @throws AccountNotFound When the account was closed meanwhile.
     */
    public function store(PurchaseRequest $request): RedirectResponse
    {
        try {
            $this->buy->handle($request->toInput());
        } catch (WrongPassword) {
            throw ValidationException::withMessages(['purchase_password' => __('shop.errors.password')]);
        } catch (InvalidUsageDays) {
            throw ValidationException::withMessages(['days' => __('shop.errors.days')]);
        } catch (UnknownPackage|PackageNotForSale) {
            throw ValidationException::withMessages(['package_id' => __('shop.errors.package')]);
        } catch (EmptyCustomPackage|UnknownTool) {
            throw ValidationException::withMessages(['tools' => __('shop.errors.tools')]);
        }

        return redirect()->route('licence')->with('status', __('shop.status.bought'));
    }
}

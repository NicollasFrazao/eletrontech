<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Application\Licensing\ShowShop;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UsageDays;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The shop (the 2015 Eletron Tech Shop): the ready-made packages with their tools, one package with the
 * purchase form ("see more"), and the form of a custom package.
 */
final readonly class ShopController
{
    public function __construct(
        private ShowShop $shop,
    ) {}

    /**
     * Every ready-made package and the custom package.
     */
    public function index(): View
    {
        return view('shop.index', ['packages' => $this->shop->packages()]);
    }

    /**
     * One package with its tools and the purchase form.
     *
     * @throws NotFoundHttpException When no ready-made package has the id.
     */
    public function show(int $package): View
    {
        try {
            $view = $this->shop->package($package);
        } catch (UnknownPackage $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        }

        return view('shop.package', ['package' => $view, 'minDays' => UsageDays::MIN, 'maxDays' => UsageDays::MAX]);
    }

    /**
     * The custom package: every tool to pick from, and the purchase form.
     */
    public function custom(): View
    {
        return view('shop.custom', ['tools' => $this->shop->tools(), 'minDays' => UsageDays::MIN, 'maxDays' => UsageDays::MAX]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Utils\TyreShopUtil;
use Illuminate\Http\Request;

class TyreShopController extends Controller
{
    protected TyreShopUtil $tyreShopUtil;

    public function __construct(TyreShopUtil $tyreShopUtil)
    {
        $this->tyreShopUtil = $tyreShopUtil;
    }

    protected function guardModule(): void
    {
        if (! $this->tyreShopUtil->moduleEnabled()) {
            abort(403, __('lang_v1.tyre_shop_module_disabled'));
        }
    }

    /**
     * Tyre Shop dashboard (shortcuts when module is enabled).
     */
    public function index()
    {
        $this->guardModule();

        return view('tyre_shop.index');
    }
}

<?php

namespace App\Http\Controllers;

use App\Utils\HardwareShopUtil;

class HardwareShopController extends Controller
{
    protected HardwareShopUtil $util;

    public function __construct(HardwareShopUtil $util)
    {
        $this->util = $util;
    }

    protected function guardModule(): void
    {
        if (! $this->util->moduleEnabled()) {
            abort(403, __('lang_v1.hardware_shop_module_disabled'));
        }
    }

    public function index()
    {
        $this->guardModule();

        return view('hardware_shop.index');
    }
}

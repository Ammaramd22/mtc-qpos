<?php

namespace App\Http\Controllers;

use App\Utils\VehicleSparePartsUtil;

class VehicleSparePartsController extends Controller
{
    protected VehicleSparePartsUtil $util;

    public function __construct(VehicleSparePartsUtil $util)
    {
        $this->util = $util;
    }

    protected function guardModule(): void
    {
        if (! $this->util->moduleEnabled()) {
            abort(403, __('lang_v1.vehicle_spare_parts_module_disabled'));
        }
    }

    public function index()
    {
        $this->guardModule();

        return view('vehicle_spare_parts.index');
    }
}

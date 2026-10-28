<?php

namespace App\Http\Middleware;

use App\Utils\ModuleUtil;
use Closure;
use Menu;
use Modules\CustomDashboard\Entities\CustomDashboard;

class AdminSidebarMenu
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->ajax()) {
            return $next($request);
        }

        Menu::create('admin-sidebar-menu', function ($menu) {
            $enabled_modules = !empty(session('business.enabled_modules')) ? session('business.enabled_modules') : [];

            $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];
            $pos_settings = !empty(session('business.pos_settings')) ? json_decode(session('business.pos_settings'), true) : [];

            $is_software_owner = method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner();
            $is_admin = $is_software_owner
                || (auth()->user()->hasRole('Admin#' . session('business.id')) ? true : false);

            // Soft Owner personal module prefs (does not change shop Modules settings)
            if ($is_software_owner) {
                $enabled_modules = \App\Utils\SoftwareOwnerUtil::applyModulePrefsForUser(auth()->user(), $enabled_modules);
            }

            //Home
            //     $menu->url(action([\App\Http\Controllers\HomeController::class, 'index']), __('home.home'), ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            //     <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            //     <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            //     <path d="M5 12l-2 0l9 -9l9 9l-2 0"></path>
            //     <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"></path>
            //     <path d="M10 12h4v4h-4z"></path>
            //   </svg>', 'active' => request()->segment(1) == 'home'])->order(5);

            
                  
            $menu->url(action([\App\Http\Controllers\HomeController::class, 'index']), __('home.home'), ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
            <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
            <path d="M10 12h4v4h-4z" />
          </svg>', 'active' => request()->segment(1) == 'home'])->order(5);

            //User management dropdown
            if (feature_enabled('users') && (auth()->user()->can('user.view') || auth()->user()->can('user.create') || auth()->user()->can('roles.view'))) {
                $menu->dropdown(
                    __('user.user_management'),
                    function ($sub) {
                        if (auth()->user()->can('user.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ManageUserController::class, 'index']),
                                __('user.users'),
                                ['icon' => '', 'active' => request()->segment(1) == 'users']
                            );
                        }
                        if (auth()->user()->can('roles.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\RoleController::class, 'index']),
                                __('user.roles'),
                                ['icon' => '', 'active' => request()->segment(1) == 'roles']
                            );
                        }
                        if (auth()->user()->can('user.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\SalesCommissionAgentController::class, 'index']),
                                __('lang_v1.sales_commission_agents'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sales-commission-agents']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                    <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    <path d="M21 21v-2a4 4 0 0 0 -3 -3.85"></path>
                  </svg>', ]
                )->order(10);
            }

            //Contacts dropdown
            if (feature_enabled('contacts') && (auth()->user()->can('supplier.view') || auth()->user()->can('customer.view') || auth()->user()->can('supplier.view_own') || auth()->user()->can('customer.view_own'))) {
                $menu->dropdown(
                    __('contact.contacts'),
                    function ($sub) {
                        if (auth()->user()->can('supplier.view') || auth()->user()->can('supplier.view_own')) {
                            $sub->url(
                                action([\App\Http\Controllers\ContactController::class, 'index'], ['type' => 'supplier']),
                                __('report.supplier'),
                                ['icon' => '', 'active' => request()->input('type') == 'supplier']
                            );
                        }
                        if (auth()->user()->can('customer.view') || auth()->user()->can('customer.view_own')) {
                            $sub->url(
                                action([\App\Http\Controllers\ContactController::class, 'index'], ['type' => 'customer']),
                                __('report.customer'),
                                ['icon' => '', 'active' => request()->input('type') == 'customer']
                            );
                            $sub->url(
                                action([\App\Http\Controllers\CustomerGroupController::class, 'index']),
                                __('lang_v1.customer_groups'),
                                ['icon' => '', 'active' => request()->segment(1) == 'customer-group']
                            );
                        }
                        if (auth()->user()->can('supplier.create') || auth()->user()->can('customer.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\ContactController::class, 'getImportContacts']),
                                __('lang_v1.import_contacts'),
                                ['icon' => '', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'import']
                            );
                        }

                        if (!empty(env('GOOGLE_MAP_API_KEY'))) {
                            $sub->url(
                                action([\App\Http\Controllers\ContactController::class, 'contactMap']),
                                __('lang_v1.map'),
                                ['icon' => 'fa fas fa-map-marker-alt', 'active' => request()->segment(1) == 'contacts' && request()->segment(2) == 'map']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M20 6v12a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2z"></path>
                    <path d="M10 16h6"></path>
                    <path d="M13 11m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                    <path d="M4 8h3"></path>
                    <path d="M4 12h3"></path>
                    <path d="M4 16h3"></path>
                  </svg>', 'id' => 'tour_step4']
                )->order(15);
            }

            //Products dropdown
            if (feature_enabled('products') && (auth()->user()->can('product.view') || auth()->user()->can('product.create') ||
                auth()->user()->can('brand.view') || auth()->user()->can('unit.view') ||
                auth()->user()->can('category.view') || auth()->user()->can('brand.create') ||
                auth()->user()->can('unit.create') || auth()->user()->can('category.create'))) {
                $menu->dropdown(
                    __('sale.products'),
                    function ($sub) use ($enabled_modules, $common_settings) {
                        if (auth()->user()->can('product.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ProductController::class, 'index']),
                                __('lang_v1.list_products'),
                                ['icon' => '', 'active' => request()->segment(1) == 'products' && request()->segment(2) == '']
                            );
                        }

                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\ProductController::class, 'create']),
                                __('product.add_product'),
                                ['icon' => '', 'active' => request()->segment(1) == 'products' && request()->segment(2) == 'create']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellingPriceGroupController::class, 'updateProductPrice']),
                                __('lang_v1.update_product_price'),
                                ['icon' => '', 'active' => request()->segment(1) == 'update-product-price']
                            );
                        }
                        if (feature_enabled('product_labels') && auth()->user()->can('product.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\LabelsController::class, 'show']),
                                __('barcode.print_labels'),
                                ['icon' => '', 'active' => request()->segment(1) == 'labels' && request()->segment(2) == 'show']
                            );
                        }
                        if (feature_enabled('variations') && auth()->user()->can('product.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\VariationTemplateController::class, 'index']),
                                __('product.variations'),
                                ['icon' => '', 'active' => request()->segment(1) == 'variation-templates']
                            );
                            $sub->url(
                                action([\App\Http\Controllers\ImportProductsController::class, 'index']),
                                __('product.import_products'),
                                ['icon' => '', 'active' => request()->segment(1) == 'import-products']
                            );
                        }
                        if (auth()->user()->can('product.opening_stock')) {
                            $sub->url(
                                action([\App\Http\Controllers\ImportOpeningStockController::class, 'index']),
                                __('lang_v1.import_opening_stock'),
                                ['icon' => '', 'active' => request()->segment(1) == 'import-opening-stock']
                            );
                        }
                        if (auth()->user()->can('product.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellingPriceGroupController::class, 'index']),
                                __('lang_v1.selling_price_group'),
                                ['icon' => '', 'active' => request()->segment(1) == 'selling-price-group']
                            );
                        }
                        if (auth()->user()->can('unit.view') || auth()->user()->can('unit.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\UnitController::class, 'index']),
                                __('unit.units'),
                                ['icon' => '', 'active' => request()->segment(1) == 'units']
                            );
                        }
                        if (session('business.enable_category')
                            && (auth()->user()->can('category.view') || auth()->user()->can('category.create'))) {
                            $sub->url(
                                action([\App\Http\Controllers\TaxonomyController::class, 'index']) . '?type=product',
                                __('category.categories'),
                                ['icon' => '', 'active' => request()->segment(1) == 'taxonomies' && request()->get('type') == 'product']
                            );
                        }
                        if (session('business.enable_brand')
                            && (auth()->user()->can('brand.view') || auth()->user()->can('brand.create'))) {
                            $sub->url(
                                action([\App\Http\Controllers\BrandController::class, 'index']),
                                __('brand.brands'),
                                ['icon' => '', 'active' => request()->segment(1) == 'brands']
                            );
                        }

                        if (! empty($common_settings['enable_product_warranty'])) {
                            $sub->url(
                                action([\App\Http\Controllers\WarrantyController::class, 'index']),
                                __('lang_v1.warranties'),
                                ['icon' => '', 'active' => request()->segment(1) == 'warranties']
                            );
                        }

                        if (feature_enabled('communication')
                            && in_array('communication', $enabled_modules)
                            && (auth()->user()->can('business_settings.access') || auth()->user()->can('product.view') || auth()->user()->can('product.update'))) {
                            $sub->url(
                                action([\App\Http\Controllers\CommunicationController::class, 'settings']),
                                __('lang_v1.communication'),
                                ['icon' => '', 'active' => request()->segment(1) == 'communication']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"></path>
                    <path d="M12 12l8 -4.5"></path>
                    <path d="M8.2 9.8l7.6 -4.6"></path>
                    <path d="M12 12v9"></path>
                    <path d="M12 12l-8 -4.5"></path>
                  </svg>', 'id' => 'tour_step5']
                )->order(20);
            }

            // Mobile Shop module (IMEI / repairs / warranty)
            if (in_array('mobile_shop', $enabled_modules) && (! function_exists('shop_mode_allows') || shop_mode_allows('mobile_shop'))) {
                $menu->dropdown(
                    __('lang_v1.mobile_shop'),
                    function ($sub) use ($enabled_modules, $common_settings) {
                        $sub->url(
                            action([\App\Http\Controllers\MobileShopController::class, 'index']),
                            __('lang_v1.mobile_shop_dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'mobile-shop' && empty(request()->segment(2))]
                        );
                        $sub->url(
                            action([\App\Http\Controllers\MobileShopController::class, 'imeiIndex']),
                            __('lang_v1.mobile_imei_inventory'),
                            ['icon' => '', 'active' => request()->segment(2) == 'imei']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\MobileShopController::class, 'warrantyLookup']),
                            __('lang_v1.mobile_warranty_lookup'),
                            ['icon' => '', 'active' => request()->segment(2) == 'warranty']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\MobileShopController::class, 'repairIndex']),
                            __('lang_v1.mobile_repairs'),
                            ['icon' => '', 'active' => request()->segment(2) == 'repairs']
                        );
                        // Keep bill payments under Mobile Shop when Reload is available
                        if (in_array('reload', $enabled_modules) || ! empty(($common_settings['reload']['myreload_enabled'] ?? null))) {
                            $sub->url(
                                action([\App\Http\Controllers\MobileShopController::class, 'reloadIndex']),
                                __('lang_v1.bill_payments'),
                                ['icon' => '', 'active' => request()->segment(1) == 'mobile-shop' && request()->segment(2) == 'reload']
                            );
                        }
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z"/><path d="M11 4h2"/><path d="M12 17v.01"/></svg>', 'active' => request()->segment(1) == 'mobile-shop']
                )->order(21);
            }

            // Tyre Shop module (size / quick services / exchange)
            if (in_array('tyre_shop', $enabled_modules) && (! function_exists('shop_mode_allows') || shop_mode_allows('tyre_shop'))) {
                $menu->dropdown(
                    __('lang_v1.tyre_shop'),
                    function ($sub) {
                        $sub->url(
                            action([\App\Http\Controllers\TyreShopController::class, 'index']),
                            __('lang_v1.tyre_shop_dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'tyre-shop']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\ProductController::class, 'index']),
                            __('sale.products'),
                            ['icon' => '', 'active' => request()->segment(1) == 'products']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\SellPosController::class, 'create']),
                            __('sale.pos_sale'),
                            ['icon' => '', 'active' => request()->segment(1) == 'pos']
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M12 3l0 2"/><path d="M12 19l0 2"/><path d="M3 12l2 0"/><path d="M19 12l2 0"/></svg>', 'active' => request()->segment(1) == 'tyre-shop']
                )->order(21);
            }

            // Vehicle Modification / Spare Parts / Hardware — never break the whole page if missing
            try {
            // Vehicle Modification Shop (workshop jobs / vehicles)
            if (in_array('vehicle_modification', $enabled_modules) && (! function_exists('shop_mode_allows') || shop_mode_allows('vehicle_modification'))) {
                $menu->dropdown(
                    __('lang_v1.vehicle_modification'),
                    function ($sub) {
                        $sub->url(
                            url('/vehicle-modification'),
                            __('lang_v1.vehicle_modification_dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'vehicle-modification' && empty(request()->segment(2))]
                        );
                        $sub->url(
                            url('/vehicle-modification/jobs'),
                            __('lang_v1.workshop_jobs'),
                            ['icon' => '', 'active' => request()->segment(2) == 'jobs']
                        );
                        $sub->url(
                            url('/vehicle-modification/vehicles'),
                            __('lang_v1.customer_vehicles'),
                            ['icon' => '', 'active' => request()->segment(2) == 'vehicles']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\ProductController::class, 'index']),
                            __('sale.products'),
                            ['icon' => '', 'active' => request()->segment(1) == 'products']
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2"/><path d="M9 17l6 0"/></svg>', 'active' => request()->segment(1) == 'vehicle-modification']
                )->order(21);
            }

            // Vehicle Spare Parts Shop
            if (in_array('vehicle_spare_parts', $enabled_modules) && (! function_exists('shop_mode_allows') || shop_mode_allows('vehicle_spare_parts'))) {
                $menu->dropdown(
                    __('lang_v1.vehicle_spare_parts'),
                    function ($sub) {
                        $sub->url(
                            url('/vehicle-spare-parts'),
                            __('lang_v1.vehicle_spare_parts_dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'vehicle-spare-parts']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\ProductController::class, 'index']),
                            __('sale.products'),
                            ['icon' => '', 'active' => request()->segment(1) == 'products']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\SellPosController::class, 'create']),
                            __('sale.pos_sale'),
                            ['icon' => '', 'active' => request()->segment(1) == 'pos']
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/></svg>', 'active' => request()->segment(1) == 'vehicle-spare-parts']
                )->order(21);
            }

            // Hardware Shop
            if (in_array('hardware_shop', $enabled_modules) && (! function_exists('shop_mode_allows') || shop_mode_allows('hardware_shop'))) {
                $menu->dropdown(
                    __('lang_v1.hardware_shop'),
                    function ($sub) {
                        $sub->url(
                            url('/hardware-shop'),
                            __('lang_v1.hardware_shop_dashboard'),
                            ['icon' => '', 'active' => request()->segment(1) == 'hardware-shop']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\ProductController::class, 'index']),
                            __('sale.products'),
                            ['icon' => '', 'active' => request()->segment(1) == 'products']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\SellPosController::class, 'create']),
                            __('sale.pos_sale'),
                            ['icon' => '', 'active' => request()->segment(1) == 'pos']
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11 17h6l1 4h-8z"/><path d="M16.5 13.5l2.5 -2.5l-8.5 -8.5l-5 5l8.5 8.5z"/><path d="M5 12l-2 2"/></svg>', 'active' => request()->segment(1) == 'hardware-shop']
                )->order(21);
            }
            } catch (\Throwable $e) {
                // Industry menus must not take down Products / Home
                \Log::warning('Industry sidebar skipped: '.$e->getMessage());
            }

            // Separate Reload module (MyReload API)
            $reload_settings = $common_settings['reload'] ?? [];
            if (in_array('reload', $enabled_modules) || ! empty($reload_settings['myreload_enabled'])) {
                $menu->dropdown(
                    __('lang_v1.reload_module'),
                    function ($sub) {
                        $sub->url(
                            action([\App\Http\Controllers\MobileShopController::class, 'reloadIndex']),
                            __('lang_v1.bill_payments'),
                            ['icon' => '', 'active' => (request()->segment(1) == 'reload') || (request()->segment(1) == 'mobile-shop' && request()->segment(2) == 'reload')]
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 3l0 7l6 0l-8 11l0 -7l-6 0l8 -11"/></svg>', 'active' => request()->segment(1) == 'reload' || (request()->segment(1) == 'mobile-shop' && request()->segment(2) == 'reload')]
                )->order(22);
            }

            // Nutmeg / Spices — Soft Owner feature + this business has nutmeg module
            if (function_exists('nutmeg_enabled') && nutmeg_enabled()) {
                $menu->dropdown(
                    __('lang_v1.nutmeg'),
                    function ($sub) {
                        if (auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase')
                            || auth()->user()->can('purchase.create') || auth()->user()->can('purchase.update')) {
                            $sub->url(
                                action([\App\Http\Controllers\ProductExpenseController::class, 'index']),
                                __('lang_v1.product_expenses'),
                                ['icon' => '', 'active' => request()->segment(1) == 'product-expenses']
                            );
                        }
                        if (auth()->user()->can('product.view') || auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\NutmegDryingController::class, 'index']),
                                __('lang_v1.nutmeg_drying'),
                                ['icon' => '', 'active' => request()->segment(1) == 'nutmeg' && request()->segment(2) == 'drying']
                            );
                            if (report_enabled('nutmeg')) {
                                $sub->url(
                                    action([\App\Http\Controllers\NutmegReportController::class, 'index']),
                                    __('lang_v1.nutmeg_cost_report'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'nutmeg' && request()->segment(2) == 'reports']
                                );
                            }
                        }
                        if (auth()->user()->can('business_settings.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\NutmegSettingsController::class, 'settings']),
                                __('lang_v1.nutmeg_settings'),
                                ['icon' => '', 'active' => request()->segment(1) == 'nutmeg' && request()->segment(2) == 'settings']
                            );
                        }
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z"/><path d="M17 4a2 2 0 0 0 2 2a2 2 0 0 0 -2 2a2 2 0 0 0 -2 -2a2 2 0 0 0 2 -2"/><path d="M19 11h2m-1 -1v2"/></svg>', 'active' => request()->segment(1) == 'nutmeg' || request()->segment(1) == 'product-expenses']
                )->order(23);
            }

            //Purchase dropdown
            if (feature_enabled('purchases') && in_array('purchases', $enabled_modules) && (auth()->user()->can('purchase.view') || auth()->user()->can('purchase.create') || auth()->user()->can('purchase.update'))) {
                $menu->dropdown(
                    __('purchase.purchases'),
                    function ($sub) use ($common_settings, $pos_settings, $enabled_modules) {
                        if (!empty($common_settings['enable_purchase_requisition']) && (auth()->user()->can('purchase_requisition.view_all') || auth()->user()->can('purchase_requisition.view_own'))) {
                            $sub->url(
                                action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']),
                                __('lang_v1.purchase_requisition'),
                                ['icon' => '', 'active' => request()->segment(1) == 'purchase-requisition']
                            );
                        }

                        if (!empty($common_settings['enable_purchase_order']) && (auth()->user()->can('purchase_order.view_all') || auth()->user()->can('purchase_order.view_own'))) {
                            $sub->url(
                                action([\App\Http\Controllers\PurchaseOrderController::class, 'index']),
                                __('lang_v1.purchase_order'),
                                ['icon' => '', 'active' => request()->segment(1) == 'purchase-order']
                            );
                        }
                        if (auth()->user()->can('purchase.view') || auth()->user()->can('view_own_purchase')) {
                            $sub->url(
                                action([\App\Http\Controllers\PurchaseController::class, 'index']),
                                __('purchase.list_purchase'),
                                ['icon' => '', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('purchase.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\PurchaseController::class, 'create']),
                                __('purchase.add_purchase'),
                                ['icon' => '', 'active' => request()->segment(1) == 'purchases' && request()->segment(2) == 'create']
                            );
                            if (!empty($pos_settings['enable_pop']) || in_array('pos_purchase', $enabled_modules)) {
                                $sub->url(
                                    action([\App\Http\Controllers\PurchasePosController::class, 'create']),
                                    __('lang_v1.point_of_purchase'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'purchase-pos']
                                );
                            }
                        }
                        if (auth()->user()->can('purchase.update')) {
                            $sub->url(
                                action([\App\Http\Controllers\PurchaseReturnController::class, 'index']),
                                __('lang_v1.list_purchase_return'),
                                ['icon' => '', 'active' => request()->segment(1) == 'purchase-return']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 3v12"></path>
                    <path d="M16 11l-4 4l-4 -4"></path>
                    <path d="M3 12a9 9 0 0 0 18 0"></path>
                  </svg>', 'id' => 'tour_step6']
                )->order(25);
            }
            //Sell dropdown
            if (feature_enabled('sales') && ($is_admin || auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping', 'access_sell_return', 'direct_sell.view', 'direct_sell.update', 'access_own_sell_return']))) {
                $menu->dropdown(
                    __('sale.sale'),
                    function ($sub) use ($enabled_modules, $is_admin, $pos_settings) {
                        if (!empty($pos_settings['enable_sales_order']) && ($is_admin || auth()->user()->hasAnyPermission(['so.view_own', 'so.view_all', 'so.create']))) {
                            $sub->url(
                                action([\App\Http\Controllers\SalesOrderController::class, 'index']),
                                __('lang_v1.sales_order'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sales-order']
                            );
                        }

                        if ($is_admin || auth()->user()->hasAnyPermission(['sell.view', 'sell.create', 'direct_sell.access', 'direct_sell.view', 'view_own_sell_only', 'view_commission_agent_sell', 'access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'index']),
                                __('lang_v1.all_sales'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == null]
                            );
                        }
                        if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'create']),
                                __('sale.add_sale'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'create' && empty(request()->get('status'))]
                            );
                        }
                        if (auth()->user()->can('sell.create')) {
                            if (in_array('pos_sale', $enabled_modules)) {
                                if (auth()->user()->can('sell.view')) {
                                    $sub->url(
                                        action([\App\Http\Controllers\SellPosController::class, 'index']),
                                        __('sale.list_pos'),
                                        ['icon' => '', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == null]
                                    );
                                }

                                $sub->url(
                                    action([\App\Http\Controllers\SellPosController::class, 'create']),
                                    __('sale.pos_sale'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'pos' && request()->segment(2) == 'create']
                                );
                            }
                            if ((!empty($pos_settings['enable_pop']) || in_array('pos_purchase', $enabled_modules)) && auth()->user()->can('purchase.create')) {
                                $sub->url(
                                    action([\App\Http\Controllers\PurchasePosController::class, 'create']),
                                    __('lang_v1.pop_short'),
                                    ['icon' => '', 'active' => request()->segment(1) == 'purchase-pos']
                                );
                            }
                        }

                        if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'draft']),
                                __('lang_v1.add_draft'),
                                ['icon' => '', 'active' => request()->get('status') == 'draft']
                            );
                        }
                        if (in_array('add_sale', $enabled_modules) && ($is_admin || auth()->user()->hasAnyPermission(['draft.view_all', 'draft.view_own']))) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'getDrafts']),
                                __('lang_v1.list_drafts'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'drafts']
                            );
                        }
                        if (in_array('add_sale', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'create'], ['status' => 'quotation']),
                                __('lang_v1.add_quotation'),
                                ['icon' => '', 'active' => request()->get('status') == 'quotation']
                            );
                        }
                        if (in_array('add_sale', $enabled_modules) && ($is_admin || auth()->user()->hasAnyPermission(['quotation.view_all', 'quotation.view_own']))) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'getQuotations']),
                                __('lang_v1.list_quotations'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sells' && request()->segment(2) == 'quotations']
                            );
                        }

                        if (auth()->user()->can('access_sell_return') || auth()->user()->can('access_own_sell_return')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellReturnController::class, 'index']),
                                __('lang_v1.list_sell_return'),
                                ['icon' => '', 'active' => request()->segment(1) == 'sell-return' && request()->segment(2) == null]
                            );
                        }

                        if ($is_admin || auth()->user()->hasAnyPermission(['access_shipping', 'access_own_shipping', 'access_commission_agent_shipping'])) {
                            $sub->url(
                                action([\App\Http\Controllers\SellController::class, 'shipments']),
                                __('lang_v1.shipments'),
                                ['icon' => '', 'active' => request()->segment(1) == 'shipments']
                            );
                        }

                        if (auth()->user()->can('discount.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\DiscountController::class, 'index']),
                                __('lang_v1.discounts'),
                                ['icon' => '', 'active' => request()->segment(1) == 'discount']
                            );
                        }
                        if (in_array('subscription', $enabled_modules) && auth()->user()->can('direct_sell.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\SellPosController::class, 'listSubscriptions']),
                                __('lang_v1.subscriptions'),
                                ['icon' => '', 'active' => request()->segment(1) == 'subscriptions']
                            );
                        }

                        if (auth()->user()->can('sell.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\ImportSalesController::class, 'index']),
                                __('lang_v1.import_sales'),
                                ['icon' => '', 'active' => request()->segment(1) == 'import-sales']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 15v-12"></path>
                    <path d="M16 7l-4 -4l-4 4"></path>
                    <path d="M3 12a9 9 0 0 0 18 0"></path>
                  </svg>', 'id' => 'tour_step7']
                )->order(30);
            }

            //Stock transfer dropdown
            if (feature_enabled('stock_transfers') && in_array('stock_transfers', $enabled_modules) && (auth()->user()->can('stock_transfer.view') || auth()->user()->can('stock_transfer.create') || auth()->user()->can('stock_transfer.view_own'))) {
                $menu->dropdown(
                    __('lang_v1.stock_transfers'),
                    function ($sub) {
                        if (auth()->user()->can('stock_transfer.view') || auth()->user()->can('stock_transfer.view_own')) {
                            $sub->url(
                                action([\App\Http\Controllers\StockTransferController::class, 'index']),
                                __('lang_v1.list_stock_transfers'),
                                ['icon' => '', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('stock_transfer.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\StockTransferController::class, 'create']),
                                __('lang_v1.add_stock_transfer'),
                                ['icon' => '', 'active' => request()->segment(1) == 'stock-transfers' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                    <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                    <path d="M5 17h-2v-4m-1 -8h11v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"></path>
                    <path d="M3 9l4 0"></path>
                  </svg>']
                )->order(35);
            }

            //stock adjustment dropdown
            if (feature_enabled('stock_adjustment') && in_array('stock_adjustment', $enabled_modules) && (auth()->user()->can('stock_adjustment.view') || auth()->user()->can('stock_adjustment.create') || auth()->user()->can('view_own_stock_adjustment'))) {
                $menu->dropdown(
                    __('stock_adjustment.stock_adjustment'),
                    function ($sub) {
                        if (auth()->user()->can('stock_adjustment.view')  || auth()->user()->can('view_own_stock_adjustment')) {
                            $sub->url(
                                action([\App\Http\Controllers\StockAdjustmentController::class, 'index']),
                                __('stock_adjustment.list'),
                                ['icon' => '', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == null]
                            );
                        }
                        if (auth()->user()->can('stock_adjustment.create')) {
                            $sub->url(
                                action([\App\Http\Controllers\StockAdjustmentController::class, 'create']),
                                __('stock_adjustment.add'),
                                ['icon' => '', 'active' => request()->segment(1) == 'stock-adjustments' && request()->segment(2) == 'create']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"></path>
                    <path d="M4 6v6a8 3 0 0 0 16 0v-6"></path>
                    <path d="M4 12v6a8 3 0 0 0 16 0v-6"></path>
                  </svg>']
                )->order(40);
            }

            //Expense dropdown
            if (feature_enabled('expenses') && in_array('expenses', $enabled_modules) && (auth()->user()->can('all_expense.access') || auth()->user()->can('view_own_expense'))) {
                $menu->dropdown(
                    __('expense.expenses'),
                    function ($sub) {
                        $sub->url(
                            action([\App\Http\Controllers\ExpenseController::class, 'index']),
                            __('lang_v1.list_expenses'),
                            ['icon' => '', 'active' => request()->segment(1) == 'expenses' || request()->segment(1) == 'import-expense' && request()->segment(2) == null]
                        );

                        if (auth()->user()->can('expense.add')) {
                            $sub->url(
                                action([\App\Http\Controllers\ExpenseController::class, 'create']),
                                __('expense.add_expense'),
                                ['icon' => '', 'active' => request()->segment(1) == 'expenses' && request()->segment(2) == 'create']
                            );
                        }

                        if (auth()->user()->can('expense.add') || auth()->user()->can('expense.edit')) {
                            $sub->url(
                                action([\App\Http\Controllers\ExpenseCategoryController::class, 'index']),
                                __('expense.expense_categories'),
                                ['icon' => '', 'active' => request()->segment(1) == 'expense-categories']
                            );
                        }
                    },
                    ['icon' => ' <svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"></path>
                    <path d="M14.8 8a2 2 0 0 0 -1.8 -1h-2a2 2 0 1 0 0 4h2a2 2 0 1 1 0 4h-2a2 2 0 0 1 -1.8 -1"></path>
                    <path d="M12 6v10"></path>
                  </svg>']
                )->order(45);
            }
            // Capital Accounts (partnership investment)
            if (function_exists('capital_account_enabled') && capital_account_enabled()
                && auth()->user()->can('capital_account.access')) {
                $menu->dropdown(
                    __('lang_v1.capital_accounts'),
                    function ($sub) {
                        $sub->url(
                            action([\App\Http\Controllers\CapitalAccountController::class, 'index']),
                            __('lang_v1.partner_balance_sheet'),
                            ['icon' => '', 'active' => request()->segment(1) == 'capital-account' && request()->segment(2) == null]
                        );
                        $sub->url(
                            action([\App\Http\Controllers\CapitalAccountController::class, 'partners']),
                            __('lang_v1.capital_partners'),
                            ['icon' => '', 'active' => request()->segment(1) == 'capital-account' && request()->segment(2) == 'partners']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\CapitalAccountController::class, 'entries']),
                            __('lang_v1.capital_entries'),
                            ['icon' => '', 'active' => request()->segment(1) == 'capital-account' && request()->segment(2) == 'entries']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\CapitalAccountController::class, 'settings']),
                            __('lang_v1.capital_settings'),
                            ['icon' => '', 'active' => request()->segment(1) == 'capital-account' && request()->segment(2) == 'settings']
                        );
                    },
                    ['icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0" /><path d="M3 10l18 0" /><path d="M5 6l7 -3l7 3" /><path d="M4 10l0 11" /><path d="M20 10l0 11" /><path d="M8 14l0 3" /><path d="M12 14l0 3" /><path d="M16 14l0 3" /></svg>']
                )->order(48);
            }

            //Accounts dropdown
            if (feature_enabled('accounts') && auth()->user()->can('account.access') && in_array('account', $enabled_modules)) {
                $menu->dropdown(
                    __('lang_v1.payment_accounts'),
                    function ($sub) {
                        $sub->url(
                            action([\App\Http\Controllers\AccountController::class, 'index']),
                            __('account.list_accounts'),
                            ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'account']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet']),
                            __('account.balance_sheet'),
                            ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'balance-sheet']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\AccountReportsController::class, 'trialBalance']),
                            __('account.trial_balance'),
                            ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'trial-balance']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\AccountController::class, 'cashFlow']),
                            __('lang_v1.cash_flow'),
                            ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'cash-flow']
                        );
                        $sub->url(
                            action([\App\Http\Controllers\AccountReportsController::class, 'paymentAccountReport']),
                            __('account.payment_account_report'),
                            ['icon' => '', 'active' => request()->segment(1) == 'account' && request()->segment(2) == 'payment-account-report']
                        );
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"></path>
                    <path d="M3 10l18 0"></path>
                    <path d="M7 15l.01 0"></path>
                    <path d="M11 15l2 0"></path>
                  </svg>']
                )->order(50);
            }

            //Reports dropdown
            if (feature_enabled('reports') && (auth()->user()->can('purchase_n_sell_report.view') || auth()->user()->can('contacts_report.view')
                || auth()->user()->can('stock_report.view') || auth()->user()->can('tax_report.view')
                || auth()->user()->can('trending_product_report.view') || auth()->user()->can('sales_representative.view') || auth()->user()->can('register_report.view')
                || auth()->user()->can('expense_report.view')
                || (function_exists('nutmeg_enabled') && nutmeg_enabled() && report_enabled('nutmeg')))) {
                $menu->dropdown(
                    __('report.reports'),
                    function ($sub) use ($enabled_modules, $is_admin) {
                        if (report_enabled('profit_loss') && auth()->user()->can('profit_loss_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getProfitLoss']),
                                __('report.profit_loss'),
                                ['icon' => '', 'active' => request()->segment(2) == 'profit-loss']
                            );
                        }
                        if (config('constants.show_report_606') == true) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'purchaseReport']),
                                'Report 606 (' . __('lang_v1.purchase') . ')',
                                ['icon' => '', 'active' => request()->segment(2) == 'purchase-report']
                            );
                        }
                        if (config('constants.show_report_607') == true) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'saleReport']),
                                'Report 607 (' . __('business.sale') . ')',
                                ['icon' => '', 'active' => request()->segment(2) == 'sale-report']
                            );
                        }
                        if (report_enabled('purchase_sell') && (in_array('purchases', $enabled_modules) || in_array('add_sale', $enabled_modules) || in_array('pos_sale', $enabled_modules)) && auth()->user()->can('purchase_n_sell_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getPurchaseSell']),
                                __('report.purchase_sell_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'purchase-sell']
                            );
                        }

                        if (report_enabled('tax') && auth()->user()->can('tax_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getTaxReport']),
                                __('report.tax_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'tax-report']
                            );
                        }
                        if (auth()->user()->can('contacts_report.view')) {
                            if (report_enabled('contacts')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getCustomerSuppliers']),
                                    __('report.contacts'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'customer-supplier']
                                );
                            }
                            if (report_enabled('customer_groups')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getCustomerGroup']),
                                    __('lang_v1.customer_groups_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'customer-group']
                                );
                            }
                            if (report_enabled('loyalty')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getLoyaltyReport']),
                                    __('lang_v1.loyalty_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'loyalty-report']
                                );
                            }
                        }
                        if (auth()->user()->can('stock_report.view')) {
                            if (report_enabled('stock')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getStockReport']),
                                    __('report.stock_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'stock-report']
                                );
                            }
                            if (report_enabled('stock_expiry') && session('business.enable_product_expiry') == 1) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getStockExpiryReport']),
                                    __('report.stock_expiry_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'stock-expiry']
                                );
                            }
                            if (report_enabled('lot') && session('business.enable_lot_number') == 1) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getLotReport']),
                                    __('lang_v1.lot_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'lot-report']
                                );
                            }

                            if (report_enabled('stock_adjustment') && in_array('stock_adjustment', $enabled_modules)) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getStockAdjustmentReport']),
                                    __('report.stock_adjustment_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'stock-adjustment-report']
                                );
                            }
                        }

                        if (report_enabled('trending_products') && auth()->user()->can('trending_product_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getTrendingProducts']),
                                __('report.trending_products'),
                                ['icon' => '', 'active' => request()->segment(2) == 'trending-products']
                            );
                        }

                        if (auth()->user()->can('purchase_n_sell_report.view')) {
                            if (report_enabled('items')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'itemsReport']),
                                    __('lang_v1.items_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'items-report']
                                );
                            }

                            if (report_enabled('product_purchase')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getproductPurchaseReport']),
                                    __('lang_v1.product_purchase_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'product-purchase-report']
                                );
                            }

                            if (report_enabled('product_sell')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'getproductSellReport']),
                                    __('lang_v1.product_sell_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'product-sell-report']
                                );
                            }

                            if (report_enabled('purchase_payment')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'purchasePaymentReport']),
                                    __('lang_v1.purchase_payment_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'purchase-payment-report']
                                );
                            }

                            if (report_enabled('sell_payment')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'sellPaymentReport']),
                                    __('lang_v1.sell_payment_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'sell-payment-report']
                                );
                            }
                        }

                        if (report_enabled('cheque')) {
                            $sub->url(
                                action([\App\Http\Controllers\ChequeController::class, 'report']),
                                __('Cheque Report'),
                                ['icon' => '', 'active' => request()->segment(1) == 'cheque' && request()->segment(2) == 'report']
                            );
                        }
                        if (report_enabled('expense') && in_array('expenses', $enabled_modules) && auth()->user()->can('expense_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getExpenseReport']),
                                __('report.expense_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'expense-report']
                            );
                        }
                        if (report_enabled('register') && auth()->user()->can('register_report.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getRegisterReport']),
                                __('report.register_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'register-report']
                            );
                        }
                        if (report_enabled('sales_representative') && auth()->user()->can('sales_representative.view')) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getSalesRepresentativeReport']),
                                __('report.sales_representative'),
                                ['icon' => '', 'active' => request()->segment(2) == 'sales-representative-report']
                            );
                        }
                        if (report_enabled('table') && auth()->user()->can('purchase_n_sell_report.view') && in_array('tables', $enabled_modules)) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getTableReport']),
                                __('restaurant.table_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'table-report']
                            );
                        }

                        if (auth()->user()->can('tax_report.view') && !empty(config('constants.enable_gst_report_india'))) {
                            if (report_enabled('gst_sales')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'gstSalesReport']),
                                    __('lang_v1.gst_sales_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'gst-sales-report']
                                );
                            }

                            if (report_enabled('gst_purchase')) {
                                $sub->url(
                                    action([\App\Http\Controllers\ReportController::class, 'gstPurchaseReport']),
                                    __('lang_v1.gst_purchase_report'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'gst-purchase-report']
                                );
                            }
                        }

                        if (report_enabled('service_staff') && auth()->user()->can('sales_representative.view') && in_array('service_staff', $enabled_modules)) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'getServiceStaffReport']),
                                __('restaurant.service_staff_report'),
                                ['icon' => '', 'active' => request()->segment(2) == 'service-staff-report']
                            );
                        }

                        if (report_enabled('activity_log') && $is_admin) {
                            $sub->url(
                                action([\App\Http\Controllers\ReportController::class, 'activityLog']),
                                __('lang_v1.activity_log'),
                                ['icon' => '', 'active' => request()->segment(2) == 'activity-log']
                            );
                        }

                        // Nutmeg / spices cost report (also listed under Nutmeg menu)
                        if (report_enabled('nutmeg') && function_exists('nutmeg_enabled') && nutmeg_enabled()) {
                            $sub->url(
                                action([\App\Http\Controllers\NutmegReportController::class, 'index']),
                                __('lang_v1.nutmeg_report'),
                                ['icon' => '', 'active' => request()->segment(1) == 'nutmeg' && request()->segment(2) == 'reports']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M8 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h5.697"></path>
                    <path d="M18 14v4h4"></path>
                    <path d="M18 11v-4a2 2 0 0 0 -2 -2h-2"></path>
                    <path d="M8 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                    <path d="M18 18m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                    <path d="M8 11h4"></path>
                    <path d="M8 15h3"></path>
                  </svg>', 'id' => 'tour_step8']
                )->order(55);
            }

            //Cheque Management menu
            if (feature_enabled('cheque') && (auth()->user()->can('sell.view') || auth()->user()->can('sell.create'))) {
                $menu->url(
                    action([\App\Http\Controllers\ChequeController::class, 'index']),
                    'Cheque Management',
                    [
                        'icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/>
                        <path d="M3 10l18 0"/>
                        <path d="M7 15l.01 0"/>
                        <path d="M11 15l6 0"/>
                    </svg>',
                        'active' => request()->segment(1) == 'cheque',
                    ]
                )->order(57);
            }

            //Backup menu
            if (feature_enabled('backup') && auth()->user()->can('backup')) {
                $menu->url(action([\App\Http\Controllers\BackUpController::class, 'index']), __('lang_v1.backup'), ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M12 18.004h-5.343c-2.572 -.004 -4.657 -2.011 -4.657 -4.487c0 -2.475 2.085 -4.482 4.657 -4.482c.393 -1.762 1.794 -3.2 3.675 -3.773c1.88 -.572 3.956 -.193 5.444 1c1.488 1.19 2.162 3.007 1.77 4.769h.99c1.38 0 2.57 .811 3.128 1.986"></path>
                <path d="M19 22v-6"></path>
                <path d="M22 19l-3 -3l-3 3"></path>
              </svg>', 'active' => request()->segment(1) == 'backup'])->order(60);
            }

            //Modules menu
            if (feature_enabled('manage_modules') && auth()->user()->can('manage_modules')) {
                $menu->url(action([\App\Http\Controllers\Install\ModulesController::class, 'index']), __('lang_v1.modules'), ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path d="M12 4l-8 4l8 4l8 -4l-8 -4"></path>
              <path d="M4 12l8 4l8 -4"></path>
              <path d="M4 16l8 4l8 -4"></path>
            </svg>', 'active' => request()->segment(1) == 'manage-modules'])->order(60);
            }

            //Booking menu
            if (feature_enabled('booking') && in_array('booking', $enabled_modules) && (auth()->user()->can('crud_all_bookings') || auth()->user()->can('crud_own_bookings'))) {
                $menu->url(action([\App\Http\Controllers\Restaurant\BookingController::class, 'index']), __('restaurant.bookings'), ['icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.5 21h-5.5a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v6" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M15 19l2 2l4 -4" /></svg>', 'active' => request()->segment(1) == 'bookings'])->order(65);
            }

            // —— Customer Displays (cart / marketing) vs Order Displays (status board) ——
            $cd_enabled = ! empty($pos_settings['customer_display_screen']);
            $cd_mode = $pos_settings['customer_display_mode'] ?? 'screen'; // screen | analog
            $is_analog = $cd_mode === 'analog';
            $show_order_displays = (feature_enabled('kitchen') && in_array('kitchen', $enabled_modules))
                || (feature_enabled('service_staff') && in_array('service_staff', $enabled_modules));

            if ($cd_enabled || $show_order_displays) {
                if ($cd_enabled) {
                    $menu->header(__('lang_v1.customer_displays'));
                    $menu->url(
                        route('pos_display'),
                        __('lang_v1.cart_display'),
                        [
                            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1v-10z" /><path d="M7 20h10" /><path d="M9 16v4" /><path d="M15 16v4" /></svg>',
                            'active' => request()->segment(1) == 'customer-display' && request()->get('view') !== 'marketing',
                            'target' => '_blank',
                        ]
                    )->order(67);

                    // Digital/screen mode only — marketing carousel is not for analog VFD
                    if (! $is_analog) {
                        $menu->url(
                            route('pos_display', ['view' => 'marketing']),
                            __('lang_v1.display_marketing'),
                            [
                                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" /></svg>',
                                'active' => request()->segment(1) == 'customer-display' && request()->get('view') === 'marketing',
                                'target' => '_blank',
                            ]
                        )->order(68);
                    }
                }

                // Always its own section — never under Customer Displays
                if ($show_order_displays) {
                    $menu->header(__('lang_v1.order_displays'));

                    if (feature_enabled('kitchen') && in_array('kitchen', $enabled_modules)) {
                        $menu->url(
                            action([\App\Http\Controllers\Restaurant\KitchenController::class, 'index']),
                            __('lang_v1.order_status_board'),
                            [
                                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M12 4v16" /></svg>',
                                'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'kitchen',
                            ]
                        )->order(70);
                    } elseif (feature_enabled('service_staff') && in_array('service_staff', $enabled_modules)) {
                        $menu->url(
                            action([\App\Http\Controllers\Restaurant\OrderController::class, 'index']),
                            __('lang_v1.order_status_board'),
                            [
                                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-shrink-0" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M12 4v16" /></svg>',
                                'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'orders',
                            ]
                        )->order(70);
                    }
                }
            }

            //Kitchen menu (only if not already under Order Displays)
            if (feature_enabled('kitchen') && in_array('kitchen', $enabled_modules) && ! $show_order_displays) {
                $menu->url(action([\App\Http\Controllers\Restaurant\KitchenController::class, 'index']), __('restaurant.kitchen'), ['icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-flame"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z" /></svg>', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'kitchen'])->order(70);
            }

            //Service Staff menu (skip if Order Displays already points at orders as the status board)
            if (feature_enabled('service_staff') && in_array('service_staff', $enabled_modules)) {
                $orders_used_as_status_board = $show_order_displays
                    && ! (feature_enabled('kitchen') && in_array('kitchen', $enabled_modules));
                if (! $orders_used_as_status_board) {
                    $menu->url(action([\App\Http\Controllers\Restaurant\OrderController::class, 'index']), __('restaurant.orders'), ['icon' => '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="18"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-baseline-density-medium"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 20h16" /><path d="M4 12h16" /><path d="M4 4h16" /></svg>', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'orders'])->order(75);
                }
            }

            //Notification template menu
            if (feature_enabled('notification_templates') && auth()->user()->can('send_notifications')) {
                $menu->url(action([\App\Http\Controllers\NotificationTemplateController::class, 'index']), __('lang_v1.notification_templates'), ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"></path>
                    <path d="M3 7l9 6l9 -6"></path>
                  </svg>', 'active' => request()->segment(1) == 'notification-templates'])->order(80);
            }

            //Settings Dropdown
            if ($is_software_owner || ((feature_enabled('business_settings') || feature_enabled('invoice_settings') || feature_enabled('barcode_settings') || feature_enabled('receipt_printers') || feature_enabled('tax_rates') || feature_enabled('tables') || feature_enabled('modifiers')) && (
                auth()->user()->can('business_settings.access') ||
                auth()->user()->can('barcode_settings.access') ||
                auth()->user()->can('invoice_settings.access') ||
                auth()->user()->can('tax_rate.view') ||
                auth()->user()->can('tax_rate.create') ||
                auth()->user()->can('access_package_subscriptions')))) {
                $menu->dropdown(
                    __('business.settings'),
                    function ($sub) use ($enabled_modules, $is_software_owner) {
                        if ($is_software_owner || (feature_enabled('business_settings') && auth()->user()->can('business_settings.access'))) {
                            $sub->url(
                                action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']),
                                __('business.business_settings'),
                                ['icon' => '', 'active' => request()->segment(1) == 'business', 'id' => 'tour_step2']
                            );
                        }
                        if (feature_enabled('business_locations') && auth()->user()->can('business_settings.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\BusinessLocationController::class, 'index']),
                                __('business.business_locations'),
                                ['icon' => '', 'active' => request()->segment(1) == 'business-location']
                            );
                        }
                        if (feature_enabled('invoice_settings') && auth()->user()->can('invoice_settings.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\InvoiceSchemeController::class, 'index']),
                                __('invoice.invoice_settings'),
                                ['icon' => '', 'active' => in_array(request()->segment(1), ['invoice-schemes', 'invoice-layouts'])]
                            );
                        }
                        if (feature_enabled('barcode_settings') && auth()->user()->can('barcode_settings.access')) {
                            $sub->url(
                                action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) . '#settings-bartender',
                                __('barcode.barcode_settings'),
                                ['icon' => '', 'active' => request()->segment(1) == 'business' && request()->segment(2) == 'settings']
                            );
                        }
                        if (feature_enabled('receipt_printers') && auth()->user()->can('access_printers')) {
                            $sub->url(
                                action([\App\Http\Controllers\PrinterController::class, 'index']),
                                __('printer.receipt_printers'),
                                ['icon' => '', 'active' => request()->segment(1) == 'printers']
                            );
                        }

                        if (feature_enabled('tax_rates') && (auth()->user()->can('tax_rate.view') || auth()->user()->can('tax_rate.create'))) {
                            if (is_tax_enabled()) {
                            $sub->url(
                                action([\App\Http\Controllers\TaxRateController::class, 'index']),
                                __('tax_rate.tax_rates'),
                                ['icon' => '', 'active' => request()->segment(1) == 'tax-rates']
                            );
                            }
                        }

                        if (feature_enabled('tables') && in_array('tables', $enabled_modules) && auth()->user()->can('access_tables')) {
                            $sub->url(
                                action([\App\Http\Controllers\Restaurant\TableController::class, 'index']),
                                __('restaurant.tables'),
                                ['icon' => '', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'tables']
                            );
                        }

                        if (feature_enabled('modifiers') && in_array('modifiers', $enabled_modules) && (auth()->user()->can('product.view') || auth()->user()->can('product.create'))) {
                            $sub->url(
                                action([\App\Http\Controllers\Restaurant\ModifierSetsController::class, 'index']),
                                __('restaurant.modifiers'),
                                ['icon' => '', 'active' => request()->segment(1) == 'modules' && request()->segment(2) == 'modifiers']
                            );
                        }

                        if (in_array('types_of_service', $enabled_modules) && auth()->user()->can('access_types_of_service')) {
                            $sub->url(
                                action([\App\Http\Controllers\TypesOfServiceController::class, 'index']),
                                __('lang_v1.types_of_service'),
                                ['icon' => '', 'active' => request()->segment(1) == 'types-of-service']
                            );
                        }
                    },
                    ['icon' => '<svg aria-hidden="true" class="tw-size-5 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"></path>
                    <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                  </svg>', 'id' => 'tour_step3']
                )->order(85);
            }
        });

        //Add menus from modules
        $moduleUtil = new ModuleUtil;
        $moduleUtil->getModuleData('modifyAdminMenu');

        return $next($request);
    }
}

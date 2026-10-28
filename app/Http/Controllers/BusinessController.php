<?php

namespace App\Http\Controllers;

use App\Business;
use App\Currency;
use App\Notifications\TestEmailNotification;
use App\Product;
use App\System;
use App\TaxRate;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\InstallBusinessModules;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\RestaurantUtil;
use Carbon\Carbon;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Rules\ReCaptcha;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | BusinessController
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new business/business as well as their
    | validation and creation.
    |
    */

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $restaurantUtil;

    protected $moduleUtil;

    protected $productUtil;

    protected $mailDrivers;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, RestaurantUtil $restaurantUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;

        $this->theme_colors = [
            'primary' => 'Blue',
            'indigo'  => 'Indigo',
            'violet'  => 'Violet',
            'purple'  => 'Purple',
            'teal'    => 'Teal',
            'emerald' => 'Emerald',
            'green'   => 'Green',
            'sky'     => 'Sky',
            'pink'    => 'Pink',
            'rose'    => 'Rose',
            'red'     => 'Red',
            'orange'  => 'Orange',
            'yellow'  => 'Yellow',
            'slate'   => 'Slate',
        ];

        $this->mailDrivers = [
            'smtp' => 'SMTP',
            // 'sendmail' => 'Sendmail',
            // 'mailgun' => 'Mailgun',
            // 'mandrill' => 'Mandrill',
            // 'ses' => 'SES',
            // 'sparkpost' => 'Sparkpost'
        ];
    }

    /**
     * Shows registration form
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        $currencies = $this->businessUtil->allCurrencies();

        $timezone_list = $this->businessUtil->allTimeZones();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $package_id = request()->package;

        try {
            $system_settings = System::getProperties(['superadmin_enable_register_tc', 'superadmin_register_tc'], true);
        } catch (\Exception $e) {
            $system_settings = [];
        }

        return view('business.register', compact(
            'currencies',
            'timezone_list',
            'months',
            'accounting_methods',
            'package_id',
            'system_settings'
        ));
    }

    /**
     * Handles the registration of a new business and it's owner
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister(Request $request)
    {
        if (! config('constants.allow_registration')) {
            return redirect('/');
        }

        try {
            $validator = $request->validate(
                [
                    'name' => 'required|max:255',
                    'currency_id' => 'required|numeric',
                    'country' => 'required|max:255',
                    'state' => 'required|max:255',
                    'city' => 'required|max:255',
                    'zip_code' => 'required|max:255',
                    'address_line_1' => 'required|max:255',
                    'address_line_2' => 'nullable|max:255',
                    'landmark' => 'nullable|max:255',
                    'time_zone' => 'required|max:255',
                    'surname' => 'max:10',
                    'email' => 'sometimes|nullable|email|unique:users|max:255',
                    'first_name' => 'required|max:255',
                    'username' => 'required|min:4|max:255|unique:users',
                    'password' => 'required|min:4|max:255',
                    'fy_start_month' => 'required',
                    'accounting_method' => 'required',
                ],
                [
                    'name.required' => __('validation.required', ['attribute' => __('business.business_name')]),
                    'name.currency_id' => __('validation.required', ['attribute' => __('business.currency')]),
                    'country.required' => __('validation.required', ['attribute' => __('business.country')]),
                    'state.required' => __('validation.required', ['attribute' => __('business.state')]),
                    'city.required' => __('validation.required', ['attribute' => __('business.city')]),
                    'zip_code.required' => __('validation.required', ['attribute' => __('business.zip_code')]),
                    'address_line_1.required' => __('validation.required', ['attribute' => __('lang_v1.address_line_1')]),
                    'time_zone.required' => __('validation.required', ['attribute' => __('business.time_zone')]),
                    'email.email' => __('validation.email', ['attribute' => __('business.email')]),
                    'email.email' => __('validation.unique', ['attribute' => __('business.email')]),
                    'first_name.required' => __('validation.required', ['attribute' => __('business.first_name')]),
                    'username.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'username.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'password.required' => __('validation.required', ['attribute' => __('business.username')]),
                    'password.min' => __('validation.min', ['attribute' => __('business.username')]),
                    'fy_start_month.required' => __('validation.required', ['attribute' => __('business.fy_start_month')]),
                    'accounting_method.required' => __('validation.required', ['attribute' => __('business.accounting_method')]),
                ]
            );

            if (config('constants.enable_recaptcha')) {
                $recaptcha_validator = Validator::make($request->all(), [
                    'g-recaptcha-response' => ['required', new \App\Rules\ReCaptcha]
                ]);
                if ($recaptcha_validator->fails()) {
                    return back()->withErrors($recaptcha_validator)->withInput();
                }
            }


            DB::beginTransaction();

            //Create owner.
            $owner_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password', 'language']);

            $owner_details['language'] = empty($owner_details['language']) ? config('app.locale') : $owner_details['language'];

            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'time_zone',
                'fy_start_month', 'accounting_method', 'tax_label_1', 'tax_number_1',
                'tax_label_2', 'tax_number_2', ]);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code',
                'website', 'mobile', 'alternate_number', ]);

            // Combine address lines (+ optional landmark) into business_locations.landmark
            $address_parts = array_filter([
                trim((string) $request->input('address_line_1', '')),
                trim((string) $request->input('address_line_2', '')),
                trim((string) $request->input('landmark', '')),
            ], function ($part) {
                return $part !== '';
            });
            $business_location['landmark'] = implode(', ', $address_parts);

            //Create the business
            $business_details['owner_id'] = $user->id;
            if (! empty($business_details['start_date'])) {
                $business_details['start_date'] = Carbon::createFromFormat(config('constants.default_date_format'), $business_details['start_date'])->toDateString();
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            //default enabled modules (includes specialty packs chosen at install)
            $business_details['enabled_modules'] = InstallBusinessModules::defaultEnabledModules();

            $flags = InstallBusinessModules::defaultProductFlags();
            if (array_key_exists('enable_brand', $flags)) {
                $business_details['enable_brand'] = (int) $flags['enable_brand'];
            }
            if (array_key_exists('enable_product_warranty', $flags)) {
                $business_details['common_settings'] = array_merge(
                    is_array($business_details['common_settings'] ?? null) ? $business_details['common_settings'] : [],
                    ['enable_product_warranty' => (int) $flags['enable_product_warranty']]
                );
            }

            $business = $this->businessUtil->createNewBusiness($business_details);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.'.$new_location->id]);

            DB::commit();

            //Module function to be called after after business is created
            if (config('app.env') != 'demo') {
                $this->moduleUtil->getModuleData('after_business_created', ['business' => $business]);
            }

            //Process payment information if superadmin is installed & package information is present
            $is_installed_superadmin = $this->moduleUtil->isSuperadminInstalled();
            $package_id = $request->get('package_id', null);
            if ($is_installed_superadmin && ! empty($package_id) && (config('app.env') != 'demo')) {
                $package = \Modules\Superadmin\Entities\Package::find($package_id);
                if (! empty($package)) {
                    Auth::login($user);
                    return redirect()->route('register-pay', ['package_id' => $package_id]);
                }
            }

            $output = ['success' => 1,
                'msg' => __('business.business_created_succesfully'),
            ];

            return redirect('login')->with('status', $output);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let Laravel redirect back with field errors (do not swallow as generic failure)
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong').' ('.$e->getMessage().')',
            ];

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Handles the validation username
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckUsername(Request $request)
    {
        $username = $request->input('username');

        if (! empty($request->input('username_ext'))) {
            $username .= $request->input('username_ext');
        }

        $count = User::where('username', $username)->count();

        if ($count == 0) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * Shows business settings form
     *
     * @return \Illuminate\Http\Response
     */
    public function getBusinessSettings()
    {
        if (! auth()->user()->can('business_settings.access') && ! (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())) {
            abort(403, 'Unauthorized action.');
        }

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $timezone_list = [];
        foreach ($timezones as $timezone) {
            $timezone_list[$timezone] = $timezone;
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        // Keep session in sync with DB so header / POS / settings all show the same values
        if (! empty($business)) {
            request()->session()->put('business', $business);
        }

        $currencies = $this->businessUtil->allCurrencies();
        $tax_details = TaxRate::forBusinessDropdown($business_id);
        $tax_rates = $tax_details['tax_rates'];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = __('business.months.'.$i);
        }

        $accounting_methods = $this->businessUtil->allAccountingMethods();
        $commission_agent_dropdown = [
            '' => __('lang_v1.disable'),
            'logged_in_user' => __('lang_v1.logged_in_user'),
            'user' => __('lang_v1.select_from_users_list'),
            'cmsn_agnt' => __('lang_v1.select_from_commisssion_agents_list'),
        ];

        $units_dropdown = Unit::forDropdown($business_id, true);

        $date_formats = Business::date_formats();

        $shortcuts = json_decode($business->keyboard_shortcuts, true);

        $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

        $email_settings = empty($business->email_settings) ? $this->businessUtil->defaultEmailSettings() : $business->email_settings;

        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        // Migrate legacy eBill notify.lk credentials into SMS settings for display
        $common_settings_preview = ! empty($business->common_settings) ? $business->common_settings : [];
        $ebill_preview = $common_settings_preview['ebill_settings'] ?? [];
        if (empty($sms_settings['notifylk_user_id']) && ! empty($ebill_preview['notifylk_user_id'])) {
            $sms_settings['sms_service'] = $sms_settings['sms_service'] ?? 'notifylk';
            $sms_settings['notifylk_user_id'] = $ebill_preview['notifylk_user_id'];
            $sms_settings['notifylk_api_key'] = $ebill_preview['notifylk_api_key'] ?? '';
            $sms_settings['notifylk_sender_id'] = $ebill_preview['notifylk_sender_id'] ?? 'NotifyDEMO';
        }

        $modules = $this->moduleUtil->availableModules();

        $theme_colors = $this->theme_colors;

        $mail_drivers = $this->mailDrivers;

        $allow_superadmin_email_settings = System::getProperty('allow_email_settings_to_businesses');

        $custom_labels = ! empty($business->custom_labels) ? json_decode($business->custom_labels, true) : [];

        $common_settings = ! empty($business->common_settings) ? $business->common_settings : [];
        if (! is_array($common_settings)) {
            $common_settings = [];
        }
        // Duplicate form fields previously saved some scalars as arrays → htmlspecialchars crash
        $common_settings = $this->flattenScalarSettingsArray($common_settings);
        $weighing_scale_setting = ! empty($business->weighing_scale_setting) ? $business->weighing_scale_setting : [];
        if (! is_array($weighing_scale_setting)) {
            $weighing_scale_setting = [];
        }
        $weighing_scale_setting = $this->flattenScalarSettingsArray($weighing_scale_setting, []);

        // Persist repair so the page stays healthy
        $dirty = false;
        if ($business->common_settings != $common_settings) {
            $business->common_settings = $common_settings;
            $dirty = true;
        }
        if ($business->weighing_scale_setting != $weighing_scale_setting) {
            $business->weighing_scale_setting = $weighing_scale_setting;
            $dirty = true;
        }
        if ($dirty) {
            $business->save();
        }

        $payment_types = $this->moduleUtil->payment_types(null, false, $business_id);

        $is_software_owner = auth()->user()->isSoftwareOwner();
        $settings_visibility = \App\Utils\SoftwareOwnerUtil::getVisibility();
        $settings_option_defs = \App\Utils\SoftwareOwnerUtil::optionDefinitions();
        $feature_visibility = \App\Utils\SoftwareOwnerUtil::getFeatures();
        $feature_defs = \App\Utils\SoftwareOwnerUtil::featureDefinitions();
        $login_bg_url = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::loginBackgroundUrl() : null;
        $login_bg_file = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::loginBackgroundFilename() : null;
        $soft_owner_modules = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::getSoftOwnerModulePrefs() : [];
        $soft_owner_module_defs = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::softOwnerModuleDefinitions() : [];
        $shop_modules = $is_software_owner
            ? \App\Utils\SoftwareOwnerUtil::getShopModulesForBusiness((int) $business->id)
            : [];
        $shop_module_defs = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::shopModuleDefinitions() : [];
        $report_visibility = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::getReports() : [];
        $report_defs = $is_software_owner ? \App\Utils\SoftwareOwnerUtil::reportDefinitions() : [];
        $shop_mode_defs = $is_software_owner ? \App\Utils\ShopModeUtil::definitions() : [];
        $current_shop_mode = \App\Utils\ShopModeUtil::current((int) $business->id);

        return view('business.settings', compact('business', 'currencies', 'tax_rates', 'timezone_list', 'months', 'accounting_methods', 'commission_agent_dropdown', 'units_dropdown', 'date_formats', 'shortcuts', 'pos_settings', 'modules', 'theme_colors', 'email_settings', 'sms_settings', 'mail_drivers', 'allow_superadmin_email_settings', 'custom_labels', 'common_settings', 'weighing_scale_setting', 'payment_types', 'is_software_owner', 'settings_visibility', 'settings_option_defs', 'feature_visibility', 'feature_defs', 'login_bg_url', 'login_bg_file', 'soft_owner_modules', 'soft_owner_module_defs', 'shop_modules', 'shop_module_defs', 'report_visibility', 'report_defs', 'shop_mode_defs', 'current_shop_mode'));
    }

    /**
     * Software owner: enable / disable Settings options + app features for shop users.
     */
    public function postSettingsMenuVisibility(Request $request)
    {
        if (! auth()->user()->isSoftwareOwner()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            \App\Utils\SoftwareOwnerUtil::saveVisibility($request->input('settings_visibility', []));
            \App\Utils\SoftwareOwnerUtil::saveFeatures($request->input('feature_visibility', []));
            \App\Utils\SoftwareOwnerUtil::saveSoftOwnerModulePrefs($request->input('soft_owner_modules', []));
            \App\Utils\SoftwareOwnerUtil::saveReports($request->input('report_visibility', []));

            $businessId = (int) ($request->session()->get('user.business_id') ?: auth()->user()->business_id);
            $enabled = \App\Utils\SoftwareOwnerUtil::saveShopModulesForBusiness(
                $businessId,
                $request->input('shop_modules', [])
            );

            $business = \App\Business::find($businessId);
            if ($business) {
                $business->enabled_modules = \App\Utils\ShopModeUtil::overlayIndustryModules(
                    is_array($business->enabled_modules) ? $business->enabled_modules : [],
                    (int) $business->id
                );
                $business->save();
                $request->session()->put('business', $business->fresh());
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Software Owner: set Shop Mode for this business.
     * Does not delete products, sales, stock, or customers.
     */
    public function postShopMode(Request $request)
    {
        if (! auth()->user()->isSoftwareOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $mode = (string) $request->input('shop_mode', \App\Utils\ShopModeUtil::DEFAULT);
        if (! \App\Utils\ShopModeUtil::isValid($mode)) {
            return redirect('business/settings')->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.shop_mode_invalid'),
            ]);
        }

        try {
            $businessId = (int) ($request->session()->get('user.business_id') ?: auth()->user()->business_id);
            $business = \App\Business::findOrFail($businessId);
            \App\Utils\ShopModeUtil::applyToBusiness($business, $mode, true);
            $request->session()->put('business', $business->fresh());

            $label = \App\Utils\ShopModeUtil::definitions()[$mode]['label'] ?? $mode;

            return redirect('business/settings#shop-mode-box')->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.shop_mode_saved', ['mode' => $label]),
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $msg = __('messages.something_went_wrong');
            $raw = $e->getMessage();
            if (stripos($raw, 'shop_mode') !== false
                || stripos($raw, 'Unknown column') !== false
                || stripos($raw, 'cpanel_shop_mode') !== false) {
                $msg = $raw;
            }

            return redirect('business/settings#shop-mode-box')->with('status', [
                'success' => 0,
                'msg' => $msg,
            ]);
        }
    }

    /**
     * Soft Owner: upload / remove login page left-panel background image.
     */
    public function postLoginBackground(Request $request)
    {
        if (! auth()->user()->isSoftwareOwner()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if ($request->boolean('remove_login_bg')) {
                $old = \App\Utils\SoftwareOwnerUtil::loginBackgroundFilename();
                if ($old) {
                    $path = public_path('uploads/login/' . $old);
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                \App\Utils\SoftwareOwnerUtil::setLoginBackground(null);

                return redirect('business/settings')->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.login_bg_removed'),
                ]);
            }

            if (! $request->hasFile('login_bg_image')) {
                return redirect('business/settings')->with('status', [
                    'success' => 0,
                    'msg' => __('lang_v1.login_bg_required'),
                ]);
            }

            $dir = public_path('uploads/login');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $file = $request->file('login_bg_image');
            if (! $file->isValid() || strpos((string) $file->getMimeType(), 'image/') !== 0) {
                return redirect('business/settings')->with('status', [
                    'success' => 0,
                    'msg' => __('lang_v1.login_bg_invalid'),
                ]);
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $ext = 'jpg';
            }
            $name = 'login_bg_' . date('YmdHis') . '_' . \Illuminate\Support\Str::random(6) . '.' . $ext;
            $file->move($dir, $name);

            $old = \App\Utils\SoftwareOwnerUtil::loginBackgroundFilename();
            if ($old && $old !== $name) {
                $oldPath = public_path('uploads/login/' . $old);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            \App\Utils\SoftwareOwnerUtil::setLoginBackground($name);

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.login_bg_saved'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Updates business settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postBusinessSettings(Request $request)
    {
        if (! auth()->user()->can('business_settings.access') && ! (method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner())) {
            abort(403, 'Unauthorized action.');
        }

        $debugLog = function (string $message, array $context = []) {
            try {
                \Log::info('[business_settings] '.$message, $context);
                $line = '['.date('Y-m-d H:i:s').'] '.$message;
                if (! empty($context)) {
                    $line .= ' '.json_encode($context, JSON_UNESCAPED_SLASHES);
                }
                $line .= PHP_EOL;
                @file_put_contents(storage_path('logs/settings_update.log'), $line, FILE_APPEND);
            } catch (\Throwable $e) {
                // ignore logging failures
            }
        };

        try {
            $debugLog('POST received', [
                'user_id' => auth()->id(),
                'business_id' => $request->session()->get('user.business_id'),
                'content_type' => $request->header('Content-Type'),
                'is_json' => $request->isJson(),
                'input_count' => count($request->all(), COUNT_RECURSIVE),
                'field_count_meta' => $request->input('_settings_field_count'),
                'has_name' => $request->has('name'),
                'has_date_format' => $request->has('date_format'),
                'has_common_settings' => $request->has('common_settings'),
                'enable_bartender_export' => $request->input('common_settings.enable_bartender_export'),
                'enable_weighing_scale_label' => $request->input('common_settings.enable_weighing_scale_label'),
                'max_input_vars' => ini_get('max_input_vars'),
                'saver' => $request->input('_qpos_saver'),
            ]);

            // Reject empty JSON saves (mobile was posting 0 fields and wiping toggles)
            if ($request->input('_qpos_saver') && ! $request->has('name') && ! $request->has('date_format')) {
                throw new \Exception('Settings payload empty — form fields were not collected. Try desktop or emergency save.');
            }
            if ($request->isJson() && (int) $request->input('_settings_field_count', 0) > 0
                && (int) $request->input('_settings_field_count') < 15) {
                throw new \Exception('Settings payload too small ('.$request->input('_settings_field_count').' fields). Refusing to save.');
            }

            $notAllowed = $this->businessUtil->notAllowedInDemo();
            if (! empty($notAllowed)) {
                $debugLog('Blocked: demo mode');
                return $notAllowed;
            }

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'default_profit_percent', 'default_sales_tax', 'default_sales_discount', 'sell_price_tax', 'sku_prefix', 'time_zone', 'fy_start_month', 'accounting_method', 'transaction_edit_days', 'sales_cmsn_agnt', 'item_addition_method', 'currency_symbol_placement', 'on_product_expiry',
                'stop_selling_before', 'default_unit', 'expiry_type', 'date_format',
                'time_format', 'ref_no_prefixes', 'theme_color', 'email_settings',
                'sms_settings', 'rp_name', 'amount_for_unit_rp',
                'min_order_total_for_rp', 'max_rp_per_order',
                'redeem_amount_per_unit_rp', 'min_order_total_for_redeem',
                'min_redeem_point', 'max_redeem_point', 'rp_expiry_period',
                'rp_expiry_type', 'custom_labels', 'weighing_scale_setting',
                'code_label_1', 'code_1', 'code_label_2', 'code_2', 'currency_precision', 'quantity_precision', ]);

            if (! empty($request->input('enable_rp')) && $request->input('enable_rp') == 1) {
                $business_details['enable_rp'] = 1;
            } else {
                $business_details['enable_rp'] = 0;
            }

            $business_details['amount_for_unit_rp'] = ! empty($business_details['amount_for_unit_rp']) ? $this->businessUtil->num_uf($business_details['amount_for_unit_rp']) : 1;
            $business_details['min_order_total_for_rp'] = ! empty($business_details['min_order_total_for_rp']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_rp']) : 1;
            $business_details['redeem_amount_per_unit_rp'] = ! empty($business_details['redeem_amount_per_unit_rp']) ? $this->businessUtil->num_uf($business_details['redeem_amount_per_unit_rp']) : 1;
            $business_details['min_order_total_for_redeem'] = ! empty($business_details['min_order_total_for_redeem']) ? $this->businessUtil->num_uf($business_details['min_order_total_for_redeem']) : 1;

            $business_details['default_profit_percent'] = ! empty($business_details['default_profit_percent']) ? $this->businessUtil->num_uf($business_details['default_profit_percent']) : 0;

            $business_details['default_sales_discount'] = ! empty($business_details['default_sales_discount']) ? $this->businessUtil->num_uf($business_details['default_sales_discount']) : 0;

            if (! empty($business_details['start_date'])) {
                $rawStart = trim((string) $business_details['start_date']);
                $parsedStart = null;

                // Already MySQL date (or datetime)
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $rawStart)) {
                    $parsedStart = substr($rawStart, 0, 10);
                } else {
                    try {
                        $parsedStart = $this->businessUtil->uf_date($rawStart);
                    } catch (\Throwable $e) {
                        $debugLog('start_date parse fallback', ['error' => $e->getMessage(), 'raw' => $rawStart]);
                    }
                    if (empty($parsedStart)) {
                        foreach (['m/d/Y', 'd/m/Y', 'm-d-Y', 'd-m-Y', 'Y-m-d', 'd-m-Y H:i:s', 'm/d/Y H:i:s'] as $fmt) {
                            try {
                                $parsedStart = \Carbon\Carbon::createFromFormat($fmt, $rawStart)->format('Y-m-d');
                                break;
                            } catch (\Throwable $e) {
                                // try next
                            }
                        }
                    }
                    if (empty($parsedStart)) {
                        $ts = strtotime($rawStart);
                        $parsedStart = $ts ? date('Y-m-d', $ts) : null;
                    }
                }

                if (! empty($parsedStart)) {
                    $business_details['start_date'] = $parsedStart;
                } else {
                    // Do not wipe existing start_date on parse failure
                    unset($business_details['start_date']);
                    $debugLog('start_date left unchanged (could not parse)', ['raw' => $rawStart]);
                }
            }

            if (! empty($request->input('enable_tooltip')) && $request->input('enable_tooltip') == 1) {
                $business_details['enable_tooltip'] = 1;
            } else {
                $business_details['enable_tooltip'] = 0;
            }

            $business_details['enable_product_expiry'] = ! empty($request->input('enable_product_expiry')) && $request->input('enable_product_expiry') == 1 ? 1 : 0;
            if ($business_details['on_product_expiry'] == 'keep_selling') {
                $business_details['stop_selling_before'] = null;
            }

            $business_details['stock_expiry_alert_days'] = ! empty($request->input('stock_expiry_alert_days')) ? $request->input('stock_expiry_alert_days') : 30;

            //Check for Purchase currency
            if (! empty($request->input('purchase_in_diff_currency')) && $request->input('purchase_in_diff_currency') == 1) {
                $business_details['purchase_in_diff_currency'] = 1;
                $business_details['purchase_currency_id'] = $request->input('purchase_currency_id');
                $business_details['p_exchange_rate'] = $request->input('p_exchange_rate');
            } else {
                $business_details['purchase_in_diff_currency'] = 0;
                $business_details['purchase_currency_id'] = null;
                $business_details['p_exchange_rate'] = 1;
            }

            //upload logo
            $logo_name = $this->businessUtil->uploadFile($request, 'business_logo', 'business_logos', 'image');
            if (! empty($logo_name)) {
                $business_details['logo'] = $logo_name;
            }

            $checkboxes = ['enable_editing_product_from_purchase',
                'enable_inline_tax',
                'enable_brand', 'enable_category', 'enable_sub_category', 'enable_price_tax', 'enable_purchase_status',
                'enable_lot_number', 'enable_racks', 'enable_row', 'enable_position', 'enable_sub_units', ];
            foreach ($checkboxes as $value) {
                $business_details[$value] = ! empty($request->input($value)) && $request->input($value) == 1 ? 1 : 0;
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            if (empty($business)) {
                throw new \Exception('Business not found for session business_id='.(string) $business_id);
            }

            //Update business settings
            if (! empty($business_details['logo'])) {
                $business->logo = $business_details['logo'];
            } else {
                unset($business_details['logo']);
            }

            //System settings
            $shortcuts = $request->input('shortcuts', []);
            $business_details['keyboard_shortcuts'] = json_encode($shortcuts ?: []);

           // Get existing pos_settings
            $pos_settings = $request->input('pos_settings', []);

            $pre_busines_detail = $this->businessUtil->getDetails($business_id);
            $pre_pos_setting = json_decode($pre_busines_detail->pos_settings, true) ?? [];
            for ($i = 1; $i <= 10; $i++) {
                $inputName = "carousel_image_$i"; // Image field names should be like carousel_image_1, carousel_image_2, etc.

                if ($request->hasFile($inputName)) {
                    $image_name = $this->businessUtil->uploadFile($request, $inputName, 'carousel_images', 'image');
                    $pos_settings[$inputName] = $image_name; // Store image URL inside pos_settings
                }else if (isset($pre_pos_setting[$inputName])){
                    $pos_settings[$inputName] = $pre_pos_setting[$inputName] ?? null;
                }
            }
            $default_pos_settings = $this->businessUtil->defaultPosSettings();
            foreach ($default_pos_settings as $key => $value) {
                if (! isset($pos_settings[$key])) {
                    $pos_settings[$key] = $value;
                }
            }
            // Checkboxes only submit when checked
            foreach (['enable_pop', 'pop_contact_both', 'pop_link_cash_register', 'touch_ask_qty', 'mute_sound'] as $flag) {
                $pos_settings[$flag] = ! empty($request->input('pos_settings.'.$flag)) ? 1 : 0;
            }
            // Save pos_settings as JSON
            $business_details['pos_settings'] = json_encode($pos_settings);

            $business_details['custom_labels'] = json_encode($business_details['custom_labels'] ?? []);

            $req_common = $request->input('common_settings', []);
            if (! is_array($req_common)) {
                $req_common = [];
            }
            // Flatten ONLY accidental scalar arrays; keep bartender_fields[] lists intact
            $req_common = $this->flattenScalarSettingsArray($req_common);

            $existing_common_settings = ! empty($business->common_settings) ? $business->common_settings : [];
            if (is_string($existing_common_settings)) {
                $existing_common_settings = json_decode($existing_common_settings, true) ?? [];
            }
            if (! is_array($existing_common_settings)) {
                $existing_common_settings = [];
            }
            $existing_common_settings = $this->flattenScalarSettingsArray($existing_common_settings);
            $previous_costcode_mapping = $existing_common_settings['costcode_mapping'] ?? [];
            $previous_costcode_repeat_zero = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_repeat_zero_letter'] ?? '')));
            if ($previous_costcode_repeat_zero === '' && ! empty($existing_common_settings['costcode_repeat_keyword'])) {
                $previous_costcode_repeat_zero = strtoupper(substr(preg_replace('/[^A-Z]/i', '', (string) $existing_common_settings['costcode_repeat_keyword']), 0, 1));
            }
            $previous_costcode_repeat_zero = $previous_costcode_repeat_zero !== '' ? $previous_costcode_repeat_zero[0] : '';
            $previous_costcode_repeat2 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_repeat2_letter'] ?? '')));
            $previous_costcode_repeat2 = $previous_costcode_repeat2 !== '' ? $previous_costcode_repeat2[0] : '';
            $previous_costcode_short_cost = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_short_cost_letter'] ?? '')));
            $previous_costcode_short_cost = $previous_costcode_short_cost !== '' ? $previous_costcode_short_cost[0] : '';
            $previous_costcode_enabled = ! empty($existing_common_settings['enable_costcode_generation']);
            $previous_costcode_scheme = (int) ($existing_common_settings['costcode_scheme_version'] ?? 1);
            $regenerate_costcodes_after_save = false;
            $regenerated_costcode_count = 0;

            $common_settings = array_merge($existing_common_settings, $req_common);
            $common_settings = $this->flattenScalarSettingsArray($common_settings);

            // Hard guarantee: bartender_fields must remain a unique list (never a string, never duplicated)
            // Hidden marker is always sent from the Bartender tab so "all unchecked" clears the list.
            if ($request->has('common_settings._bartender_fields_present')
                || $request->has('common_settings.bartender_fields')) {
                $bf = $request->input('common_settings.bartender_fields', []);
                if (! is_array($bf)) {
                    $bf = $bf === null || $bf === '' ? [] : [(string) $bf];
                }
                $common_settings['bartender_fields'] = array_values(array_unique(array_map('strval', $bf)));
            } elseif (! isset($common_settings['bartender_fields']) || ! is_array($common_settings['bartender_fields'])) {
                $common_settings['bartender_fields'] = [
                    'product_name', 'sku', 'barcode', 'barcode_value', 'price',
                    'category', 'brand', 'unit', 'weight', 'description', 'quantity',
                ];
            } else {
                // Repair already-duplicated lists in DB
                $common_settings['bartender_fields'] = array_values(array_unique(array_map('strval', $common_settings['bartender_fields'])));
            }
            unset($common_settings['_bartender_fields_present']);

            if ($request->has('common_settings._bartender_custom_fields_present')
                || $request->has('common_settings.bartender_custom_fields')) {
                $bcf = $request->input('common_settings.bartender_custom_fields', []);
                if (! is_array($bcf)) {
                    $bcf = $bcf === null || $bcf === '' ? [] : [(string) $bcf];
                }
                $common_settings['bartender_custom_fields'] = array_values(array_unique(array_map('strval', $bcf)));
            } elseif (! isset($common_settings['bartender_custom_fields']) || ! is_array($common_settings['bartender_custom_fields'])) {
                $common_settings['bartender_custom_fields'] = [];
            } else {
                $common_settings['bartender_custom_fields'] = array_values(array_unique(array_map('strval', $common_settings['bartender_custom_fields'])));
            }
            unset($common_settings['_bartender_custom_fields_present']);

            // Checkboxes only submit when checked — read from REQUEST (not merged old values)
            $common_settings['enable_commission_masking'] = ! empty($req_common['enable_commission_masking']) ? 1 : 0;
            // Browser Print is the supported label workflow; BarTender export stays disabled.
            $common_settings['enable_bartender_export'] = 0;
            $common_settings['enable_costcode_generation'] = ! empty($req_common['enable_costcode_generation']) ? 1 : 0;
            $common_settings['enable_product_warranty'] = ! empty($req_common['enable_product_warranty']) ? 1 : 0;
            $common_settings['enable_secondary_unit'] = ! empty($req_common['enable_secondary_unit']) ? 1 : 0;
            $common_settings['is_product_image_required'] = ! empty($req_common['is_product_image_required']) ? 1 : 0;

            // Validate the shared Browser Print layout saved from Business Settings.
            if ($request->has('common_settings.browser_label_layout')) {
                $raw_label_layout = $request->input('common_settings.browser_label_layout', []);
                if (! is_array($raw_label_layout)) {
                    $raw_label_layout = [];
                }
                $browser_label_layout = [
                    'label_business_name' => mb_substr(strip_tags(trim((string) ($raw_label_layout['label_business_name'] ?? $business->name))), 0, 80),
                    'label_w' => (string) max(20, min(100, (float) ($raw_label_layout['label_w'] ?? 38))),
                    'label_h' => (string) max(15, min(80, (float) ($raw_label_layout['label_h'] ?? 25))),
                    'business_name_size' => (string) max(6, min(28, (int) ($raw_label_layout['business_name_size'] ?? 8))),
                    'name_size' => (string) max(6, min(32, (int) ($raw_label_layout['name_size'] ?? 9))),
                    'sku_size' => (string) max(5, min(22, (int) ($raw_label_layout['sku_size'] ?? 8))),
                    'price_size' => (string) max(6, min(36, (int) ($raw_label_layout['price_size'] ?? 12))),
                    'costcode_size' => (string) max(5, min(22, (int) ($raw_label_layout['costcode_size'] ?? 8))),
                    'barcode_width' => (string) max(40, min(100, (int) ($raw_label_layout['barcode_width'] ?? 94))),
                    'barcode_height' => (string) max(16, min(90, (int) ($raw_label_layout['barcode_height'] ?? 42))),
                    'barcode_bar_width' => (string) max(1, min(5, (int) ($raw_label_layout['barcode_bar_width'] ?? 4))),
                    'barcode_position' => in_array(($raw_label_layout['barcode_position'] ?? ''), ['top', 'middle', 'bottom'], true)
                        ? $raw_label_layout['barcode_position']
                        : 'bottom',
                    'price_type' => ($raw_label_layout['price_type'] ?? '') === 'exclusive' ? 'exclusive' : 'inclusive',
                ];
                foreach (['show_business_name', 'show_name', 'show_sku', 'show_barcode', 'show_price', 'show_costcode'] as $label_flag) {
                    $browser_label_layout[$label_flag] = ! empty($raw_label_layout[$label_flag])
                        && ! in_array((string) $raw_label_layout[$label_flag], ['0', 'false', 'off'], true)
                            ? '1'
                            : '0';
                }
                $common_settings['browser_label_layout'] = $browser_label_layout;
            }

            // A 10-letter unique keyword is the source of the 0-9 mapping.
            $costcode_keyword = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($req_common['costcode_keyword'] ?? '')));
            $valid_costcode_keyword = strlen($costcode_keyword) === 10
                && count(array_unique(str_split($costcode_keyword))) === 10;
            if ($valid_costcode_keyword) {
                $common_settings['costcode_keyword'] = $costcode_keyword;
                $common_settings['costcode_mapping'] = array_combine(
                    array_map('strval', range(0, 9)),
                    str_split($costcode_keyword)
                );
            } elseif (! empty($req_common['costcode_mapping']) && is_array($req_common['costcode_mapping'])) {
                $map = [];
                foreach ($req_common['costcode_mapping'] as $digit => $letter) {
                    if (is_array($letter)) {
                        $letter = reset($letter);
                    }
                    $letter = strtoupper(trim((string) $letter));
                    if ($letter === '' || ! preg_match('/^[A-Z]/', $letter)) {
                        continue;
                    }
                    $map[(string) $digit] = $letter[0];
                }
                if (! empty($map)) {
                    $common_settings['costcode_mapping'] = $map;
                    if (count($map) === 10 && count(array_unique(array_values($map))) === 10) {
                        ksort($map);
                        $common_settings['costcode_keyword'] = implode('', $map);
                    }
                }
            }

            $main_costcode_map = is_array($common_settings['costcode_mapping'] ?? null)
                ? $common_settings['costcode_mapping']
                : [];
            $main_costcode_letters = [];
            foreach ($main_costcode_map as $letter) {
                if (is_array($letter)) {
                    $letter = reset($letter);
                }
                $letter = strtoupper(trim((string) $letter));
                if (preg_match('/^[A-Z]$/', $letter)) {
                    $main_costcode_letters[] = $letter;
                }
            }

            // Repeated digits use dedicated letters (not from the main word).
            // Letter 1 = 2nd time a digit appears; Letter 2 = 3rd+ times.
            $repeat_zero = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($req_common['costcode_repeat_zero_letter'] ?? '')));
            $repeat_zero = $repeat_zero !== '' ? $repeat_zero[0] : '';
            if ($repeat_zero === '' || in_array($repeat_zero, $main_costcode_letters, true)) {
                $existing_zero = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_repeat_zero_letter'] ?? '')));
                $existing_zero = $existing_zero !== '' ? $existing_zero[0] : '';
                if ($existing_zero === '' && ! empty($existing_common_settings['costcode_repeat_keyword'])) {
                    $existing_zero = strtoupper(substr(preg_replace('/[^A-Z]/i', '', (string) $existing_common_settings['costcode_repeat_keyword']), 0, 1));
                }
                if ($existing_zero !== '' && ! in_array($existing_zero, $main_costcode_letters, true)) {
                    $repeat_zero = $existing_zero;
                } else {
                    $unused_letters = array_values(array_diff(range('A', 'Z'), $main_costcode_letters));
                    $repeat_zero = $unused_letters[0] ?? 'X';
                }
            }
            $common_settings['costcode_repeat_zero_letter'] = $repeat_zero;
            // Keep a tiny compatibility map so older code paths stay safe.
            $common_settings['costcode_repeat_keyword'] = $repeat_zero;
            $common_settings['costcode_repeat_mapping'] = ['0' => $repeat_zero];

            $reserved_special = array_values(array_unique(array_merge($main_costcode_letters, [$repeat_zero])));
            $repeat2 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($req_common['costcode_repeat2_letter'] ?? '')));
            $repeat2 = $repeat2 !== '' ? $repeat2[0] : '';
            if ($repeat2 === '' || in_array($repeat2, $reserved_special, true)) {
                $existing_repeat2 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_repeat2_letter'] ?? '')));
                $existing_repeat2 = $existing_repeat2 !== '' ? $existing_repeat2[0] : '';
                if ($existing_repeat2 !== '' && ! in_array($existing_repeat2, $reserved_special, true)) {
                    $repeat2 = $existing_repeat2;
                } else {
                    $unused_letters = array_values(array_diff(range('A', 'Z'), $reserved_special));
                    $repeat2 = $unused_letters[0] ?? 'Y';
                }
            }
            $common_settings['costcode_repeat2_letter'] = $repeat2;

            // Separate letter used when cost has fewer digits than selling price.
            $reserved_special = array_values(array_unique(array_merge($reserved_special, [$repeat2])));
            $short_cost = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($req_common['costcode_short_cost_letter'] ?? '')));
            $short_cost = $short_cost !== '' ? $short_cost[0] : '';
            if ($short_cost === '' || in_array($short_cost, $reserved_special, true)) {
                $existing_short = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_short_cost_letter'] ?? '')));
                $existing_short = $existing_short !== '' ? $existing_short[0] : '';
                if ($existing_short !== '' && ! in_array($existing_short, $reserved_special, true)) {
                    $short_cost = $existing_short;
                } else {
                    $unused_letters = array_values(array_diff(range('A', 'Z'), $reserved_special));
                    $short_cost = $unused_letters[0] ?? 'W';
                }
            }
            $common_settings['costcode_short_cost_letter'] = $short_cost;

            // Decimal-point letter (middle of prices like 55.50 → …D…).
            $reserved_special = array_values(array_unique(array_merge($reserved_special, [$short_cost])));
            $previous_costcode_decimal = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($existing_common_settings['costcode_decimal_letter'] ?? '')));
            $previous_costcode_decimal = $previous_costcode_decimal !== '' ? $previous_costcode_decimal[0] : '';
            $decimal_letter = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($req_common['costcode_decimal_letter'] ?? '')));
            $decimal_letter = $decimal_letter !== '' ? $decimal_letter[0] : '';
            if ($decimal_letter === '' || in_array($decimal_letter, $reserved_special, true)) {
                if ($previous_costcode_decimal !== '' && ! in_array($previous_costcode_decimal, $reserved_special, true)) {
                    $decimal_letter = $previous_costcode_decimal;
                } else {
                    $unused_letters = array_values(array_diff(range('A', 'Z'), $reserved_special));
                    $decimal_letter = $unused_letters[0] ?? 'D';
                }
            }
            $common_settings['costcode_decimal_letter'] = $decimal_letter;

            if (! empty($common_settings['enable_costcode_generation'])) {
                $normalize_map = function ($map) {
                    if (! is_array($map)) {
                        return [];
                    }
                    $out = [];
                    foreach ($map as $digit => $letter) {
                        if (is_array($letter)) {
                            $letter = reset($letter);
                        }
                        $letter = strtoupper(trim((string) $letter));
                        if (preg_match('/^[A-Z]$/', $letter)) {
                            $out[(string) $digit] = $letter;
                        }
                    }
                    ksort($out);

                    return $out;
                };
                $regenerate_costcodes_after_save = ! $previous_costcode_enabled
                    || $normalize_map($previous_costcode_mapping) != $normalize_map($common_settings['costcode_mapping'] ?? [])
                    || $previous_costcode_repeat_zero !== $repeat_zero
                    || $previous_costcode_repeat2 !== $repeat2
                    || $previous_costcode_short_cost !== $short_cost
                    || $previous_costcode_decimal !== $decimal_letter
                    || $previous_costcode_scheme < 11;
                $common_settings['costcode_scheme_version'] = 11;
            }

            // When cost code is enabled, ensure product custom field 1 is labeled "Cost Code"
            // so List Products shows the column (visibility depends on label text length).
            if (! empty($common_settings['enable_costcode_generation'])) {
                $labels = [];
                if (! empty($business_details['custom_labels'])) {
                    $labels = is_string($business_details['custom_labels'])
                        ? (json_decode($business_details['custom_labels'], true) ?: [])
                        : (array) $business_details['custom_labels'];
                } elseif (! empty($business->custom_labels)) {
                    $labels = is_string($business->custom_labels)
                        ? (json_decode($business->custom_labels, true) ?: [])
                        : (array) $business->custom_labels;
                }
                if (! is_array($labels)) {
                    $labels = [];
                }
                if (! isset($labels['product']) || ! is_array($labels['product'])) {
                    $labels['product'] = [];
                }
                if (empty($labels['product']['custom_field_1'])) {
                    $labels['product']['custom_field_1'] = __('lang_v1.costcode');
                }
                if (empty($labels['product']['custom_field_2'])) {
                    $labels['product']['custom_field_2'] = __('lang_v1.supplier_code');
                }
                $business_details['custom_labels'] = json_encode($labels);
            }
            $common_settings['enable_offline_billing'] = ! empty($req_common['enable_offline_billing']) ? 1 : 0;
            $common_settings['enable_pwa'] = ! empty($req_common['enable_pwa']) ? 1 : 0;
            $common_settings['enable_tax'] = ! empty($req_common['enable_tax']) ? 1 : 0;
            $common_settings['enable_weighing_scale_scan'] = ! empty($req_common['enable_weighing_scale_scan']) ? 1 : 0;
            $common_settings['enable_weighing_scale_label'] = ! empty($req_common['enable_weighing_scale_label']) ? 1 : 0;
            $common_settings['bartender_save_fixed_url'] = ! empty($req_common['bartender_save_fixed_url']) ? 1 : 0;
            $common_settings['bartender_download_browser'] = ! empty($req_common['bartender_download_browser']) ? 1 : 0;
            $common_settings['offline_manual_sync_only'] = ! empty($req_common['offline_manual_sync_only']) ? 1 : 0;
            $common_settings['loyalty_auto_enroll'] = ! empty($req_common['loyalty_auto_enroll']) ? 1 : 0;
            $common_settings['loyalty_ask_mobile_on_pos'] = ! empty($req_common['loyalty_ask_mobile_on_pos']) ? 1 : 0;
            $common_settings['loyalty_otp_on_redeem'] = ! empty($req_common['loyalty_otp_on_redeem']) ? 1 : 0;
            $common_settings['loyalty_redeem_mode'] = ($common_settings['loyalty_redeem_mode'] ?? 'min_points') === 'any' ? 'any' : 'min_points';
            if (! empty($common_settings['loyalty_earn_spend']) && ! empty($common_settings['loyalty_earn_points'])) {
                $spend = $this->businessUtil->num_uf($common_settings['loyalty_earn_spend']);
                $pts = (float) $common_settings['loyalty_earn_points'];
                if ($spend > 0 && $pts > 0) {
                    $business_details['amount_for_unit_rp'] = $spend / $pts;
                    // Store clean numerics (avoid formatted strings breaking @num_format on PHP 8.4)
                    $common_settings['loyalty_earn_spend'] = $spend;
                    $common_settings['loyalty_earn_points'] = $pts;
                }
            }
            if (($common_settings['loyalty_redeem_mode'] ?? '') === 'any') {
                $business_details['min_redeem_point'] = 0;
            }

            if (empty($common_settings['enable_tax'])) {
                $business_details['enable_price_tax'] = 0;
                $business_details['enable_inline_tax'] = 0;
                $pos_settings['disable_order_tax'] = 1;
                $business_details['pos_settings'] = json_encode($pos_settings);
            }

            // Handle PWA icon upload
            if ($request->hasFile('pwa_icon')) {
                $file = $request->file('pwa_icon');
                $filename = 'pwa_icon_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/pwa'), $filename);
                $common_settings['pwa_icon'] = $filename;
            }

            // Persist ebill_settings (checkboxes are only submitted when checked, so default to 0 if absent)
            if (! isset($common_settings['ebill_settings']) || ! is_array($common_settings['ebill_settings'])) {
                $common_settings['ebill_settings'] = [];
            }
            if (! isset($req_common['ebill_settings']['enable_sms_ebill'])) {
                $common_settings['ebill_settings']['enable_sms_ebill'] = 0;
            }
            if (! isset($req_common['ebill_settings']['enable_whatsapp_ebill'])) {
                $common_settings['ebill_settings']['enable_whatsapp_ebill'] = 0;
            }
            if (! isset($req_common['ebill_settings']['prompt_before_send'])) {
                $common_settings['ebill_settings']['prompt_before_send'] = 0;
            }

            $business_details['common_settings'] = $common_settings;

            // Enabled modules — Soft Owner only (shop users manage via Soft Owner Access).
            // Never wipe modules when Admin saves other settings without the Modules fields.
            $is_so = method_exists(auth()->user(), 'isSoftwareOwner') && auth()->user()->isSoftwareOwner();
            if ($is_so && $request->has('enabled_modules')) {
                $enabled_modules = $request->input('enabled_modules');
                if (! is_array($enabled_modules)) {
                    $enabled_modules = ! empty($enabled_modules) ? [(string) $enabled_modules] : [];
                }
                // Turning on MyReload API for this business must unlock the Reload module
                // (POS header / sidebar check enabled_modules, not only the API toggle).
                if (! empty($common_settings['reload']['myreload_enabled'])
                    && ! in_array('reload', $enabled_modules, true)) {
                    $enabled_modules[] = 'reload';
                }
                $business_details['enabled_modules'] = ! empty($enabled_modules) ? array_values(array_unique($enabled_modules)) : null;
                $business_details['enabled_modules'] = \App\Utils\ShopModeUtil::overlayIndustryModules(
                    $business_details['enabled_modules'] ?? [],
                    (int) $business->id
                );
            } elseif (! empty($common_settings['reload']['myreload_enabled'])) {
                // Soft-add reload if MyReload enabled, without clearing other modules
                $current = is_array($business->enabled_modules) ? $business->enabled_modules : [];
                if (! in_array('reload', $current, true)) {
                    $current[] = 'reload';
                    $business_details['enabled_modules'] = array_values(array_unique($current));
                }
            }
            $business->fill($business_details);
            $business->save();
            $business->refresh();

            // Wholesale module: ensure Wholesale selling price group exists for POS / products.
            if (in_array('wholesale', $enabled_modules ?? [], true)) {
                \App\Utils\WholesaleUtil::ensurePriceGroup($business_id);
            }

            // A mapping or encoding-scheme change must update every existing product,
            // not only products created after the setting was changed.
            if ($regenerate_costcodes_after_save) {
                Product::where('business_id', $business_id)
                    ->orderBy('id')
                    ->chunkById(100, function ($products) use (&$regenerated_costcode_count, $common_settings) {
                        foreach ($products as $product) {
                            $this->productUtil->generateAndSaveCostCode($product, $common_settings);
                            $regenerated_costcode_count++;
                        }
                    });
            }

            $debugLog('Saved OK', [
                'enable_bartender_export' => $common_settings['enable_bartender_export'] ?? null,
                'enable_weighing_scale_label' => $common_settings['enable_weighing_scale_label'] ?? null,
                'business_name' => $business->name,
                'date_format' => $business->date_format,
                'start_date' => $business->start_date,
                'saver' => $request->input('_qpos_saver'),
                'field_count_meta' => $request->input('_settings_field_count'),
            ]);

            //update session data (fresh from DB so header/UI match form)
            $request->session()->put('business', $business);
            $request->session()->forget('business_details'); // legacy keys if any

            //Update Currency details
            $currency = Currency::find($business->currency_id);
            if (! empty($currency)) {
                $request->session()->put('currency', [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'thousand_separator' => $currency->thousand_separator,
                    'decimal_separator' => $currency->decimal_separator,
                ]);
            }

            //update current financial year to session
            $financial_year = $this->businessUtil->getCurrentFinancialYear($business->id);
            $request->session()->put('financial_year', $financial_year);

            $output = ['success' => 1,
                'msg' => $regenerated_costcode_count > 0
                    ? __('lang_v1.costcode_settings_saved_and_updated', ['count' => $regenerated_costcode_count])
                    : __('business.settings_updated_success'),
            ];
        } catch (\Exception $e) {
            $debugLog('FAILED: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong').' ('.$e->getMessage().')',
            ];
        }

        // Always flash for the standard top-right toastr (#status_span in layout)
        session()->flash('status', $output);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect('business/settings')->with('status', $output);
    }

    /**
     * Handles the validation email
     *
     * @return \Illuminate\Http\Response
     */
    public function postCheckEmail(Request $request)
    {
        if(config('constants.do_not_allow_disposable_email') && $request->input('is_disposable_email')) {

            $email_validator = Validator::make($request->only('email'), [
                'email' => 'email|indisposable',
            ], [
                'email.indisposable' => __('validation.indisposable'),
            ]);

            if ($email_validator->fails()) {
                echo $email_validator->errors()->first('email');
                exit;
            }
        }

        // Second: uniqueness check (existing behavior)
        $query = User::where('email', $request->input('email'));

        if (! empty($request->input('user_id'))) {
            $user_id = $request->input('user_id');
            $query->where('id', '!=', $user_id);
        }

        $exists = $query->exists();
        if (! $exists) {
            echo 'true';
            exit;
        } else {
            // If indisposable mode, return message string; else preserve boolean 'false' for legacy consumers.
            if ($request->boolean('is_disposable_email')) {
                echo __('validation.unique', ['attribute' => __('business.email')]);
            } else {
                echo 'false';
            }
            exit;
        }
    }

    public function getEcomSettings()
    {
        try {
            $api_token = request()->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $settings = Business::where('id', $api_settings->business_id)
                        ->value('ecom_settings');

            $settings_array = ! empty($settings) ? json_decode($settings, true) : [];

            if (! empty($settings_array['slides'])) {
                foreach ($settings_array['slides'] as $key => $value) {
                    $settings_array['slides'][$key]['image_url'] = ! empty($value['image']) ? url('uploads/img/'.$value['image']) : '';
                }
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong($e);
        }

        return $this->respond($settings_array);
    }

    /**
     * Handles the testing of email configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function toggleIcMode(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $biz = \App\Business::find($business_id);
        $common_settings = ! empty($biz->common_settings) ? $biz->common_settings : [];
        $stored_pin = $common_settings['ic_mode_pin'] ?? null;

        if (empty($stored_pin)) {
            return response()->json(['success' => false, 'msg' => 'IC Mode PIN not configured.']);
        }

        $pin = $request->input('pin');

        if ($pin !== (string) $stored_pin) {
            return response()->json(['success' => false, 'msg' => 'Incorrect PIN.']);
        }

        $current = $request->session()->get('ic_mode', false);
        $newState = ! $current;
        $request->session()->put('ic_mode', $newState);

        // Store activation timestamp and timeout when enabling
        if ($newState) {
            $timeoutMinutes = !empty($common_settings['ic_mode_timeout_minutes']) ? (int) $common_settings['ic_mode_timeout_minutes'] : 5;
            $request->session()->put('ic_mode_activated_at', now()->timestamp);
            $request->session()->put('ic_mode_timeout_minutes', $timeoutMinutes);
        } else {
            $request->session()->forget('ic_mode_activated_at');
            $request->session()->forget('ic_mode_timeout_minutes');
        }

        return response()->json(['success' => true, 'active' => $newState]);
    }

    public function testEmailConfiguration(Request $request)
    {
        try {
            $email_settings = $request->input();

            $data['email_settings'] = $email_settings;
            \Notification::route('mail', $email_settings['mail_from_address'])
            ->notify(new TestEmailNotification($data));

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.email_tested_successfully'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Handles the testing of sms configuration
     *
     * @return \Illuminate\Http\Response
     */
    public function testSmsConfiguration(Request $request)
    {
        try {
            $sms_settings = $request->input();

            $data = [
                'sms_settings' => $sms_settings,
                'mobile_number' => $sms_settings['test_number'] ?? null,
                'sms_body' => 'This is a test SMS from QPOS via '.($sms_settings['sms_service'] ?? 'SMS'),
            ];
            if (! empty($sms_settings['test_number'])) {
                $response = $this->businessUtil->sendSms($data);
                $sms_service = $sms_settings['sms_service'] ?? 'other';

                if (in_array($sms_service, ['smslenz', 'notifylk', 'nexmo', 'twilio'], true)) {
                    return [
                        'success' => true,
                        'msg' => 'Test SMS sent successfully via '.$sms_service,
                    ];
                }

                $parameter_type = isset($sms_settings['data_parameter_type']) ? $sms_settings['data_parameter_type'] : 'form-data';

                if ($parameter_type == 'json' && is_object($response) && method_exists($response, 'getBody')) {
                    $body = json_decode($response->getBody(), true);

                    return ['success' => true, 'msg' => 'SMS sent successfully', 'data' => $body];
                }
            } else {
                $response = __('lang_v1.test_number_is_required');
            }

            $output = [
                'success' => 1,
                'msg' => is_string($response) ? $response : 'SMS sent successfully',
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        return $output;
    }

    /**
     * Return dynamic PWA manifest JSON based on business settings.
     */
    public function pwaManifest()
    {
        $business = null;
        if (auth()->check()) {
            $business_id = request()->session()->get('user.business_id');
            $business = Business::find($business_id);
        }

        $cs = ! empty($business->common_settings) ? $business->common_settings : [];
        $isEnabled = ! empty($cs['enable_pwa']);

        $name = ! empty($cs['pwa_name']) ? $cs['pwa_name'] : 'QPOS';
        if (stripos($name, 'vsoft') !== false) {
            $name = 'QPOS';
        }
        $shortName = ! empty($cs['pwa_short_name']) ? $cs['pwa_short_name'] : 'QPOS';
        if (stripos($shortName, 'vsoft') !== false) {
            $shortName = 'QPOS';
        }
        $themeColor = ! empty($cs['pwa_theme_color']) ? $cs['pwa_theme_color'] : '#f5c518';
        $bgColor = ! empty($cs['pwa_bg_color']) ? $cs['pwa_bg_color'] : '#0a0a0a';
        $display = ! empty($cs['pwa_display']) ? $cs['pwa_display'] : 'standalone';
        $startUrl = ! empty($cs['pwa_start_url']) ? '/' . $cs['pwa_start_url'] : '/pos';
        $icon = ! empty($cs['pwa_icon']) ? asset('uploads/pwa/' . $cs['pwa_icon']) : asset('img/qpos-icon.png');

        $manifest = [
            'name' => $name,
            'short_name' => $shortName,
            'description' => 'Smart Point of Sale System',
            'start_url' => $startUrl,
            'display' => $display,
            'background_color' => $bgColor,
            'theme_color' => $themeColor,
            'orientation' => 'any',
            'scope' => '/',
            'icons' => [
                [
                    'src' => $icon,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $icon,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'categories' => ['business', 'finance'],
        ];

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Flatten accidental array scalars in settings JSON (duplicate form fields).
     * Keeps real list/nested keys (bartender_fields, ebill_settings, etc.).
     */
    protected function flattenScalarSettingsArray(array $settings, array $keepNested = ['ebill_settings', 'mobile_shop', 'tyre_shop', 'reload', 'communication', 'nutmeg', 'bartender_fields', 'bartender_custom_fields', 'browser_label_layout', 'costcode_mapping', 'costcode_repeat_mapping']): array
    {
        foreach ($settings as $key => $value) {
            if (! is_array($value)) {
                continue;
            }
            // Never flatten digit maps / nested objects — 0..9 keys look "list-like"
            // and were being collapsed to a single letter, wiping repeat mappings.
            if (in_array($key, $keepNested, true)
                || str_ends_with((string) $key, '_fields')
                || str_ends_with((string) $key, '_mapping')
                || str_ends_with((string) $key, '_layout')) {
                continue;
            }
            // Keep associative nested objects
            if ($value !== [] && array_keys($value) !== range(0, count($value) - 1)) {
                continue;
            }
            $flat = null;
            array_walk_recursive($value, function ($item) use (&$flat) {
                if (! is_array($item)) {
                    $flat = $item;
                }
            });
            $settings[$key] = $flat;
        }

        // Repair bartender_fields if earlier fix turned it into a string
        if (isset($settings['bartender_fields']) && ! is_array($settings['bartender_fields'])) {
            $settings['bartender_fields'] = [
                'product_name', 'sku', 'barcode', 'barcode_value', 'price',
                'category', 'brand', 'unit', 'weight', 'description', 'quantity',
            ];
        }
        if (isset($settings['bartender_custom_fields']) && ! is_array($settings['bartender_custom_fields'])) {
            $settings['bartender_custom_fields'] = [];
        }

        return $settings;
    }

    /**
     * Clear Laravel caches (views/config/route/app) from Business Settings.
     */
    public function clearAppCache(Request $request)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $user = auth()->user();
        $allowed = $user->can('business_settings.access')
            || $user->can('superadmin')
            || $user->hasRole('Admin#'.session('user.business_id'));

        if (! $allowed) {
            abort(403, 'Unauthorized action.');
        }

        $cleared = [];
        $errors = [];

        try {
            \Artisan::call('view:clear');
            $cleared[] = 'views';
        } catch (\Throwable $e) {
            $errors[] = 'view: '.$e->getMessage();
        }

        try {
            \Artisan::call('cache:clear');
            $cleared[] = 'cache';
        } catch (\Throwable $e) {
            $errors[] = 'cache: '.$e->getMessage();
        }

        try {
            \Artisan::call('config:clear');
            $cleared[] = 'config';
        } catch (\Throwable $e) {
            $errors[] = 'config: '.$e->getMessage();
        }

        try {
            \Artisan::call('route:clear');
            $cleared[] = 'routes';
        } catch (\Throwable $e) {
            $errors[] = 'route: '.$e->getMessage();
        }

        // Manual fallbacks (shared hosting / no artisan write)
        $viewDir = storage_path('framework/views');
        $n = 0;
        if (is_dir($viewDir)) {
            foreach (glob($viewDir.'/*.php') ?: [] as $f) {
                if (@unlink($f)) {
                    $n++;
                }
            }
            if ($n > 0) {
                $cleared[] = "blade({$n})";
            }
        }

        foreach ([
            base_path('bootstrap/cache/config.php'),
            base_path('bootstrap/cache/routes-v7.php'),
            base_path('bootstrap/cache/services.php'),
            base_path('bootstrap/cache/packages.php'),
        ] as $cacheFile) {
            if (is_file($cacheFile) && @unlink($cacheFile)) {
                $cleared[] = basename($cacheFile);
            }
        }

        // Bust stale barcode CSV so CDN/browser picks up fresh export next time
        $csv = public_path('exports/barcode_labels.csv');
        if (is_file($csv)) {
            @touch($csv);
            $cleared[] = 'barcode_labels.csv';
        }

        $msg = __('lang_v1.cache_cleared_success');
        if (! empty($cleared)) {
            $msg .= ' ('.implode(', ', $cleared).')';
        }
        if (! empty($errors)) {
            $msg .= ' — warnings: '.implode('; ', $errors);
        }

        return response()->json([
            'success' => 1,
            'msg' => $msg,
            'cleared' => $cleared,
            'errors' => $errors,
        ]);
    }
}

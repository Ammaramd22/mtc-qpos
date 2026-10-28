<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Rules\ReCaptcha;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->middleware('guest')->except('logout');
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    public function showLoginForm()
    {
        $demo_shops = $this->getAvailableDemoShops();
        $login_brand = $this->resolveLoginBrand($demo_shops);

        return view('auth.login', compact('demo_shops', 'login_brand'));
    }

    /**
     * Branding for the login screen (QPOS logo + optional shop context e.g. Nutmeg).
     */
    protected function resolveLoginBrand(array $demo_shops): array
    {
        $brand = [
            'app_name' => config('app.name', 'QPOS'),
            'logo' => asset('img/qpos-icon.png'),
            'shop_key' => null,
            'shop_name' => null,
            'shop_tagline' => null,
            'theme' => 'qpos',
            'accent' => '#f5c518',
            'background_url' => \App\Utils\SoftwareOwnerUtil::loginBackgroundUrl(),
        ];

        $requested = strtolower((string) request()->query('shop', request()->query('demo', '')));
        $picked = null;

        foreach ($demo_shops as $shop) {
            if ($requested !== '' && (
                $requested === strtolower((string) ($shop['username'] ?? ''))
                || $requested === strtolower((string) ($shop['theme'] ?? ''))
                || str_contains(strtolower((string) ($shop['name'] ?? '')), $requested)
            )) {
                $picked = $shop;
                break;
            }
        }

        // Prefer Nutmeg when present and nothing else was requested
        if (! $picked) {
            foreach ($demo_shops as $shop) {
                if (($shop['theme'] ?? '') === 'nutmeg' || ($shop['username'] ?? '') === 'nutmeg') {
                    $picked = $shop;
                    break;
                }
            }
        }

        if ($picked) {
            $brand['shop_key'] = $picked['username'] ?? null;
            $brand['shop_name'] = $picked['business_name'] ?? $picked['name'] ?? null;
            $brand['shop_tagline'] = $picked['tagline'] ?? null;
            $brand['theme'] = $picked['theme'] ?? 'qpos';
            $brand['accent'] = $picked['accent'] ?? $brand['accent'];
        }

        return $brand;
    }

    /**
     * Local / seeded demo businesses shown as one-click login boxes.
     */
    protected function getAvailableDemoShops()
    {
        if (! config('app.show_demo_shops')) {
            return [];
        }

        $catalog = [
            'nutmeg' => [
                'name' => 'Nutmeg Buying & Selling',
                'tagline' => 'POP + AVCO trading',
                'icon' => 'fa-seedling',
                'accent' => '#52b788',
                'theme' => 'nutmeg',
            ],
            'fishshop' => [
                'name' => 'Fish Shop',
                'tagline' => 'Touch POS seafood',
                'icon' => 'fa-fish',
                'accent' => '#0077b6',
                'theme' => 'fishshop',
            ],
            'supermarket' => [
                'name' => 'Supermarket',
                'tagline' => 'Retail & grocery',
                'icon' => 'fa-shopping-cart',
                'accent' => '#6a4c93',
                'theme' => 'supermarket',
            ],
            'grocery' => [
                'name' => 'Grocery Shop',
                'tagline' => 'Daily essentials',
                'icon' => 'fa-apple-alt',
                'accent' => '#e76f51',
                'theme' => 'grocery',
            ],
            'carpetshop' => [
                'name' => 'Carpet Shop',
                'tagline' => 'Flooring & textiles',
                'icon' => 'fa-th',
                'accent' => '#bc6c25',
                'theme' => 'carpetshop',
            ],
            'mobileshop' => [
                'name' => 'Mobile Shop',
                'tagline' => 'Phones · IMEI · Reload',
                'icon' => 'fa-mobile-alt',
                'accent' => '#4cc9f0',
                'theme' => 'mobileshop',
            ],
            'tyreshop' => [
                'name' => 'Tyre Shop',
                'tagline' => 'Car · Bike · Van tyres',
                'icon' => 'fa-circle-notch',
                'accent' => '#c1121f',
                'theme' => 'tyreshop',
            ],
            'packshop' => [
                'name' => 'Packaging Shop',
                'tagline' => 'Food packs · Tissue · Pizza boxes',
                'icon' => 'fa-box-open',
                'accent' => '#e9c46a',
                'theme' => 'packshop',
            ],
        ];

        $users = \App\User::whereIn('username', array_keys($catalog))
            ->where('status', 'active')
            ->where('allow_login', 1)
            ->with('business:id,name,is_active')
            ->get()
            ->keyBy('username');

        $shops = [];
        foreach ($catalog as $username => $meta) {
            $user = $users->get($username);
            if ($user && ! empty($user->business) && $user->business->is_active) {
                $shops[] = array_merge($meta, [
                    'username' => $username,
                    'business_name' => $user->business->name,
                ]);

                continue;
            }

            // Still list the tile when SHOW_DEMO_SHOPS is on so the login page
            // never looks empty if a demo user is missing / inactive.
            $shops[] = array_merge($meta, [
                'username' => $username,
                'business_name' => $meta['name'],
            ]);
        }

        return $shops;
    }

    /**
     * Change authentication from email to username
     *
     * @return void
     */
    public function username()
    {
        return 'username';
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $this->businessUtil->activityLog($user, 'logout');
        }

        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, proxy-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * The user has been authenticated.
     * Check if the business is active or not.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        $this->businessUtil->activityLog($user, 'login', null, [], false, $user->business_id);

        if (! $user->business->is_active) {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.business_inactive')]
              );
        } elseif ($user->status != 'active') {
            \Auth::logout();

            return redirect('/login')
              ->with(
                  'status',
                  ['success' => 0, 'msg' => __('lang_v1.user_inactive')]
              );
        } elseif (! $user->allow_login) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.login_not_allowed')]
                );
        } elseif (($user->user_type == 'user_customer') && ! $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'crm_module')) {
            \Auth::logout();

            return redirect('/login')
                ->with(
                    'status',
                    ['success' => 0, 'msg' => __('lang_v1.business_dont_have_crm_subscription')]
                );
        }
    }

    protected function redirectTo()
    {
        $user = \Auth::user();
        if (! $user->can('dashboard.data') && $user->can('sell.create')) {
            return '/pos/create';
        }

        if ($user->user_type == 'user_customer') {
            return 'contact/contact-dashboard';
        }

        return '/home';
    }

    public function validateLogin(Request $request)
    {
        if(config('constants.enable_recaptcha')){
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
                'g-recaptcha-response' => ['required', new ReCaptcha]
            ]);
        }else{
            $this->validate($request, [
                $this->username() => 'required|string',
                'password' => 'required|string',
            ]);
        }
       
    }

    /**
     * Log in with username + PIN.
     */
    public function loginWithPin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'pin' => 'required|digits_between:4,6',
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'pin' => [__('lang_v1.invalid_pin')],
            ]);
        }

        if (empty($user->login_pin)) {
            throw ValidationException::withMessages([
                'pin' => [__('lang_v1.pin_not_set')],
            ]);
        }

        if (! Hash::check($request->input('pin'), $user->login_pin)) {
            throw ValidationException::withMessages([
                'pin' => [__('lang_v1.invalid_pin')],
            ]);
        }

        if ($request->boolean('remember')) {
            $this->guard()->login($user, true);
        } else {
            $this->guard()->login($user);
        }

        $request->session()->regenerate();

        return $this->sendLoginResponse($request);
    }

    /**
     * Set up PIN login (guest — requires current password).
     */
    public function setupLoginPin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'pin' => 'required|digits_between:4,6|confirmed',
        ], [
            'pin.digits_between' => __('lang_v1.pin_must_be_4_to_6_digits'),
            'pin.confirmed' => __('lang_v1.pin_must_be_4_to_6_digits'),
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('lang_v1.u_have_entered_wrong_password')],
            ]);
        }

        if (! $user->allow_login) {
            throw ValidationException::withMessages([
                'username' => [__('lang_v1.login_not_allowed')],
            ]);
        }

        $user->login_pin = Hash::make($request->input('pin'));
        $user->save();

        return redirect()->route('login')->with('status', [
            'success' => 1,
            'msg' => __('lang_v1.pin_setup_success'),
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Mail\RegisterReportMail;
use App\User;
use App\Utils\CashRegisterUtil;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class CashRegisterController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $cashRegisterUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  CashRegisterUtil  $cashRegisterUtil
     * @return void
     */
    public function __construct(CashRegisterUtil $cashRegisterUtil, ModuleUtil $moduleUtil)
    {
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('cash_register.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if yes then redirect to POS screen.
        if ($this->cashRegisterUtil->countOpenedRegister() != 0) {
            return redirect()->action([\App\Http\Controllers\SellPosController::class, 'create'], ['sub_type' => $sub_type]);
        }
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('cash_register.create')->with(compact('business_locations', 'sub_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //like:repair
        $sub_type = request()->get('sub_type');

        try {
            $initial_amount = 0;
            if (! empty($request->input('amount'))) {
                $initial_amount = $this->cashRegisterUtil->num_uf($request->input('amount'));
            }
            $user_id = $request->session()->get('user.id');
            $business_id = $request->session()->get('user.business_id');

            $register = CashRegister::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'status' => 'open',
                'location_id' => $request->input('location_id'),
                'created_at' => \Carbon::now()->format('Y-m-d H:i:00'),
            ]);
            if (! empty($initial_amount)) {
                $register->cash_register_transactions()->create([
                    'amount' => $initial_amount,
                    'pay_method' => 'cash',
                    'type' => 'credit',
                    'transaction_type' => 'initial',
                ]);
            }
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
        }

        return redirect()->action([\App\Http\Controllers\SellPosController::class, 'create'], ['sub_type' => $sub_type]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\CashRegister  $cashRegister
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details = $this->cashRegisterUtil->getRegisterDetails($id);
        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = ! empty($register_details['closed_at']) ? $register_details['closed_at'] : \Carbon::now()->toDateTimeString();
        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time);

        $payment_types = $this->cashRegisterUtil->payment_types(null, false, $business_id);

        $due_applied_to_register = $this->cashRegisterUtil->getDuePaymentsForCurrentRegisterSales($id);

        return view('cash_register.register_details')
                    ->with(compact('register_details', 'details', 'payment_types', 'close_time', 'due_applied_to_register'));
    }

    /**
     * Shows register details modal.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getRegisterDetails()
    {
        if (! auth()->user()->can('view_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $register_details = $this->cashRegisterUtil->getRegisterDetails();

        $user_id = auth()->user()->id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);

        $due_applied_to_register = $this->cashRegisterUtil->getDuePaymentsForCurrentRegisterSales();

        return view('cash_register.register_details')
                ->with(compact('register_details', 'details', 'payment_types', 'close_time', 'due_applied_to_register'));
    }

    /**
     * Shows close register form.
     *
     * @param  void
     * @return \Illuminate\Http\Response
     */
    public function getCloseRegister($id = null)
    {
        if (! auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $register_details = $this->cashRegisterUtil->getRegisterDetails($id);

        $user_id = $register_details->user_id;
        $open_time = $register_details['open_time'];
        $close_time = \Carbon::now()->toDateTimeString();

        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $details = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $payment_types = $this->cashRegisterUtil->payment_types($register_details->location_id, true, $business_id);

        $pos_settings = ! empty(request()->session()->get('business.pos_settings')) ? json_decode(request()->session()->get('business.pos_settings'), true) : [];

        $due_applied_to_register = $this->cashRegisterUtil->getDuePaymentsForCurrentRegisterSales($id);

        return view('cash_register.close_register_modal')
                    ->with(compact('register_details', 'details', 'payment_types', 'pos_settings', 'due_applied_to_register'));
    }

    /**
     * Closes currently opened register.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postCloseRegister(Request $request)
    {
        if (! auth()->user()->can('close_cash_register')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                    'msg' => 'Feature disabled in demo!!',
                ];

                return redirect()->action([\App\Http\Controllers\HomeController::class, 'index'])->with('status', $output);
            }

            $input = $request->only(['closing_amount', 'total_card_slips', 'total_cheques', 'closing_note']);
            $input['closing_amount'] = $this->cashRegisterUtil->num_uf($input['closing_amount']);
            $user_id = $request->input('user_id');
            $input['closed_at'] = \Carbon::now()->format('Y-m-d H:i:s');
            $input['status'] = 'close';
            $input['denominations'] = ! empty(request()->input('denominations')) ? json_encode(request()->input('denominations')) : null;

            CashRegister::where('user_id', $user_id)
                                ->where('status', 'open')
                                ->update($input);

            // Send email report to business owner if requested
            if ($request->input('send_report_email') == 1) {
                try {
                    $this->sendRegisterReportEmail($user_id, $input);
                } catch (\Exception $mailEx) {
                    \Log::error('Register report email failed: ' . $mailEx->getMessage());
                }
            }

            $output = ['success' => 1,
                'msg' => __('cash_register.close_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Add cash in / cash out to the current open register.
     */
    public function postCashInOut(Request $request)
    {
        $request->validate([
            'type'   => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string|max:255',
        ]);

        try {
            $user_id  = auth()->user()->id;
            $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

            if (empty($register)) {
                return response()->json(['success' => 0, 'msg' => __('cash_register.no_open_register')]);
            }

            $register->cash_register_transactions()->create([
                'amount'           => $this->cashRegisterUtil->num_uf($request->input('amount')),
                'pay_method'       => 'cash',
                'type'             => $request->input('type') === 'cash_in' ? 'credit' : 'debit',
                'transaction_type' => $request->input('type'),
                'note'             => $request->input('note'),
            ]);

            return response()->json(['success' => 1, 'msg' => __('lang_v1.success')]);
        } catch (\Exception $e) {
            \Log::error('Cash in/out error: ' . $e->getMessage());
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Send register close report to business owner email.
     */
    protected function sendRegisterReportEmail($user_id, $input)
    {
        $business_id = request()->session()->get('user.business_id');
        $business    = Business::find($business_id);
        $owner       = User::find($business->owner_id);

        // Determine recipient: report_email from common_settings, fallback to owner email
        $common  = ! empty($business->common_settings) ? $business->common_settings : [];
        $to_email = ! empty($common['register_report_email'])
            ? $common['register_report_email']
            : ($owner ? $owner->email : null);

        if (empty($to_email)) {
            return;
        }

        // Configure mailer from business email_settings
        $email_settings = ! empty($business->email_settings) ? $business->email_settings : [];
        if (! empty($email_settings['mail_host'])) {
            Config::set('mail.mailers.smtp.host',       $email_settings['mail_host']);
            Config::set('mail.mailers.smtp.port',       $email_settings['mail_port'] ?? 587);
            Config::set('mail.mailers.smtp.username',   $email_settings['mail_username'] ?? null);
            Config::set('mail.mailers.smtp.password',   $email_settings['mail_password'] ?? null);
            Config::set('mail.mailers.smtp.encryption', $email_settings['mail_encryption'] ?? 'tls');
            Config::set('mail.from.address', $email_settings['mail_from_address'] ?? $to_email);
            Config::set('mail.from.name',    $business->name);
        }

        // Build report data
        $is_types_of_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');
        $register_details = $this->cashRegisterUtil->getRegisterDetails();
        $open_time  = $register_details->open_time ?? \Carbon::now()->subDay()->toDateTimeString();
        $close_time = $input['closed_at'];
        $details    = $this->cashRegisterUtil->getRegisterTransactionDetails($user_id, $open_time, $close_time, $is_types_of_service_enabled);

        $reportData = [
            'register_details'    => $register_details,
            'transaction_details' => $details['transaction_details'],
            'closing_amount'      => $input['closing_amount'] ?? 0,
            'closing_note'        => $input['closing_note'] ?? '',
            'due_applied'         => $this->cashRegisterUtil->getDuePaymentsForCurrentRegisterSales() ?? 0,
        ];

        $closedAt = \Carbon::parse($close_time)->format('d M Y, h:i A');

        Mail::to($to_email)->send(new RegisterReportMail($reportData, $business->name, $closedAt));
    }
}

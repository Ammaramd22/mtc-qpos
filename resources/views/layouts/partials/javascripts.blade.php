<script type="text/javascript">
    base_path = "{{ url('/') }}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{ config('broadcasting.connections.pusher.key') }}';
    APP.PUSHER_APP_CLUSTER = '{{ config('broadcasting.connections.pusher.options.cluster') }}';
    APP.INVOICE_SCHEME_SEPARATOR = '{{ config('constants.invoice_scheme_separator') }}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{ $__is_pusher_enabled }}';
    @auth
    @php
        $user = Auth::user();
    @endphp
    APP.USER_ID = "{{ $user->id }}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->

{{-- jQuery already loaded in layouts/app head + before this include; keep a third guard. --}}
<script src="{{ asset('js/jquery-3.7.1.min.js?v=' . $asset_v) }}"></script>
<script>
    if (typeof window.jQuery !== 'undefined') {
        window.$ = window.jQuery;
    } else {
        console.error('QPOS: jQuery missing. Upload public/js/jquery-3.7.1.min.js and clear view cache.');
    }
</script>
<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/pos-theme.js?v=' . $asset_v) }}"></script>
<script>
    if (typeof window.jQuery !== 'undefined') {
        window.$ = window.$ || window.jQuery;
    }
</script>

@if (file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);

    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);

    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if ($business_time_format == 12) {
        $moment_time_format = 'hh:mm A';
    }

    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];

    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries'])
        ? $common_settings['default_datatable_page_entries']
        : 25;
@endphp

<script>
    Dropzone.autoDiscover = false;
    moment.tz.setDefault('{{ Session::get('business.time_zone') }}');
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get('financial_year.start') }}'),
        end: moment('{{ Session::get('financial_year.end') }}'),
    }
    @if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
        //Default setting for select2
        $.fn.select2.defaults.set("language", "{{ session()->get('user.language', config('app.locale')) }}");
    @endif

    var datepicker_date_format = "{{ $datepicker_date_format }}";
    var moment_date_format = "{{ $moment_date_format }}";
    var moment_time_format = "{{ $moment_time_format }}";

    var app_locale = "{{ session()->get('user.language', config('app.locale')) }}";

    var non_utf8_languages = [
        @foreach (config('constants.non_utf8_languages') as $const)
            "{{ $const }}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{ $default_datatable_page_entries }}";

    var __new_notification_count_interval = "{{ config('constants.new_notification_count_interval', 60) }}000";

    @auth
    var __ic_mode_url = "{{ route('ic_mode.toggle') }}";
    var __ic_mode_active = {{ session('ic_mode', false) ? 'true' : 'false' }};
    @php
        $__biz_cs = session('business') ? (is_object(session('business')) ? session('business')->common_settings : (session('business')['common_settings'] ?? [])) : [];
        $__biz_ic_key = (!empty($__biz_cs) && !empty($__biz_cs['ic_mode_shortcut_key'])) ? $__biz_cs['ic_mode_shortcut_key'] : 'F7';
        $__user_bank_raw = auth()->user()->bank_details;
        $__user_bank = $__user_bank_raw ? (is_array($__user_bank_raw) ? $__user_bank_raw : json_decode($__user_bank_raw, true)) : [];
        $__user_ic_key = $__user_bank['ic_shortcut_key'] ?? '';
        $__ic_key = !empty($__user_ic_key) ? $__user_ic_key : $__biz_ic_key;
    @endphp
    var __ic_shortcut_key = "{{ $__ic_key }}";
    @php
        $__ic_timeout = session('ic_mode_timeout_minutes', 5);
        $__ic_activated_at = session('ic_mode_activated_at');
    @endphp
    var __ic_timeout_minutes = {{ $__ic_timeout }};
    var __ic_activated_at = {{ $__ic_activated_at ? $__ic_activated_at : 'null' }};
    @endauth
</script>

@auth
<script>
(function() {
    var icOverlay = null;
    var icBackdrop = null;

    function showIcPrompt() {
        if (icOverlay) return;

        icBackdrop = document.createElement('div');
        icBackdrop.id = '__ic_backdrop';
        icBackdrop.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.72);z-index:99998;backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);';

        icOverlay = document.createElement('div');
        icOverlay.id = '__ic_overlay';
        icOverlay.className = 'vspos-ic-prompt';
        var icLight = document.documentElement.getAttribute('data-vspos-theme') === 'light';
        icOverlay.style.cssText = [
            'position:fixed',
            'top:50%',
            'left:50%',
            'transform:translate(-50%,-50%)',
            icLight ? 'background:#ffffff' : 'background:#141414',
            icLight ? 'border:1px solid #e2e8f0' : 'border:1px solid #2a2a2a',
            'border-radius:16px',
            'padding:20px 22px',
            'z-index:99999',
            icLight ? 'box-shadow:0 24px 60px rgba(15,23,42,0.16)' : 'box-shadow:0 24px 60px rgba(0,0,0,0.55)',
            'min-width:280px',
            'max-width:92vw',
            icLight ? 'color:#111827' : 'color:#f5f5f5',
            'font-family:var(--qpos-font,\'Plus Jakarta Sans\',sans-serif)'
        ].join(';');

        var label = document.createElement('div');
        label.style.cssText = 'font-size:12px;color:#b45309;margin-bottom:10px;letter-spacing:0.06em;font-weight:800;text-transform:uppercase;';
        if (!icLight) {
            label.style.color = '#f5c518';
        }
        label.textContent = __ic_mode_active ? 'Deactivate IC Mode' : 'Enter PIN';

        var hint = document.createElement('div');
        hint.style.cssText = 'font-size:12px;color:' + (icLight ? '#475569' : '#a3a3a3') + ';margin-bottom:12px;line-height:1.4;';
        hint.textContent = __ic_mode_active ? 'Enter PIN to turn off IC mode' : 'Enter PIN to show real values';

        var input = document.createElement('input');
        input.type = 'password';
        input.placeholder = 'Enter PIN';
        input.style.cssText = [
            'width:100%',
            'border:1px solid #333',
            'border-radius:10px',
            'padding:12px 14px',
            'font-size:18px',
            'font-weight:700',
            'letter-spacing:0.28em',
            'outline:none',
            'box-sizing:border-box',
            icLight ? 'background:#ffffff' : 'background:#0a0a0a',
            icLight ? 'color:#111827' : 'color:#fafafa',
            icLight ? 'caret-color:#b45309' : 'caret-color:#f5c518',
            icLight ? '-webkit-text-fill-color:#111827' : '-webkit-text-fill-color:#fafafa',
            icLight ? 'color-scheme:light' : 'color-scheme:dark',
            icLight ? 'border-color:#d1d5db' : 'border-color:#333'
        ].join(';');
        input.autocomplete = 'off';
        input.setAttribute('inputmode', 'numeric');

        var err = document.createElement('div');
        err.style.cssText = 'font-size:12px;color:#f87171;margin-top:8px;min-height:16px;font-weight:600;';

        var actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:8px;margin-top:14px;justify-content:flex-end;';

        var cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.textContent = 'Cancel';
        cancelBtn.style.cssText = icLight
            ? 'appearance:none;border:1px solid #d1d5db;background:#ffffff;color:#111827;border-radius:10px;padding:9px 14px;font-weight:700;font-size:13px;cursor:pointer;'
            : 'appearance:none;border:1px solid #333;background:#1a1a1a;color:#e5e5e5;border-radius:10px;padding:9px 14px;font-weight:700;font-size:13px;cursor:pointer;';

        var okBtn = document.createElement('button');
        okBtn.type = 'button';
        okBtn.textContent = 'Confirm';
        okBtn.style.cssText = 'appearance:none;border:none;background:#f5c518;color:#111;border-radius:10px;padding:9px 16px;font-weight:800;font-size:13px;cursor:pointer;';

        actions.appendChild(cancelBtn);
        actions.appendChild(okBtn);

        icOverlay.appendChild(label);
        icOverlay.appendChild(hint);
        icOverlay.appendChild(input);
        icOverlay.appendChild(err);
        icOverlay.appendChild(actions);
        document.body.appendChild(icBackdrop);
        document.body.appendChild(icOverlay);
        input.focus();

        function dismiss() {
            if (icOverlay && icOverlay.parentNode) { icOverlay.parentNode.removeChild(icOverlay); }
            if (icBackdrop && icBackdrop.parentNode) { icBackdrop.parentNode.removeChild(icBackdrop); }
            icOverlay = null;
            icBackdrop = null;
        }

        function submitPin() {
            var pin = input.value.trim();
            if (!pin) {
                err.textContent = 'PIN is required';
                input.focus();
                return;
            }
            var xhr = new XMLHttpRequest();
            xhr.open('POST', __ic_mode_url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            __ic_mode_active = res.active;
                            dismiss();
                            location.reload();
                        } else {
                            err.textContent = res.msg || 'Incorrect PIN';
                            input.value = '';
                            input.focus();
                        }
                    } catch(ex) { dismiss(); }
                }
            };
            xhr.send(JSON.stringify({ pin: pin }));
        }

        cancelBtn.addEventListener('click', dismiss);
        okBtn.addEventListener('click', submitPin);
        icBackdrop.addEventListener('click', dismiss);

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { dismiss(); return; }
            if (e.key === 'Enter') {
                e.preventDefault();
                submitPin();
            }
        });

        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') { dismiss(); document.removeEventListener('keydown', escHandler); }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === __ic_shortcut_key) {
            e.preventDefault();
            showIcPrompt();
        }
    });

    // Auto-refresh when IC mode timeout expires
    (function() {
        if (__ic_mode_active && __ic_activated_at && __ic_timeout_minutes) {
            var elapsed = (Math.floor(Date.now() / 1000) - __ic_activated_at) / 60;
            var remaining = __ic_timeout_minutes - elapsed;
            if (remaining > 0) {
                setTimeout(function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'IC Mode Expired',
                            text: 'Real values are now hidden. Page will reload.',
                            icon: 'info',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                    setTimeout(function() { location.reload(); }, 3000);
                }, remaining * 60 * 1000);
            }
        }
    })();
})();
</script>
@endauth

@if (file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/qpos-table-layout.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>

<!-- TODO -->
@if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script
        src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale')) . '.js';
@endphp
@if (file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}">
    </script>
@endif

@if (!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')

@if (Module::has('Essentials'))
    @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready(function() {
        var locale = "{{ session()->get('user.language', config('app.locale')) }}";
        var isRTL =
            @if (in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')))
                true;
            @else
                false;
            @endif

        $('#calendar').fullCalendar('option', {
            locale: locale,
            isRTL: isRTL
        });

        // Initialize popovers and close them when clicking outside
        $('[data-toggle="popover"]').popover();
        $(document).on('click', function (e) {
            $('[data-toggle="popover"]').each(function () {
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
                    $(this).popover('hide');
                }
            });
        });

        $('.dt-buttons.btn-group').find('a.btn').removeClass('btn-default');
        $('.dt-buttons.btn-group').find('a.btn').removeClass('btn');
        
        // $('.date_range').on('show.daterangepicker', function (ev, picker) {
        //     $(picker.container).insertAfter($(this));
        // });
   
    });
</script>

<script src="{{ asset('js/qpos-product-delete-fix.js') }}?v=2"></script>



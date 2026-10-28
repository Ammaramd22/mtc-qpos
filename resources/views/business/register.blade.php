@extends('layouts.auth2')
@section('title', __('lang_v1.register'))

@section('content')
    <style>
        /* Full-width register on dark auth shell */
        .auth-brand-panel { display: none !important; }
        .auth-form-panel { width: 100% !important; background: #050505 !important; }
        .auth-layout { justify-content: center; background: #050505; }
        .auth-form-content {
            align-items: flex-start !important;
            padding: 28px 16px 48px !important;
            width: 100%;
        }

        .register-card {
            --reg-bg: #111111;
            --reg-bg-2: #141414;
            --reg-border: #2a2a2a;
            --reg-text: #f5f5f5;
            --reg-muted: #a3a3a3;
            --reg-input: #1a1a1a;
            --reg-input-border: #333333;
            --reg-accent: #f5c518;
            --reg-accent-soft: rgba(245, 197, 24, 0.12);
            --reg-accent-hover: #e6b800;

            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            background: var(--reg-bg);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.55);
            border: 1px solid var(--reg-border);
            padding: 36px 40px;
            color: var(--reg-text);
        }
        @media (max-width: 576px) {
            .register-card { padding: 22px 14px; border-radius: 16px; }
        }

        .register-header {
            text-align: center;
            margin-bottom: 26px;
        }
        .register-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--reg-text);
            margin: 0 0 6px;
            letter-spacing: -.02em;
        }
        .register-header h1 span { color: var(--reg-accent); }
        .register-header p {
            font-size: 0.9rem;
            color: var(--reg-muted);
            margin: 0;
        }

        .register-card .form-group { margin-bottom: 16px; }
        .register-card label,
        .register-card .control-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--reg-muted) !important;
            margin-bottom: 6px;
        }
        .register-card .form-control,
        .register-card input[type="text"],
        .register-card input[type="password"],
        .register-card input[type="email"],
        .register-card input[type="file"],
        .register-card select,
        .register-card textarea {
            width: 100%;
            display: block;
            height: 44px;
            border: 1.5px solid var(--reg-input-border) !important;
            border-radius: 10px !important;
            padding: 0 12px;
            font-size: 0.9rem;
            color: var(--reg-text) !important;
            background: var(--reg-input) !important;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
            box-shadow: none !important;
        }
        .register-card .form-control:focus,
        .register-card input:focus,
        .register-card select:focus {
            border-color: var(--reg-accent) !important;
            background: #1f1f1f !important;
            box-shadow: 0 0 0 3px var(--reg-accent-soft) !important;
        }
        .register-card .form-control::placeholder,
        .register-card input::placeholder {
            color: #737373 !important;
        }

        .register-card .input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
        }
        .register-card .input-group-addon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            flex-shrink: 0;
            background: #1a1a1a !important;
            border: 1.5px solid var(--reg-input-border) !important;
            border-right: none !important;
            border-radius: 10px 0 0 10px !important;
            color: var(--reg-accent) !important;
            font-size: 0.85rem;
            padding: 0;
        }
        .register-card .input-group .form-control,
        .register-card .input-group select {
            border-radius: 0 10px 10px 0 !important;
            flex: 1;
            min-width: 0;
        }
        .register-card .input-group .select2-container {
            flex: 1;
            width: 100% !important;
        }
        .register-card .input-group .select2-container .select2-selection--single {
            border-radius: 0 10px 10px 0 !important;
            height: 44px !important;
        }

        /* File input — kill browser blue chrome clash */
        .register-card input[type="file"] {
            padding: 9px 12px !important;
            height: auto !important;
            color: var(--reg-muted) !important;
            cursor: pointer;
            line-height: 1.4;
        }
        .register-card input[type="file"]::file-selector-button {
            margin-right: 12px;
            border: 1px solid var(--reg-border);
            border-radius: 8px;
            padding: 6px 12px;
            background: #1f1f1f;
            color: var(--reg-text);
            font-weight: 600;
            font-size: 0.78rem;
            cursor: pointer;
        }
        .register-card input[type="file"]::-webkit-file-upload-button {
            margin-right: 12px;
            border: 1px solid var(--reg-border);
            border-radius: 8px;
            padding: 6px 12px;
            background: #1f1f1f;
            color: var(--reg-text);
            font-weight: 600;
            font-size: 0.78rem;
            cursor: pointer;
        }

        /* Sections — soft dark panels, not black boxes */
        .register-card fieldset {
            border: 1px solid var(--reg-border) !important;
            border-radius: 14px !important;
            padding: 18px 16px 8px !important;
            margin-bottom: 22px !important;
            background: var(--reg-bg-2) !important;
            color: var(--reg-text) !important;
        }
        .register-card legend,
        .register-card legend.text-black {
            font-size: 0.82rem !important;
            font-weight: 700 !important;
            color: var(--reg-accent) !important;
            padding: 0 10px !important;
            width: auto !important;
            border: none !important;
            margin-bottom: 0 !important;
            background: transparent !important;
        }
        .register-card h3 {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--reg-text) !important;
            margin: 0 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--reg-border);
        }

        .register-card .btn-primary {
            background: var(--reg-accent) !important;
            border: none !important;
            border-radius: 12px !important;
            height: 50px !important;
            font-size: 1rem !important;
            font-weight: 800 !important;
            color: #111 !important;
            box-shadow: 0 4px 16px rgba(245, 197, 24, 0.28) !important;
            transition: background .15s, transform .15s, box-shadow .15s !important;
            letter-spacing: 0.2px;
            width: 100%;
        }
        .register-card .btn-primary:hover {
            background: var(--reg-accent-hover) !important;
            color: #111 !important;
            box-shadow: 0 6px 22px rgba(245, 197, 24, 0.4) !important;
            transform: translateY(-1px) !important;
        }

        .register-card .input-check-box {
            width: 18px;
            height: 18px;
            accent-color: var(--reg-accent);
            vertical-align: middle;
            margin-right: 8px;
            cursor: pointer;
            flex-shrink: 0;
        }
        .register-card label[for="accept_tc"] {
            display: inline-flex !important;
            align-items: center;
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--reg-muted) !important;
            cursor: pointer;
            margin-bottom: 0;
        }
        .register-card .terms_condition {
            color: var(--reg-accent) !important;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
        }
        .register-card .terms_condition:hover { text-decoration: underline; }

        .form-row { display: flex; flex-wrap: wrap; gap: 0 14px; margin-bottom: 0; }
        .form-col { flex: 1 1 calc(50% - 7px); min-width: 220px; }
        .form-col.full { flex: 1 1 100%; }
        .form-row.three .form-col { flex: 1 1 calc(33.333% - 10px); min-width: 150px; }
        @media (max-width: 576px) {
            .form-col, .form-row.three .form-col { flex: 1 1 100%; min-width: 100%; }
        }

        /* Wizard steps — POS accent */
        .wizard > .steps > ul {
            display: flex !important;
            gap: 8px;
            justify-content: center;
            margin: 0 0 22px !important;
            padding: 0 !important;
            list-style: none !important;
        }
        .wizard > .steps > ul > li {
            flex: 1;
            max-width: 200px;
            float: none !important;
            width: auto !important;
        }
        .wizard > .steps a,
        .wizard > .steps a:hover,
        .wizard > .steps a:active {
            display: block !important;
            text-align: center;
            padding: 11px 8px !important;
            border-radius: 11px !important;
            background: #1a1a1a !important;
            color: var(--reg-muted) !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
            text-decoration: none !important;
            border: 1px solid var(--reg-border) !important;
            transition: all .2s;
            margin: 0 !important;
            line-height: 1.3 !important;
        }
        .wizard > .steps .current a,
        .wizard > .steps .current a:hover {
            background: var(--reg-accent) !important;
            color: #111 !important;
            border-color: var(--reg-accent) !important;
            box-shadow: 0 4px 14px rgba(245, 197, 24, 0.28);
        }
        .wizard > .steps .done a,
        .wizard > .steps .done a:hover {
            background: var(--reg-accent-soft) !important;
            color: var(--reg-accent) !important;
            border-color: rgba(245, 197, 24, 0.35) !important;
        }
        .wizard > .steps .number {
            font-weight: 800;
        }

        /* Kill jquery-steps black content box + left slide bug */
        .wizard {
            overflow: visible !important;
            width: 100% !important;
        }
        .wizard > .content,
        .register-card .wizard > .content,
        .wizard > .content > .body {
            background: transparent !important;
            border: none !important;
            border-radius: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            min-height: 0 !important;
            overflow: visible !important;
            position: relative !important;
            width: 100% !important;
            max-width: 100% !important;
            float: none !important;
            left: 0 !important;
            right: auto !important;
            top: auto !important;
            display: block !important;
        }
        .wizard > .content > .body {
            display: none !important;
            position: relative !important;
            float: none !important;
            width: 100% !important;
            height: auto !important;
            padding: 0 !important;
            left: 0 !important;
            opacity: 1 !important;
            transform: none !important;
        }
        .wizard > .content > .body.current {
            display: block !important;
        }
        .wizard > .content,
        .tabcontrol {
            overflow: visible !important;
        }
        /* jquery.steps absolute positioning overrides */
        .wizard > .content > .body[role="tabpanel"] {
            position: relative !important;
            left: 0 !important;
            width: 100% !important;
        }

        .wizard > .actions {
            margin-top: 18px !important;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .wizard > .actions > ul {
            display: flex !important;
            gap: 10px;
            list-style: none;
            margin: 0;
            padding: 0;
            float: none !important;
        }
        .wizard > .actions > ul > li { float: none !important; }
        .wizard > .actions a {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            padding: 11px 22px !important;
            border-radius: 11px !important;
            font-size: 0.88rem !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: all .15s;
            border: 1px solid transparent !important;
            line-height: 1.2 !important;
            margin: 0 !important;
        }
        .wizard > .actions .disabled a {
            background: #1a1a1a !important;
            color: #525252 !important;
            border-color: var(--reg-border) !important;
            pointer-events: none;
        }
        .wizard > .actions a[href="#next"],
        .wizard > .actions a[href="#finish"] {
            background: var(--reg-accent) !important;
            color: #111 !important;
            box-shadow: 0 4px 14px rgba(245, 197, 24, 0.28);
        }
        .wizard > .actions a[href="#previous"] {
            background: #1a1a1a !important;
            color: var(--reg-text) !important;
            border-color: var(--reg-border) !important;
        }

        /* Select2 dark */
        .register-card .select2-container--default .select2-selection--single {
            background: var(--reg-input) !important;
            border: 1.5px solid var(--reg-input-border) !important;
            border-radius: 10px !important;
            height: 44px !important;
        }
        .register-card .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--reg-text) !important;
            line-height: 42px !important;
            padding-left: 12px !important;
        }
        .register-card .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
        }
        .register-card .select2-container--default.select2-container--focus .select2-selection--single,
        .register-card .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--reg-accent) !important;
        }
        .select2-dropdown {
            background: #1a1a1a !important;
            border: 1px solid #333 !important;
            color: #f5f5f5 !important;
        }
        .select2-container--default .select2-results__option {
            color: #e5e5e5 !important;
            background: #1a1a1a !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected],
        .select2-container--default .select2-results__option--highlighted {
            background: rgba(245, 197, 24, 0.18) !important;
            color: #f5c518 !important;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field {
            background: #111 !important;
            border: 1px solid #333 !important;
            color: #f5f5f5 !important;
        }

        /* Datepicker / tooltips on dark */
        .register-card .text-danger,
        .register-card .help-block { color: #f87171 !important; }
        .register-card a { color: var(--reg-accent); }
    </style>

    <div class="register-card">
        <div class="register-header">
            <h1>Create your <span>{{ config('app.name', 'QPOS') }}</span> account</h1>
            <p>Register your business and get started in minutes</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" style="background:#3f1d1d;border:1px solid #7f1d1d;color:#fecaca;border-radius:12px;padding:12px 14px;margin-bottom:18px;">
                <strong>Please fix these fields:</strong>
                <ul style="margin:8px 0 0 18px;padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {!! Form::open([
            'url' => route('business.postRegister'),
            'method' => 'post',
            'id' => 'business_register_form',
            'files' => true,
        ]) !!}
        @include('business.partials.register_form', ['is_register' => true])
        {!! Form::hidden('package_id', $package_id) !!}
        {!! Form::close() !!}
    </div>
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('.change_lang').click(function() {
                window.location = "{{ route('business.getRegister') }}?lang=" + $(this).attr('value');
            });
        })
    </script>
@endsection

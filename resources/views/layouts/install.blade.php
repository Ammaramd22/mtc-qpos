<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - QPOS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --qpos-green: #00935F;
            --qpos-green-2: #00b377;
            --qpos-ink: #0f172a;
            --qpos-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; min-height: 100%; }
        body.install-body {
            font-family: var(--qpos-font);
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(0,147,95,.35), transparent 55%),
                radial-gradient(900px 500px at 100% 0%, rgba(0,179,119,.25), transparent 50%),
                linear-gradient(160deg, #0b1220 0%, #111827 45%, #0f172a 100%);
            color: #111827;
            min-height: 100vh;
        }
        .install-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .install-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: none;
            min-height: 100vh;
            background: #fff;
            border-radius: 0;
            box-shadow: none;
            overflow: visible;
        }
        .install-header {
            background: linear-gradient(135deg, var(--qpos-green) 0%, var(--qpos-green-2) 100%);
            padding: 28px 40px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }
        .install-header img {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
            object-fit: cover;
            background: #fff;
        }
        .install-header h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 2px 0;
        }
        .install-header p {
            font-size: 14px;
            opacity: .92;
            margin: 0;
        }
        .install-body-inner {
            padding: 28px 40px 40px;
            max-width: 1100px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }
        .install-nav {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }
        .install-nav-item {
            flex: 1;
            text-align: center;
            padding: 12px 8px;
            font-size: 13px;
            font-weight: 600;
            color: #9ca3af;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .install-nav-item.active {
            color: var(--qpos-green);
            border-bottom-color: var(--qpos-green);
        }
        .install-nav-item.completed {
            color: #059669;
        }
        .install-nav-item.completed::after { content: " \2713"; }
        .install-form label {
            font-weight: 600;
            font-size: 13px;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
        .install-form .form-control,
        .install-form input[type="text"],
        .install-form input[type="email"],
        .install-form input[type="password"],
        .install-form select,
        .install-form textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .install-form .form-control:focus,
        .install-form input:focus,
        .install-form select:focus {
            outline: none;
            border-color: var(--qpos-green);
            box-shadow: 0 0 0 3px rgba(0, 147, 95, 0.15);
        }
        .install-btn {
            background: linear-gradient(135deg, var(--qpos-green) 0%, var(--qpos-green-2) 100%);
            color: #fff !important;
            border: none;
            border-radius: 10px;
            padding: 13px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
        }
        .install-btn:hover { filter: brightness(1.05); }
        .install-btn:disabled { opacity: .6; cursor: not-allowed; }
        .install-btn-secondary {
            background: #f3f4f6;
            color: #374151 !important;
            border: 1px solid #d1d5db;
        }
        .install-alert {
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .install-alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .install-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .install-section-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin: 28px 0 14px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .install-welcome-icon {
            font-size: 48px;
            color: var(--qpos-green);
            margin-bottom: 16px;
        }
        .install-check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .install-check-pass {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .install-check-fail {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .install-footer {
            text-align: center;
            padding: 16px 32px;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            background: #fafafa;
        }
        .hide { display: none !important; }
        @media (max-width: 720px) {
            .install-header, .install-body-inner { padding-left: 18px; padding-right: 18px; }
            .install-body-inner [style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body class="install-body">
    <div class="install-shell">
        @yield('content')
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @yield('javascript')
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') - {{ config('app.name', 'QPOS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(150deg, #0f172a 0%, #1e3a5f 30%, #2563eb 70%, #7c3aed 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            color: #fff;
        }
        .deco-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            z-index: 0;
        }
        .deco-1 { width: 420px; height: 420px; bottom: -120px; right: -140px; }
        .deco-2 { width: 260px; height: 260px; top: -80px; right: 20px; }
        .deco-3 { width: 180px; height: 180px; bottom: 80px; left: -60px; background: rgba(255,255,255,.04); }

        .error-card {
            position: relative;
            z-index: 2;
            background: rgba(255,255,255,.97);
            border-radius: 28px;
            padding: 52px 48px;
            max-width: 520px;
            width: 92%;
            text-align: center;
            box-shadow: 0 32px 80px rgba(0,0,0,.35);
            border: 1px solid rgba(255,255,255,.3);
            animation: slideUp .6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-code {
            font-size: clamp(4rem, 10vw, 6.5rem);
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -2px;
        }
        .error-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .error-desc {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .error-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(37,99,235,.35);
            transition: box-shadow .15s, transform .15s;
        }
        .btn-primary:hover {
            box-shadow: 0 6px 24px rgba(37,99,235,.5);
            transform: translateY(-1px);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 12px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #e2e8f0;
            margin-left: 10px;
            transition: background .15s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .error-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .logo-mark {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
            color: #0f172a;
            font-size: 1.1rem;
            font-weight: 800;
            text-decoration: none;
        }
        .logo-mark svg { width: 28px; height: 28px; color: #2563eb; }
        @media (max-width: 480px) {
            .error-card { padding: 36px 24px; }
            .btn-secondary { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="deco-circle deco-1"></div>
    <div class="deco-circle deco-2"></div>
    <div class="deco-circle deco-3"></div>

    <div class="error-card">
        <a href="{{ url('/') }}" class="logo-mark">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            {{ config('app.name', 'QPOS') }}
        </a>

        @yield('content')
    </div>
</body>
</html>

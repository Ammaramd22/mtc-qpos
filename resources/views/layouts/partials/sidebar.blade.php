<!-- Left side column. contains the logo and sidebar -->
<aside class="side-bar qpos-sidebar tw-relative tw-hidden tw-h-full tw-w-64 xl:tw-w-64 lg:tw-flex lg:tw-flex-col tw-shrink-0">

    <a href="{{ route('home') }}" class="sb-brand">
        <div class="sb-brand-mark">
            <img src="{{ asset('img/qpos-icon.png') }}" alt="QPOS" class="sb-brand-logo">
        </div>
        <div class="sb-brand-text">
            <span class="sb-brand-name">QPOS</span>
            <span class="sb-brand-status"><span class="sb-online-dot"></span> Online</span>
        </div>
    </a>

    <style>
    /* ── QPOS sidebar — charcoal + yellow (not Ultimate blue) ── */
    .qpos-sidebar.side-bar {
        --sb-bg: #0a0a0a;
        --sb-panel: #111111;
        --sb-border: #222222;
        --sb-text: #d4d4d4;
        --sb-muted: #737373;
        --sb-accent: #f5c518;
        --sb-accent-soft: rgba(245, 197, 24, 0.12);
        --sb-accent-strong: rgba(245, 197, 24, 0.22);
        --sb-search-bg: #141414;
        --sb-search-border: #262626;
        --sb-search-text: #f5f5f5;
        width: 16.5rem !important;
        background:
            radial-gradient(420px 180px at 0% 0%, rgba(245, 197, 24, 0.07), transparent 70%),
            linear-gradient(180deg, #0c0c0c 0%, #090909 100%) !important;
        border-right: 1px solid var(--sb-border) !important;
        color: var(--sb-text) !important;
    }
    .qpos-sidebar .main-sidebar,
    .qpos-sidebar section,
    .qpos-sidebar .sidebar,
    .qpos-sidebar .treeview-menu,
    .qpos-sidebar .sidebar-menu,
    .qpos-sidebar .slimScrollDiv {
        background: transparent !important;
    }
    .qpos-sidebar::before,
    .qpos-sidebar::after {
        content: none !important;
    }

    /* Brand lockup */
    .qpos-sidebar .sb-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px 12px;
        min-height: 64px;
        border-bottom: 1px solid var(--sb-border);
        text-decoration: none !important;
        flex-shrink: 0;
        background: transparent;
        position: relative;
    }
    .qpos-sidebar .sb-brand::after {
        content: '';
        position: absolute;
        left: 16px;
        right: 16px;
        bottom: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(245,197,24,.35), transparent);
    }
    .qpos-sidebar .sb-brand:hover { background: rgba(255,255,255,0.02); }
    .qpos-sidebar .sb-brand-mark {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        background: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
        box-shadow: 0 6px 16px rgba(245, 197, 24, 0.22), inset 0 0 0 1px rgba(245, 197, 24, 0.35);
    }
    .qpos-sidebar .sb-brand-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        padding: 3px;
    }
    .qpos-sidebar .sb-brand-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        gap: 2px;
    }
    .qpos-sidebar .sb-brand-name {
        font-size: 1rem;
        font-weight: 800;
        color: #fafafa !important;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.15;
    }
    .qpos-sidebar .sb-brand-status {
        font-size: 0.68rem;
        color: var(--sb-muted) !important;
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .qpos-sidebar .sb-online-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #22c55e;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
        display: inline-block;
        flex-shrink: 0;
    }

    /* Search */
    .qpos-sidebar .sb-search-wrap {
        padding: 12px 14px 8px;
        flex-shrink: 0;
    }
    .qpos-sidebar .sb-search {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 12px;
        background: var(--sb-search-bg);
        border: 1px solid var(--sb-search-border);
        transition: border-color .15s, box-shadow .15s;
    }
    .qpos-sidebar .sb-search:focus-within {
        border-color: rgba(245, 197, 24, 0.55);
        box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.12);
    }
    .qpos-sidebar .sb-search svg {
        color: var(--sb-muted);
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }
    .qpos-sidebar #sidebar-search {
        flex: 1;
        min-width: 0;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        color: var(--sb-search-text) !important;
        font-size: 0.8rem !important;
        font-weight: 500;
        padding: 0 !important;
        box-shadow: none !important;
    }
    .qpos-sidebar #sidebar-search::placeholder {
        color: var(--sb-muted) !important;
    }
    #sidebar-search-clear {
        color: #737373;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        display: none;
    }
    #sidebar-search-clear:not(.tw-hidden) {
        display: inline-flex !important;
    }

    /* Nav scroll area */
    .qpos-sidebar #side-bar {
        flex: 1;
        overflow-y: auto;
        padding: 6px 10px 18px !important;
        border-right: none !important;
        scrollbar-width: thin;
        scrollbar-color: #333 transparent;
    }
    .qpos-sidebar #side-bar::-webkit-scrollbar { width: 4px; }
    .qpos-sidebar #side-bar::-webkit-scrollbar-thumb {
        background: #333;
        border-radius: 99px;
    }

    /* Top-level links — kill blue theme classes */
    .qpos-sidebar #side-bar > a,
    .qpos-sidebar #side-bar > div > a.drop_down,
    .qpos-sidebar #side-bar a.theme-sidebar-hover {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        gap: 11px !important;
        padding: 9px 12px 9px 14px !important;
        margin: 2px 0 !important;
        border-radius: 11px !important;
        color: #c4c4c4 !important;
        font-size: 0.84rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em;
        background: transparent !important;
        border: 1px solid transparent !important;
        transition: background .15s, color .15s, border-color .15s, transform .15s !important;
        text-decoration: none !important;
    }
    .qpos-sidebar #side-bar > a::before,
    .qpos-sidebar #side-bar > div > a.drop_down::before {
        content: '';
        position: absolute;
        left: 3px;
        top: 50%;
        transform: translateY(-50%) scaleY(0);
        width: 3px;
        height: 55%;
        border-radius: 99px;
        background: var(--sb-accent);
        transition: transform .18s ease;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-hover:hover,
    .qpos-sidebar #side-bar a.theme-sidebar-hover:focus {
        background: rgba(255, 255, 255, 0.04) !important;
        color: #fafafa !important;
        border-color: #262626 !important;
        outline: none !important;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-hover:hover svg,
    .qpos-sidebar #side-bar a.theme-sidebar-hover:hover i {
        color: var(--sb-accent) !important;
    }

    /* Active — yellow rail, not blue pill */
    .qpos-sidebar #side-bar a.theme-sidebar-active,
    .qpos-sidebar #side-bar a.theme-sidebar-active:hover,
    .qpos-sidebar #side-bar a.theme-sidebar-active:focus {
        background: var(--sb-accent-soft) !important;
        color: var(--sb-accent) !important;
        border-color: rgba(245, 197, 24, 0.28) !important;
        box-shadow: inset 0 0 0 1px rgba(245, 197, 24, 0.06);
        outline: none !important;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-active::before,
    .qpos-sidebar #side-bar > div > a.drop_down.theme-sidebar-active::before {
        transform: translateY(-50%) scaleY(1);
    }
    .qpos-sidebar #side-bar a.theme-sidebar-active svg,
    .qpos-sidebar #side-bar a.theme-sidebar-active i,
    .qpos-sidebar #side-bar a.theme-sidebar-active .svg {
        color: var(--sb-accent) !important;
    }

    /* Icons idle = muted stone, active/hover = yellow — boxed like dashboard stats */
    .qpos-sidebar #side-bar .sb-icon-box {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.04);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.14);
        flex-shrink: 0;
        transition: background .15s, box-shadow .15s, color .15s;
    }
    .qpos-sidebar #side-bar a svg,
    .qpos-sidebar #side-bar a i {
        width: 16px !important;
        min-width: 16px !important;
        height: 16px !important;
        color: #8a8a8a !important;
        opacity: 1 !important;
        flex-shrink: 0;
        transition: color .15s;
        margin: 0 !important;
        font-size: 14px !important;
        line-height: 1 !important;
        display: block;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-active .sb-icon-box,
    .qpos-sidebar #side-bar a:hover .sb-icon-box {
        background: var(--sb-accent-soft);
        box-shadow: inset 0 0 0 1px rgba(245, 197, 24, 0.35);
    }
    .qpos-sidebar #side-bar a .svg {
        width: 15px !important;
        height: 15px !important;
        margin-left: auto !important;
        color: #555 !important;
        flex-shrink: 0;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-active .svg {
        color: var(--sb-accent) !important;
    }

    /* Child tree */
    .qpos-sidebar #side-bar .chiled {
        margin: 2px 0 8px !important;
        padding-left: 2.65rem !important;
        position: relative;
    }
    .qpos-sidebar #side-bar .chiled > .tw-absolute {
        left: 1.35rem !important;
        width: 2px !important;
        background: linear-gradient(180deg, rgba(245,197,24,.45), rgba(245,197,24,.08)) !important;
        border-radius: 99px;
    }
    .qpos-sidebar #side-bar .chiled a {
        display: block !important;
        padding: 6px 10px !important;
        margin: 1px 0 !important;
        border-radius: 8px !important;
        color: #a3a3a3 !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        background: transparent !important;
        transition: color .15s, background .15s;
    }
    .qpos-sidebar #side-bar .chiled a:hover,
    .qpos-sidebar #side-bar a.theme-sidebar-child-hover:hover {
        color: #f5f5f5 !important;
        background: rgba(255,255,255,0.03) !important;
    }
    .qpos-sidebar #side-bar a.theme-sidebar-child-active,
    .qpos-sidebar #side-bar a.theme-sidebar-child-active:hover {
        color: var(--sb-accent) !important;
        background: var(--sb-accent-soft) !important;
        font-weight: 700 !important;
    }

    /* Section headers — gold like screenshot */
    .qpos-sidebar #side-bar .tw-text-xs.tw-uppercase,
    .qpos-sidebar .sidebar-menu .header {
        color: var(--sb-accent) !important;
        letter-spacing: 0.08em !important;
        font-size: 0.65rem !important;
        font-weight: 800 !important;
        padding: 14px 12px 6px !important;
        margin: 0 !important;
        background: transparent !important;
    }

    .qpos-sidebar #sidebar-no-results {
        color: #737373 !important;
        border: none !important;
    }

    /* Override global blue theme vars inside sidebar only */
    .qpos-sidebar #side-bar a.theme-sidebar-active svg,
    .qpos-sidebar #side-bar a.theme-sidebar-active i,
    .qpos-sidebar #side-bar a:hover svg,
    .qpos-sidebar #side-bar a:hover i {
        color: var(--sb-accent) !important;
    }
    </style>

    <!-- Sidebar Search -->
    <div class="sb-search-wrap">
        <div class="sb-search">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/>
                <path d="M21 21l-6 -6"/>
            </svg>
            <input type="text" id="sidebar-search" placeholder="Search menu..." autocomplete="off" />
            <button id="sidebar-search-clear" type="button" aria-label="Clear search" class="tw-hidden">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6l-12 12"/>
                    <path d="M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Sidebar Menu -->
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom') !!}

    <p id="sidebar-no-results" class="tw-hidden tw-px-4 tw-py-3 tw-text-xs tw-text-center">
        No menu items found.
    </p>
</aside>

@php
    $filterId = 'pf_' . uniqid();
    $isOpen   = !empty($open); // pages can pass open => true
@endphp

@once
<style>
    /* grid-trick for smooth height animation — can't do this with Tailwind utilities */
    .pf-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .3s cubic-bezier(.4,0,.2,1); }
    .pf-body--open { grid-template-rows: 1fr; }
    .pf-inner { overflow: hidden; }
    /* Filter card — dark admin theme */
    .pf-card {
        background: #111111;
        border: 1px solid rgba(148, 163, 184, .24);
        box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
    }
    .pf-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, .3);
    }
    .pf-btn { position: relative; }
    .pf-btn::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: var(--theme-700, #3b82f6);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .25s cubic-bezier(.4,0,.2,1);
    }
    .pf-open .pf-btn::before { transform: scaleX(1); }
    .pf-open .pf-btn { border-bottom-color: rgba(148, 163, 184, .22) !important; }
    .pf-btn:hover,
    .pf-btn:focus {
        background: #141414 !important;
        outline: none;
    }
    .pf-title { color: #f8fafc; }
    .pf-inner .form-group label,
    .pf-inner label { color: #cbd5e1 !important; }
</style>
@endonce

@once
<script>
function pfToggle(id) {
    var card    = document.getElementById(id);
    var body    = document.getElementById(id + '_body');
    var chevron = card.querySelector('.pf-chevron');
    var btn     = card.querySelector('.pf-btn');
    var open    = card.classList.contains('pf-open');

    card.classList.toggle('pf-open', !open);
    body.classList.toggle('pf-body--open', !open);
    chevron.classList.toggle('tw-rotate-180', !open);
    btn.setAttribute('aria-expanded', open ? 'false' : 'true');
}
</script>
@endonce

<div id="{{ $filterId }}"
     class="pf-card tw-rounded-xl tw-mb-4 tw-overflow-hidden tw-transition-shadow tw-duration-200 {{ $isOpen ? 'pf-open' : '' }}">

    {{-- Header --}}
    <button type="button"
            id="{{ $filterId }}_btn"
            class="pf-btn tw-w-full tw-flex tw-items-center tw-justify-between tw-px-4 tw-py-3 tw-border-0 tw-border-b tw-border-transparent tw-bg-transparent tw-cursor-pointer tw-transition-colors tw-duration-150"
            onclick="pfToggle('{{ $filterId }}')"
            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
            aria-controls="{{ $filterId }}_body">

        <span class="tw-flex tw-items-center tw-gap-2.5">
            {{-- Icon badge --}}
            <span class="tw-flex tw-items-center tw-justify-center tw-w-7 tw-h-7 tw-rounded-lg tw-shrink-0 tw-text-xs tw-transition-colors tw-duration-200"
                  style="background: color-mix(in srgb, var(--theme-700, #004EEB) 10%, transparent); color: var(--theme-700, #004EEB);">
                @if (!empty($icon))
                    {!! $icon !!}
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                @endif
            </span>
            {{-- Title --}}
            <span class="pf-title tw-text-sm tw-font-semibold tw-tracking-tight">
                {{ $title ?? __('report.filters') }}
            </span>
        </span>

        {{-- Chevron --}}
        <svg class="pf-chevron tw-w-4 tw-h-4 tw-shrink-0 tw-transition-all tw-duration-300 {{ $isOpen ? 'tw-rotate-180' : '' }}"
             style="color: var(--theme-700, #004EEB);"
             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"/>
        </svg>
    </button>

    {{-- Collapsible body --}}
    <div id="{{ $filterId }}_body" class="pf-body {{ $isOpen ? 'pf-body--open' : '' }}">
        <div class="pf-inner">
            <div class="tw-px-4 tw-pt-3 tw-pb-4">
                {{ $slot }}
            </div>
        </div>
    </div>

</div>

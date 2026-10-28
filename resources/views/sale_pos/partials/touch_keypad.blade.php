{{-- On-screen numeric keypad for Touch POS payment --}}
<div class="vspos-touch-keypad" id="vspos_touch_keypad" aria-label="Numeric keypad">
    <div class="vspos-touch-keypad-grid">
        @foreach (['1','2','3','4','5','6','7','8','9','.','0','⌫'] as $key)
            <button type="button"
                class="vspos-touch-key {{ $key === '⌫' ? 'vspos-touch-key--back' : ($key === '.' ? 'vspos-touch-key--dot' : '') }}"
                data-key="{{ $key === '⌫' ? 'back' : $key }}">
                {{ $key }}
            </button>
        @endforeach
    </div>
    <div class="vspos-touch-keypad-row">
        <button type="button" class="vspos-touch-key vspos-touch-key--clear" data-key="clear">C</button>
        <button type="button" class="vspos-touch-key vspos-touch-key--exact" data-key="exact">Exact</button>
    </div>
</div>

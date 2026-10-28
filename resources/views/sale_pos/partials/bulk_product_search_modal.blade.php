{{-- POS bulk product search — F8 — search by name/SKU, show cost + stock --}}
@php
    $can_view_cost = auth()->user()->can('view_purchase_price');
    $bulk_is_pop = !empty($is_pop) || request()->is('purchase-pos*');
    $bulk_cost_label = $bulk_is_pop ? 'Last buy' : 'Cost';
    $bulk_search_key_label = !empty($shortcuts['pos']['bulk_product_search'])
        ? strtoupper($shortcuts['pos']['bulk_product_search'])
        : 'F8';
    $bulk_cols = $can_view_cost
        ? 'minmax(0, 2.2fr) minmax(72px, 1fr) minmax(88px, 1fr) minmax(88px, 1fr) minmax(88px, 1.1fr) 72px'
        : 'minmax(0, 2.4fr) minmax(72px, 1.1fr) minmax(88px, 1.1fr) minmax(88px, 1.2fr) 72px';
@endphp
<div class="modal fade vspos-pos-modal" id="vspos_bulk_search_modal" tabindex="-1" role="dialog" aria-labelledby="vspos_bulk_search_title" data-can-view-cost="{{ $can_view_cost ? 1 : 0 }}" data-is-pop="{{ $bulk_is_pop ? 1 : 0 }}">
    <div class="modal-dialog modal-lg vspos-bulk-dialog" role="document">
        <div class="modal-content vspos-bulk-content">
            <div class="modal-header vspos-bulk-header">
                <div class="vspos-bulk-header-text">
                    <h4 class="modal-title" id="vspos_bulk_search_title">
                        Product search
                        <span class="keycap">{{ $bulk_search_key_label }}</span>
                    </h4>
                    <p class="vspos-bulk-sub">Search by name, tyre size, brand, note, barcode / SKU · cost &amp; stock</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body vspos-bulk-body-wrap">
                <div class="vspos-bulk-search-bar">
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="vspos_bulk_search_input" class="form-control"
                            placeholder="Search product name, barcode or SKU..."
                            autocomplete="off"
                            spellcheck="false">
                    </div>
                </div>

                {{-- CSS grid (not &lt;table&gt;) so global modal table CSS cannot collapse columns --}}
                <div class="vspos-bulk-panel" style="--vspos-bulk-cols: {{ $bulk_cols }};">
                    <div class="vspos-bulk-head" role="row">
                        <div class="vspos-bulk-cell" role="columnheader">Product</div>
                        <div class="vspos-bulk-cell" role="columnheader">Code</div>
                        @if($can_view_cost)
                        <div class="vspos-bulk-cell is-num" role="columnheader">{{ $bulk_cost_label }}</div>
                        @endif
                        <div class="vspos-bulk-cell is-num" role="columnheader">{{ $bulk_is_pop ? 'MRP' : 'Rate' }}</div>
                        <div class="vspos-bulk-cell is-num" role="columnheader">Qty avail.</div>
                        <div class="vspos-bulk-cell is-action" role="columnheader"></div>
                    </div>
                    <div id="vspos_bulk_search_body" class="vspos-bulk-results" role="listbox">
                        <div class="vspos-bulk-empty">Type to search products…</div>
                    </div>
                </div>
                <p class="vspos-bulk-hint">
                    ↑↓ / Tab navigate · Enter or double-click → Qty &amp; Rate · Esc close
                </p>
            </div>
        </div>
    </div>
</div>

<style>
#vspos_bulk_search_modal .vspos-bulk-dialog {
    width: min(920px, 96vw);
    max-width: 96vw;
    margin-top: 4vh;
}
#vspos_bulk_search_modal .vspos-bulk-content {
    background: #111;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    overflow: hidden;
}
#vspos_bulk_search_modal .vspos-bulk-header {
    border-bottom: 1px solid #222;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
#vspos_bulk_search_modal .vspos-bulk-header-text { flex: 1; min-width: 0; }
#vspos_bulk_search_modal .vspos-bulk-header .modal-title {
    margin: 0;
    color: #f5f5f5;
    font-weight: 800;
    font-size: 1rem;
}
#vspos_bulk_search_modal .vspos-bulk-header .keycap {
    margin-left: 8px;
    vertical-align: middle;
}
#vspos_bulk_search_modal .vspos-bulk-sub {
    margin: 4px 0 0;
    color: #737373;
    font-size: 12px;
}
#vspos_bulk_search_modal .vspos-bulk-header .close {
    color: #a3a3a3;
    opacity: 1;
    font-size: 24px;
}
#vspos_bulk_search_modal .vspos-bulk-body-wrap { padding: 14px 18px 8px; }
#vspos_bulk_search_modal .vspos-bulk-search-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}
#vspos_bulk_search_modal .vspos-bulk-search-bar .input-group { flex: 1; }
#vspos_bulk_search_modal .vspos-bulk-search-bar .input-group-addon {
    background: #1a1a1a;
    border-color: #333;
    color: #f5c518;
}
#vspos_bulk_search_input {
    color: #fafafa !important;
    -webkit-text-fill-color: #fafafa !important;
    caret-color: #f5c518 !important;
    background: #0a0a0a !important;
    border-color: #333 !important;
    height: 42px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
}
#vspos_bulk_search_input:focus {
    border-color: #f5c518 !important;
    box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.18) !important;
}

#vspos_bulk_search_modal .vspos-bulk-panel {
    border: 1px solid #222;
    border-radius: 12px;
    overflow: hidden;
    background: #0f0f0f;
}
#vspos_bulk_search_modal .vspos-bulk-head,
#vspos_bulk_search_modal .vspos-bulk-row {
    display: grid !important;
    grid-template-columns: var(--vspos-bulk-cols);
    align-items: center;
    column-gap: 8px;
    width: 100%;
    box-sizing: border-box;
    min-width: 640px;
}
#vspos_bulk_search_modal .vspos-bulk-head {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #141414;
    border-bottom: 1px solid #2a2a2a;
    padding: 10px 12px;
}
#vspos_bulk_search_modal .vspos-bulk-head .vspos-bulk-cell {
    color: #f5c518;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#vspos_bulk_search_modal .vspos-bulk-results {
    max-height: min(58vh, 520px);
    overflow: auto;
    -webkit-overflow-scrolling: touch;
}
#vspos_bulk_search_modal .vspos-bulk-row {
    cursor: pointer;
    padding: 10px 12px;
    border-bottom: 1px solid #1f1f1f;
    background: #0f0f0f;
    transition: background .12s;
}
#vspos_bulk_search_modal .vspos-bulk-row:nth-child(even) { background: #121212; }
#vspos_bulk_search_modal .vspos-bulk-row.is-active,
#vspos_bulk_search_modal .vspos-bulk-row:hover,
#vspos_bulk_search_modal .vspos-bulk-row:focus {
    background: rgba(245, 197, 24, 0.1) !important;
    outline: none;
}
#vspos_bulk_search_modal .vspos-bulk-cell {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #e5e5e5;
    font-size: 13px;
}
#vspos_bulk_search_modal .vspos-bulk-cell.is-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}
#vspos_bulk_search_modal .vspos-bulk-cell.is-action {
    text-align: right;
    overflow: visible;
}
#vspos_bulk_search_modal .vspos-bulk-name {
    color: #fafafa;
    font-weight: 700;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
}
#vspos_bulk_search_modal .vspos-bulk-code {
    color: #a3a3a3;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 12px;
}
#vspos_bulk_search_modal .vspos-bulk-add {
    background: #f5c518 !important;
    color: #111 !important;
    border: none !important;
    border-radius: 8px !important;
    font-weight: 800 !important;
    font-size: 12px !important;
    padding: 6px 12px !important;
    min-width: 56px;
}
#vspos_bulk_search_modal .qty-low { color: #f87171 !important; font-weight: 700; }
#vspos_bulk_search_modal .qty-ok { color: #4ade80 !important; font-weight: 700; }
#vspos_bulk_search_modal .vspos-bulk-empty {
    padding: 28px;
    color: #737373;
    text-align: center;
    font-size: 13px;
}
.vspos-bulk-hint {
    margin: 10px 0 4px;
    color: #525252;
    font-size: 11px;
}

.vspos-entry-search-btn {
    height: 40px;
    min-width: 64px;
    border-radius: 10px !important;
    background: rgba(245, 197, 24, 0.14) !important;
    border: 1px solid rgba(245, 197, 24, 0.4) !important;
    color: #f5c518 !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 0 10px !important;
    font-weight: 800;
    font-size: 11px;
    flex-shrink: 0;
    box-shadow: none !important;
}
.vspos-entry-search-btn .keycap {
    background: #111;
    color: #f5c518;
    border: 1px solid rgba(245,197,24,.35);
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 10px;
    font-weight: 800;
    line-height: 1.2;
}
body.lockscreen .vspos-entry-bar .vspos-entry-search-btn {
    background: rgba(245, 197, 24, 0.14) !important;
    border: 1px solid rgba(245, 197, 24, 0.45) !important;
    color: #f5c518 !important;
}
</style>

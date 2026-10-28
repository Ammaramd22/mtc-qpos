{{-- Purchase bulk product search — Enter on empty search / Bulk button --}}
@php
    $can_view_cost = auth()->user()->can('view_purchase_price');
    $bulk_cols = $can_view_cost
        ? 'minmax(0, 2.2fr) minmax(72px, 1fr) minmax(88px, 1fr) minmax(88px, 1fr) minmax(88px, 1.1fr) 72px'
        : 'minmax(0, 2.4fr) minmax(72px, 1.1fr) minmax(88px, 1.1fr) minmax(88px, 1.2fr) 72px';
@endphp
<div class="modal fade" id="purchase_bulk_search_modal" tabindex="-1" role="dialog"
     aria-labelledby="purchase_bulk_search_title"
     data-can-view-cost="{{ $can_view_cost ? 1 : 0 }}">
    <div class="modal-dialog modal-lg purchase-bulk-dialog" role="document">
        <div class="modal-content purchase-bulk-content">
            <div class="modal-header purchase-bulk-header">
                <div class="purchase-bulk-header-text">
                    <h4 class="modal-title" id="purchase_bulk_search_title">
                        Bulk product search
                        <span class="keycap">Enter</span>
                    </h4>
                    <p class="purchase-bulk-sub">Search by name or barcode / SKU · pick product · enter qty</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body purchase-bulk-body-wrap">
                <div class="purchase-bulk-search-bar">
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-search"></i></span>
                        <input type="text" id="purchase_bulk_search_input" class="form-control"
                               placeholder="Type product name or code…"
                               autocomplete="off" spellcheck="false">
                    </div>
                </div>
                <div class="purchase-bulk-panel" style="--purchase-bulk-cols: {{ $bulk_cols }};">
                    <div class="purchase-bulk-head" role="row">
                        <div class="purchase-bulk-cell" role="columnheader">Product</div>
                        <div class="purchase-bulk-cell" role="columnheader">Code</div>
                        @if($can_view_cost)
                        <div class="purchase-bulk-cell is-num" role="columnheader">Cost</div>
                        @endif
                        <div class="purchase-bulk-cell is-num" role="columnheader">MRP</div>
                        <div class="purchase-bulk-cell is-num" role="columnheader">Stock</div>
                        <div class="purchase-bulk-cell is-action" role="columnheader"></div>
                    </div>
                    <div id="purchase_bulk_search_body" class="purchase-bulk-results" role="listbox">
                        <div class="purchase-bulk-empty">Type to search products…</div>
                    </div>
                </div>
                <p class="purchase-bulk-hint">↑↓ navigate · Enter → ask quantity · Esc close</p>
            </div>
        </div>
    </div>
</div>

{{-- Quantity popup after barcode / bulk select --}}
<div class="modal fade" id="purchase_qty_modal" tabindex="-1" role="dialog" aria-labelledby="purchase_qty_title">
    <div class="modal-dialog purchase-qty-dialog" role="document">
        <div class="modal-content purchase-qty-content">
            <div class="modal-header purchase-qty-header">
                <h4 class="modal-title" id="purchase_qty_title">Add to purchase</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="purchase-qty-product" id="purchase_qty_product">—</p>
                <p class="purchase-qty-meta">
                    <span id="purchase_qty_sku"></span>
                </p>
                <div class="purchase-qty-details" id="purchase_qty_details">
                    <div class="purchase-qty-detail-row">
                        <span class="pq-label">Brand</span>
                        <span class="pq-value" id="purchase_qty_brand">—</span>
                    </div>
                    <div class="purchase-qty-detail-row">
                        <span class="pq-label">Category</span>
                        <span class="pq-value" id="purchase_qty_category">—</span>
                    </div>
                    <div class="purchase-qty-detail-row is-desc">
                        <span class="pq-label">Description</span>
                        <span class="pq-value" id="purchase_qty_description">—</span>
                    </div>
                </div>
                <div class="row purchase-qty-fields">
                    <div class="col-xs-4">
                        <label for="purchase_qty_input" class="control-label">Qty</label>
                        <div class="input-group">
                            <input type="text" id="purchase_qty_input" class="form-control input_number"
                                   value="1" inputmode="decimal" autocomplete="off">
                            <span class="input-group-addon" id="purchase_qty_unit"></span>
                        </div>
                    </div>
                    <div class="col-xs-4">
                        <label for="purchase_qty_cost" class="control-label">Cost price</label>
                        <input type="text" id="purchase_qty_cost" class="form-control input_number"
                               value="0" inputmode="decimal" autocomplete="off">
                    </div>
                    <div class="col-xs-4">
                        <label for="purchase_qty_sell" class="control-label">Selling price</label>
                        <input type="text" id="purchase_qty_sell" class="form-control input_number"
                               value="0" inputmode="decimal" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="modal-footer purchase-qty-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="purchase_qty_confirm">
                    Add to purchase
                </button>
            </div>
        </div>
    </div>
</div>

<style>
#purchase_bulk_search_modal .purchase-bulk-dialog {
    width: min(920px, 96vw);
    max-width: 96vw;
    margin-top: 4vh;
}
#purchase_bulk_search_modal .purchase-bulk-content {
    background: #141414;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    color: #f5f5f5;
}
#purchase_bulk_search_modal .purchase-bulk-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    border-bottom: 1px solid #2a2a2a;
    padding: 14px 18px;
}
#purchase_bulk_search_modal .purchase-bulk-header .modal-title {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}
#purchase_bulk_search_modal .purchase-bulk-header .keycap {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 7px;
    border-radius: 4px;
    background: #262626;
    border: 1px solid #404040;
    font-size: 11px;
    font-weight: 600;
    vertical-align: middle;
}
#purchase_bulk_search_modal .purchase-bulk-sub {
    margin: 4px 0 0;
    color: #a3a3a3;
    font-size: 12px;
}
#purchase_bulk_search_modal .purchase-bulk-header .close {
    color: #fff;
    opacity: .7;
    text-shadow: none;
}
#purchase_bulk_search_modal .purchase-bulk-body-wrap { padding: 14px 18px 10px; }
#purchase_bulk_search_modal .purchase-bulk-search-bar { margin-bottom: 12px; }
#purchase_bulk_search_modal .purchase-bulk-search-bar .input-group-addon {
    background: #1f1f1f;
    border-color: #333;
    color: #a3a3a3;
}
#purchase_bulk_search_input {
    background: #1a1a1a !important;
    border-color: #333 !important;
    color: #fff !important;
    height: 42px;
    font-size: 15px;
}
#purchase_bulk_search_modal .purchase-bulk-panel {
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    overflow: hidden;
    background: #0f0f0f;
}
#purchase_bulk_search_modal .purchase-bulk-head,
#purchase_bulk_search_modal .purchase-bulk-row {
    display: grid;
    grid-template-columns: var(--purchase-bulk-cols);
    gap: 0;
    align-items: center;
}
#purchase_bulk_search_modal .purchase-bulk-head {
    background: #1a1a1a;
    border-bottom: 1px solid #2a2a2a;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #a3a3a3;
}
#purchase_bulk_search_modal .purchase-bulk-results {
    max-height: min(48vh, 420px);
    overflow-y: auto;
}
#purchase_bulk_search_modal .purchase-bulk-row {
    border-bottom: 1px solid #1f1f1f;
    cursor: pointer;
}
#purchase_bulk_search_modal .purchase-bulk-row:nth-child(even) { background: #121212; }
#purchase_bulk_search_modal .purchase-bulk-row.is-active,
#purchase_bulk_search_modal .purchase-bulk-row:hover {
    background: #1e3a5f !important;
}
#purchase_bulk_search_modal .purchase-bulk-cell {
    padding: 9px 10px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
}
#purchase_bulk_search_modal .purchase-bulk-cell.is-num { text-align: right; font-variant-numeric: tabular-nums; }
#purchase_bulk_search_modal .purchase-bulk-cell.is-action { text-align: center; }
#purchase_bulk_search_modal .purchase-bulk-name { font-weight: 600; }
#purchase_bulk_search_modal .purchase-bulk-code { color: #a3a3a3; font-size: 12px; }
#purchase_bulk_search_modal .purchase-bulk-add {
    padding: 3px 10px;
    background: #2563eb;
    border: none;
    color: #fff;
    border-radius: 5px;
    font-size: 12px;
}
#purchase_bulk_search_modal .qty-low { color: #f87171; font-weight: 700; }
#purchase_bulk_search_modal .qty-ok { color: #4ade80; font-weight: 700; }
#purchase_bulk_search_modal .purchase-bulk-empty {
    padding: 28px 16px;
    text-align: center;
    color: #737373;
}
#purchase_bulk_search_modal .purchase-bulk-hint {
    margin: 10px 0 0;
    font-size: 12px;
    color: #737373;
}

#purchase_qty_modal .purchase-qty-dialog {
    width: min(520px, 94vw);
    margin-top: 12vh;
}
#purchase_qty_modal .purchase-qty-content {
    background: #141414;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    color: #f5f5f5;
}
#purchase_qty_modal .purchase-qty-header {
    border-bottom: 1px solid #2a2a2a;
    padding: 12px 16px;
}
#purchase_qty_modal .purchase-qty-header .close { color: #fff; opacity: .7; text-shadow: none; }
#purchase_qty_modal .purchase-qty-product {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 4px;
}
#purchase_qty_modal .purchase-qty-meta {
    color: #a3a3a3;
    font-size: 12px;
    margin-bottom: 10px;
}
#purchase_qty_modal .purchase-qty-details {
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 14px;
}
#purchase_qty_modal .purchase-qty-detail-row {
    display: flex;
    gap: 10px;
    padding: 4px 0;
    font-size: 12px;
}
#purchase_qty_modal .purchase-qty-detail-row .pq-label {
    flex: 0 0 88px;
    color: #737373;
    text-transform: uppercase;
    letter-spacing: .03em;
    font-size: 11px;
    font-weight: 600;
}
#purchase_qty_modal .purchase-qty-detail-row .pq-value {
    flex: 1;
    color: #e5e5e5;
    white-space: normal;
    word-break: break-word;
}
#purchase_qty_modal .purchase-qty-fields {
    margin-left: -6px;
    margin-right: -6px;
}
#purchase_qty_modal .purchase-qty-fields > [class*="col-"] {
    padding-left: 6px;
    padding-right: 6px;
}
#purchase_qty_modal .purchase-qty-fields .control-label {
    font-size: 11px;
    color: #a3a3a3;
    text-transform: uppercase;
    letter-spacing: .03em;
}
#purchase_qty_modal #purchase_qty_input,
#purchase_qty_modal #purchase_qty_cost,
#purchase_qty_modal #purchase_qty_sell {
    background: #1a1a1a;
    border-color: #333;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    height: 42px;
    text-align: center;
}
#purchase_qty_modal .purchase-qty-footer {
    border-top: 1px solid #2a2a2a;
    padding: 10px 16px;
}
</style>

{{-- In-app receipt print preview (QPOS) --}}
<div class="vspos-pp-backdrop" id="vspos_pp_backdrop" aria-hidden="true"></div>
<div class="vspos-pp-modal" id="vspos_print_preview" role="dialog" aria-modal="true" aria-labelledby="vspos_pp_title" aria-hidden="true">
    <div class="vspos-pp-shell">
        <header class="vspos-pp-head">
            <div class="vspos-pp-brand">
                <span class="vspos-pp-mark">Q</span>
                <div>
                    <h3 id="vspos_pp_title">Print Preview</h3>
                    <p class="vspos-pp-sub">Invoice / receipt</p>
                </div>
            </div>
            <button type="button" class="vspos-pp-x" id="vspos_pp_close" aria-label="Close">&times;</button>
        </header>

        <div class="vspos-pp-stage">
            <div class="vspos-pp-badge"><span>Preview</span></div>
            <div class="vspos-pp-paper-wrap">
                <div class="vspos-pp-paper" id="vspos_pp_paper"></div>
            </div>
        </div>

        <footer class="vspos-pp-foot">
            <button type="button" class="vspos-pp-btn vspos-pp-btn--ghost" id="vspos_pp_cancel">Close</button>
            <button type="button" class="vspos-pp-btn vspos-pp-btn--print" id="vspos_pp_print">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </footer>
    </div>
</div>
<iframe id="vspos_pp_print_frame" title="print" style="position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;"></iframe>

{{-- Screen-only ledger / contact view styles (PDF uses ledger_pdf_styles) --}}
<style>
/* ── Contact show page ── */
.qpos-contact-page .qpos-contact-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
}
.qpos-contact-page .qpos-contact-hero h3 {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 800;
    color: #f5f5f5;
}
.qpos-contact-page .qpos-contact-hero .select2-container {
    min-width: 220px;
}
.qpos-contact-page .qpos-contact-card {
    background: #111;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    padding: 18px 18px 8px;
    margin-bottom: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
}
.qpos-contact-page .profile-username {
    margin: 0 0 10px !important;
    color: #fafafa !important;
    font-size: 1.25rem !important;
    font-weight: 800 !important;
}
.qpos-contact-page .profile-username small {
    display: inline-block;
    margin-left: 8px;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(245, 197, 24, 0.14);
    color: #f5c518 !important;
    font-size: 0.7rem !important;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    vertical-align: middle;
}
.qpos-contact-page .profile-username i {
    color: #f5c518;
    margin-right: 6px;
}
.qpos-contact-page .text-muted {
    color: #a3a3a3 !important;
}
.qpos-contact-page strong {
    color: #e5e5e5;
}
.qpos-contact-page .nav-tabs-custom {
    background: #111 !important;
    border: 1px solid #2a2a2a !important;
    border-radius: 16px !important;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
}
.qpos-contact-page .nav-tabs-custom > .nav-tabs {
    background: #0a0a0a !important;
    border-bottom: 1px solid #222 !important;
    margin: 0 !important;
    padding: 6px 8px 0 !important;
}
.qpos-contact-page .nav-tabs-custom > .nav-tabs > li > a {
    border: none !important;
    border-radius: 10px 10px 0 0 !important;
    color: #a3a3a3 !important;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    padding: 10px 12px !important;
    margin: 0 2px !important;
}
.qpos-contact-page .nav-tabs-custom > .nav-tabs > li.active > a,
.qpos-contact-page .nav-tabs-custom > .nav-tabs > li.active > a:hover {
    background: #111 !important;
    color: #f5c518 !important;
    border-bottom: 2px solid #f5c518 !important;
}
.qpos-contact-page .nav-tabs-custom > .tab-content {
    background: #111 !important;
    padding: 16px !important;
}

/* ── Ledger toolbar ── */
.ql-toolbar {
    display: grid;
    grid-template-columns: 1.1fr 1.4fr 1fr auto;
    gap: 12px;
    align-items: end;
    padding: 14px;
    margin-bottom: 14px;
    background: #0a0a0a;
    border: 1px solid #222;
    border-radius: 14px;
}
.ql-toolbar .form-group {
    margin-bottom: 0 !important;
}
.ql-toolbar label {
    display: block;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #a3a3a3 !important;
    margin-bottom: 6px;
}
.ql-format-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ql-format-group label.btn {
    margin: 0 !important;
    border-radius: 9px !important;
    border: 1px solid #333 !important;
    background: #141414 !important;
    color: #d4d4d4 !important;
    font-size: 0.75rem !important;
    font-weight: 700 !important;
    padding: 7px 11px !important;
    box-shadow: none !important;
}
.ql-format-group label.btn.active,
.ql-format-group label.btn:hover {
    background: rgba(245, 197, 24, 0.14) !important;
    border-color: rgba(245, 197, 24, 0.45) !important;
    color: #f5c518 !important;
}
.ql-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    align-items: center;
    padding-bottom: 2px;
}
.ql-actions .btn {
    width: 38px;
    height: 38px;
    border-radius: 10px !important;
    border: 1px solid #333 !important;
    background: #141414 !important;
    color: #f5c518 !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
}
.ql-actions .btn:hover {
    background: rgba(245, 197, 24, 0.14) !important;
    border-color: rgba(245, 197, 24, 0.4) !important;
}

/* ── Ledger document ── */
.qpos-ledger {
    background: #0c0c0c;
    border: 1px solid #2a2a2a;
    border-radius: 16px;
    padding: 18px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
}
.ql-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #222;
}
.ql-brand {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
}
.ql-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    object-fit: contain;
    background: #111;
    padding: 4px;
    box-shadow: inset 0 0 0 1px rgba(245, 197, 24, 0.35), 0 6px 16px rgba(245, 197, 24, 0.15);
    flex-shrink: 0;
}
.ql-brand-text strong {
    display: block;
    color: #fafafa;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: .02em;
}
.ql-brand-text span,
.ql-brand-text p {
    color: #a3a3a3;
    font-size: 0.82rem;
    margin: 2px 0 0;
    line-height: 1.4;
}
.ql-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(245, 197, 24, 0.12);
    border: 1px solid rgba(245, 197, 24, 0.28);
    color: #f5c518;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    white-space: nowrap;
}
.ql-grid {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
.ql-card {
    background: #111;
    border: 1px solid #2a2a2a;
    border-radius: 14px;
    overflow: hidden;
}
.ql-card-head {
    padding: 10px 14px;
    background: linear-gradient(90deg, rgba(245,197,24,0.18), rgba(245,197,24,0.05));
    border-bottom: 1px solid #2a2a2a;
    color: #f5c518;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.ql-card-body {
    padding: 14px;
}
.ql-party-name {
    color: #fafafa;
    font-size: 1rem;
    font-weight: 800;
    margin: 0 0 6px;
}
.ql-party-meta {
    color: #a3a3a3;
    font-size: 0.84rem;
    line-height: 1.55;
    margin: 0;
}
.ql-period {
    color: #d4d4d4;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 10px;
}
.ql-summary-table {
    width: 100%;
    margin: 0 !important;
}
.ql-summary-table td {
    border: none !important;
    padding: 7px 0 !important;
    color: #c4c4c4 !important;
    background: transparent !important;
    font-size: 0.86rem;
}
.ql-summary-table td:last-child {
    text-align: right !important;
    font-weight: 700;
    color: #f5f5f5 !important;
    font-variant-numeric: tabular-nums;
}
.ql-summary-table tr.ql-due td {
    border-top: 1px solid #2a2a2a !important;
    padding-top: 10px !important;
    color: #f5c518 !important;
    font-weight: 800;
}
.ql-summary-table tr.ql-due td:last-child {
    color: #f5c518 !important;
}
.ql-table-title {
    text-align: center;
    color: #e5e5e5;
    font-weight: 700;
    margin: 4px 0 12px;
    font-size: 0.92rem;
}
.qpos-ledger #ledger_table {
    margin: 0 !important;
    background: transparent !important;
    color: #e5e5e5 !important;
}
.qpos-ledger #ledger_table > thead > tr > th,
.qpos-ledger table.table > thead > tr > th {
    background: #141414 !important;
    color: #f5c518 !important;
    border-color: #2a2a2a !important;
    font-size: 0.72rem !important;
    font-weight: 800 !important;
    letter-spacing: .04em;
    text-transform: uppercase;
    white-space: nowrap;
}
.qpos-ledger #ledger_table > tbody > tr > td,
.qpos-ledger table.table > tbody > tr > td {
    background: #0f0f0f !important;
    color: #d4d4d4 !important;
    border-color: #222 !important;
    vertical-align: middle !important;
}
.qpos-ledger #ledger_table > tbody > tr:nth-child(even) > td,
.qpos-ledger table.table > tbody > tr:nth-child(even) > td {
    background: #121212 !important;
}
.qpos-ledger #ledger_table .align-right,
.qpos-ledger #ledger_table .ws-nowrap {
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}
.qpos-ledger .blue-heading {
    background: transparent !important;
    color: #f5c518 !important;
}
.qpos-ledger .row > [class*="col-"] > p,
.qpos-ledger .row > [class*="col-"] strong {
    color: #e5e5e5;
}
.qpos-ledger h2,
.qpos-ledger h3,
.qpos-ledger h4 {
    color: #f5f5f5 !important;
}

@media (max-width: 991px) {
    .ql-toolbar {
        grid-template-columns: 1fr 1fr;
    }
    .ql-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
    .ql-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .ql-toolbar {
        grid-template-columns: 1fr;
    }
    .ql-header {
        flex-direction: column;
    }
}
</style>

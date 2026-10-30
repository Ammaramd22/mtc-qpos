(function () {
    'use strict';

    if (window.qposPreviewReady) {
        return;
    }
    window.qposPreviewReady = true;

    var zoom = 1;
    var fitMode = 'width';
    var timer = null;
    var abortCtrl = null;
    var letterHeadDataUrl = '';
    var logoDataUrl = '';

    function el(id) {
        return document.getElementById(id);
    }

    function rootEl() {
        return el('qposLivePreview');
    }

    function formEl() {
        return el('add_invoice_layout_form');
    }

    function paperPx(design) {
        if (design === 'slim2') {
            return 260;
        }
        if (design === 'slim' || design === 'classic_80mm' || design === 'classic_80mm_mobile' || design === 'quotation_80mm') {
            return 320;
        }
        if (design === 'a5_half_landscape') {
            return 900;
        }
        return 840;
    }

    function designValue() {
        var form = formEl();
        var select = form ? form.querySelector('select[name="design"]') : null;
        return select && select.value ? select.value : 'classic';
    }

    function setStatus(text) {
        var statusEl = el('qposPreviewStatus');
        if (statusEl) {
            statusEl.textContent = text || '';
        }
    }

    function showProblem(text) {
        var box = el('qposPreviewMessage');
        if (!box) {
            return;
        }
        box.hidden = false;
        box.textContent = text;
    }

    function hideProblem() {
        var box = el('qposPreviewMessage');
        if (box) {
            box.hidden = true;
        }
    }

    function paintModes() {
        var form = formEl();
        var selected = form ? form.querySelector('input[name="common_settings[print_appearance]"]:checked') : null;
        var value = selected ? selected.value : 'colour';
        var nodes = document.querySelectorAll('#qposLivePreview [data-qpos-mode]');
        for (var i = 0; i < nodes.length; i++) {
            var on = nodes[i].getAttribute('data-qpos-mode') === value;
            nodes[i].classList.toggle('is-on', on);
            nodes[i].setAttribute('aria-pressed', on ? 'true' : 'false');
        }
        if (form) {
            var cards = form.querySelectorAll('.qpos-mode-option');
            for (var c = 0; c < cards.length; c++) {
                var input = cards[c].querySelector('input');
                cards[c].classList.toggle('is-selected', !!(input && input.checked));
            }
        }
    }

    function paintZoom(scale) {
        var label = el('qposZoomLabel');
        if (label) {
            label.textContent = Math.round(scale * 100) + '%';
        }
    }

    function applyScale(resetScroll) {
        var frame = el('qposPreviewFrame');
        var stage = el('qposPreviewStage');
        var scaler = el('qposPreviewScaler');
        if (!frame || !stage || !scaler) {
            return;
        }
        var px = paperPx(designValue());
        var height = frame._contentHeight || 900;
        frame.style.width = px + 'px';
        frame.style.height = height + 'px';
        frame.style.minHeight = '0px';
        frame.style.maxHeight = 'none';
        frame.style.transform = 'none';
        var availW = Math.max(240, stage.clientWidth - 24);
        var availH = Math.max(320, stage.clientHeight - 20);
        var widthFit = availW / px;
        var heightFit = availH / height;
        var base = fitMode === 'fit' ? Math.min(widthFit, heightFit) : widthFit;
        var scale = Math.max(0.25, Math.min(2.4, base * zoom));
        scaler.style.zoom = String(scale);
        scaler.style.width = px + 'px';
        scaler.style.height = 'auto';
        scaler.style.margin = '0 auto';
        paintZoom(scale);
        if (resetScroll) {
            stage.scrollTop = 0;
        }
    }

    function sectionFor(node) {
        if (!node || !node.closest) {
            return 'qposSectionLayout';
        }
        if (node.closest('.t80-foot, .a4-foot, .t80-thanks')) {
            return 'qposSectionFooter';
        }
        if (node.closest('.t80-totals, .t80-pay, .a4-pay, .a4-total')) {
            return 'qposSectionTotals';
        }
        if (node.closest('.t80-item, .t80-bar, table, .a4-lines')) {
            return 'qposSectionTable';
        }
        if (node.closest('.t80-party, .a4-parties, .a4-party')) {
            return 'qposSectionCustomer';
        }
        if (node.closest('.t80-doc, .t80-doc-title, .a4-doc-title, .a4-top-right')) {
            return 'qposSectionHeading';
        }
        if (node.closest('.t80-head, .a4-top, .a4-biz-name, .t80-biz')) {
            return 'qposSectionBusiness';
        }
        return 'qposDesigner';
    }

    function focusSection(id) {
        var grid = document.querySelector('.qpos-layout-edit-grid');
        if (grid && grid.classList.contains('is-preview-only')) {
            grid.classList.remove('is-preview-only');
            var expand = el('qposPreviewExpand');
            if (expand) {
                expand.textContent = 'Expand preview';
            }
        }
        var target = el(id);
        if (!target) {
            return;
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('qpos-section-flash');
        setTimeout(function () {
            target.classList.remove('qpos-section-flash');
        }, 1400);
    }

    function bindPreviewClicks() {
        var frame = el('qposPreviewFrame');
        if (!frame) {
            return;
        }
        var doc;
        try {
            doc = frame.contentDocument;
        } catch (e) {
            return;
        }
        if (!doc || !doc.body || doc.body.getAttribute('data-qpos-bound') === '1') {
            return;
        }
        doc.body.setAttribute('data-qpos-bound', '1');
        var style = doc.createElement('style');
        style.textContent = '.t80-head,.t80-doc,.t80-item,.t80-bar,.t80-totals,.t80-pay,.t80-foot,.a4-top,.a4-parties,.a4-lines,.a4-foot,table{cursor:pointer}';
        if (doc.head) {
            doc.head.appendChild(style);
        }
        doc.body.addEventListener('click', function (event) {
            focusSection(sectionFor(event.target));
        });
    }

    function measure() {
        var frame = el('qposPreviewFrame');
        if (!frame) {
            return;
        }
        try {
            var doc = frame.contentDocument;
            if (doc && doc.body) {
                var paper = doc.querySelector('.paper');
                var h = paper ? (paper.offsetTop + paper.offsetHeight) : 0;
                if (h < 240) {
                    var kids = doc.body.children;
                    for (var i = 0; i < kids.length; i++) {
                        h = Math.max(h, kids[i].offsetTop + kids[i].offsetHeight);
                    }
                    h = Math.max(h, doc.body.scrollHeight, doc.documentElement.scrollHeight);
                }
                frame._contentHeight = Math.max(700, h + 32);
            }
        } catch (e) {
            frame._contentHeight = 1200;
        }
        applyScale(false);
        bindPreviewClicks();
    }

    function readFile(input, assign) {
        var file = input.files && input.files[0];
        if (!file || !/^image\//.test(file.type) || file.size > 1000000) {
            assign('');
            schedule();
            return;
        }
        var reader = new FileReader();
        reader.onload = function () {
            assign(typeof reader.result === 'string' ? reader.result : '');
            schedule();
        };
        reader.readAsDataURL(file);
    }

    function refresh() {
        var root = rootEl();
        var form = formEl();
        var frame = el('qposPreviewFrame');
        if (!root || !form || !frame) {
            setStatus('Preview is not ready.');
            return;
        }
        if (abortCtrl) {
            abortCtrl.abort();
        }
        abortCtrl = typeof AbortController === 'function' ? new AbortController() : null;
        var data = new FormData(form);
        data.delete('_method');
        data.delete('letter_head');
        data.delete('logo');
        if (letterHeadDataUrl) {
            data.set('letter_head_data_url', letterHeadDataUrl);
        }
        if (logoDataUrl) {
            data.set('logo_data_url', logoDataUrl);
        }
        setStatus('Updating…');
        var tokenInput = form.querySelector('input[name="_token"]');
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        };
        if (tokenInput) {
            headers['X-CSRF-TOKEN'] = tokenInput.value;
        }
        fetch(root.getAttribute('data-url'), {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: headers,
            signal: abortCtrl ? abortCtrl.signal : undefined
        }).then(function (response) {
            return response.text().then(function (html) {
                if (!response.ok) {
                    throw new Error('Preview could not be updated (' + response.status + '). The sample already on screen is unchanged.');
                }
                if (!html || html.replace(/\s/g, '') === '') {
                    throw new Error('Preview came back empty.');
                }
                hideProblem();
                frame.onload = function () {
                    measure();
                    setStatus('Updated');
                };
                frame.srcdoc = html;
                setStatus('Updated');
            });
        }).catch(function (err) {
            if (err && err.name === 'AbortError') {
                return;
            }
            showProblem((err && err.message) ? err.message : 'Preview could not be updated.');
            setStatus('Preview could not be updated.');
        });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(refresh, 320);
    }

    function syncDesignFromPreview(select) {
        var form = formEl();
        var target = form ? form.querySelector('select[name="design"]') : null;
        if (target && select && target.value !== select.value) {
            target.value = select.value;
        }
        schedule();
    }

    function syncDesignFromForm() {
        var form = formEl();
        var preview = el('qposPreviewDesign');
        var target = form ? form.querySelector('select[name="design"]') : null;
        if (preview && target && preview.value !== target.value) {
            preview.value = target.value;
        }
    }

    var presets = {
        classic: { primary: '#1a3a4a', text: '#111111', table_header_bg: '#1a3a4a', table_header_text: '#ffffff', border: '#1a3a4a', total_highlight: '#1a3a4a', footer_color: '#334155' },
        slate: { primary: '#334155', text: '#0f172a', table_header_bg: '#334155', table_header_text: '#ffffff', border: '#64748b', total_highlight: '#0f172a', footer_color: '#334155' },
        forest: { primary: '#14532d', text: '#111111', table_header_bg: '#14532d', table_header_text: '#ffffff', border: '#166534', total_highlight: '#14532d', footer_color: '#14532d' }
    };

    function applyPreset(name) {
        var preset = presets[name];
        if (!preset) {
            return;
        }
        var use = el('qposUseColours');
        if (use) {
            use.checked = true;
        }
        Object.keys(preset).forEach(function (key) {
            var input = el('qpos-' + key);
            if (input) {
                input.value = preset[key];
            }
        });
        schedule();
    }

    function resetDesigner() {
        var use = el('qposUseColours');
        if (use) {
            use.checked = false;
        }
        var font = el('qposFontFamily');
        if (font) {
            font.value = '';
        }
        ['business_size', 'heading_size', 'table_size', 'totals_size', 'footer_size', 'logo_mm', 'section_gap', 'cell_pad', 'border_width', 'page_margin'].forEach(function (key) {
            var input = el('qpos-' + key);
            if (input) {
                input.value = '';
            }
        });
        var form = formEl();
        if (form) {
            var aligns = form.querySelectorAll('select[name^="common_settings[designer][align_"]');
            for (var i = 0; i < aligns.length; i++) {
                aligns[i].value = '';
            }
        }
        schedule();
    }

    function runAction(action, button) {
        if (action === 'zoom-in') {
            fitMode = 'manual';
            zoom = Math.min(3, zoom * 1.15);
            applyScale(false);
            setStatus(el('qposZoomLabel') ? el('qposZoomLabel').textContent : 'Zoomed in');
            return;
        }
        if (action === 'zoom-out') {
            fitMode = 'manual';
            zoom = Math.max(0.35, zoom / 1.15);
            applyScale(false);
            setStatus(el('qposZoomLabel') ? el('qposZoomLabel').textContent : 'Zoomed out');
            return;
        }
        if (action === 'fit') {
            fitMode = 'fit';
            zoom = 1;
            applyScale(true);
            setStatus('Fit');
            return;
        }
        if (action === 'refresh') {
            refresh();
            return;
        }
        if (action === 'top' || action === 'bottom') {
            var stage = el('qposPreviewStage');
            if (stage) {
                stage.scrollTop = action === 'bottom' ? stage.scrollHeight : 0;
            }
            setStatus(action === 'bottom' ? 'Bottom' : 'Top');
            return;
        }
        if (action === 'expand') {
            var grid = document.querySelector('.qpos-layout-edit-grid');
            if (!grid) {
                return;
            }
            var expanded = grid.classList.toggle('is-preview-only');
            if (button) {
                button.textContent = expanded ? 'Show settings' : 'Expand preview';
            }
            applyScale(false);
            setStatus(expanded ? 'Preview expanded' : 'Settings shown');
            return;
        }
        if (action === 'colour' || action === 'black_white') {
            var form = formEl();
            var input = form ? form.querySelector('input[name="common_settings[print_appearance]"][value="' + action + '"]') : null;
            if (input) {
                input.checked = true;
            }
            paintModes();
            setStatus(action === 'black_white' ? 'Black & white' : 'Colour');
            schedule();
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }
        var button = target.closest('#qposLivePreview [data-qpos-action]');
        if (button) {
            event.preventDefault();
            runAction(button.getAttribute('data-qpos-action'), button);
            return;
        }
        var preset = target.closest('#add_invoice_layout_form [data-qpos-preset]');
        if (preset) {
            event.preventDefault();
            applyPreset(preset.getAttribute('data-qpos-preset'));
            return;
        }
        if (target.closest('#qposResetDesigner')) {
            event.preventDefault();
            resetDesigner();
            return;
        }
        if (target.closest('#qposResetPrintAppearance')) {
            event.preventDefault();
            var form = formEl();
            var input = form ? form.querySelector('input[name="common_settings[print_appearance]"][value="colour"]') : null;
            if (input) {
                input.checked = true;
            }
            paintModes();
            schedule();
        }
    }, true);

    document.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || !target.closest || !target.closest('#add_invoice_layout_form')) {
            return;
        }
        if (target.id === 'qposPreviewDesign') {
            syncDesignFromPreview(target);
            return;
        }
        if (target.name === 'design') {
            syncDesignFromForm();
            schedule();
            return;
        }
        if (target.name === 'letter_head') {
            readFile(target, function (url) { letterHeadDataUrl = url; });
            return;
        }
        if (target.name === 'logo') {
            readFile(target, function (url) { logoDataUrl = url; });
            return;
        }
        paintModes();
        schedule();
    }, true);

    document.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.closest || !target.closest('#add_invoice_layout_form')) {
            return;
        }
        if (target.id === 'qposPreviewDesign' || target.id === 'qposPreviewDocument') {
            return;
        }
        if (target.tagName === 'SELECT' || target.type === 'file' || target.type === 'checkbox' || target.type === 'radio') {
            return;
        }
        schedule();
    }, true);

    window.addEventListener('resize', function () {
        applyScale(false);
    });

    function bootLate() {
        if (!window.jQuery) {
            return;
        }
        window.jQuery(document).on('ifChanged select2:select', '#add_invoice_layout_form input, #add_invoice_layout_form select', function () {
            paintModes();
            schedule();
        });
    }

    syncDesignFromForm();
    paintModes();
    var frame = el('qposPreviewFrame');
    if (frame && frame.getAttribute('srcdoc')) {
        frame.onload = measure;
        try {
            if (frame.contentDocument && frame.contentDocument.readyState === 'complete') {
                measure();
            }
        } catch (e) {}
    } else {
        applyScale(true);
    }
    if (document.readyState === 'complete') {
        bootLate();
    } else {
        window.addEventListener('load', bootLate);
    }
}());

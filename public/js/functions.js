//This file contains all functions used in the app.

function __calculate_amount(calculation_type, calculation_amount, amount) {
    var calculation_amount = parseFloat(calculation_amount);
    calculation_amount = isNaN(calculation_amount) ? 0 : calculation_amount;

    var amount = parseFloat(amount);
    amount = isNaN(amount) ? 0 : amount;

    switch (calculation_type) {
        case 'fixed':
            return parseFloat(calculation_amount);
        case 'percentage':
        case 'percent':
                var div = Decimal.div(calculation_amount, 100).toNumber();
            return Decimal.mul(div, amount).toNumber();
        default:
            return 0;
    }
}

//Add specified percentage to the input amount.
function __add_percent(amount, percentage = 0) {
    var amount = parseFloat(amount);
    var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);

    var div = Decimal.div(percentage, 100).toNumber();
    var mul = Decimal.mul(div, amount).toNumber();
    return Decimal.add(amount, mul).toNumber();
}

//Substract specified percentage to the input amount.
function __substract_percent(amount, percentage = 0) {
    var amount = parseFloat(amount);
    var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);

    var div = Decimal.div(percentage, 100).toNumber();
    var mul = Decimal.mul(div, amount).toNumber();
    return Decimal.sub(amount, mul).toNumber();
}

//Returns the principle amount for the calculated amount and percentage
function __get_principle(amount, percentage = 0, minus = false) {
    var amount = parseFloat(amount);
    var percentage = isNaN(percentage) ? 0 : parseFloat(percentage);
    var mul = Decimal.mul(100, amount).toNumber();
    var sum = 1;
    if (minus) {
        sum = Decimal.sub(100, percentage).toNumber();
    } else {
        sum = Decimal.add(100, percentage).toNumber();
    }
    return Decimal.div(mul, sum).toNumber();
}

//Returns the rate at which amount is calculated from principal
function __get_rate(principal, amount) {
    var principal = isNaN(principal) ? 0 : parseFloat(principal);
    var amount = isNaN(amount) ? 0 : parseFloat(amount);
    var interest = Decimal.sub(amount, principal).toNumber();
    var div = Decimal.div(interest, principal).toNumber();
    return Decimal.mul(div, 100).toNumber();
}

function __tab_key_up(e) {
    if (e.keyCode == 9) {
        return true;
    }
}

function __currency_trans_from_en(
    input,
    show_symbol = true,
    use_page_currency = false,
    precision = __currency_precision,
    is_quantity = false
) {
    if (use_page_currency && __p_currency_symbol) {
        var s = __p_currency_symbol;
        var thousand = __p_currency_thousand_separator;
        var decimal = __p_currency_decimal_separator;
    } else {
        var s = __currency_symbol;
        var thousand = __currency_thousand_separator;
        var decimal = __currency_decimal_separator;
    }

    symbol = '';
    var format = '%s%v';
    if (show_symbol) {
        symbol = s;
        format = '%s %v';
        if (__currency_symbol_placement == 'after') {
            format = '%v %s';
        }
    }

    if (is_quantity) {
        precision = __quantity_precision;
    }

    return accounting.formatMoney(input, symbol, precision, thousand, decimal, format);
}

function __currency_convert_recursively(element, use_page_currency = false) {
    element.find('.display_currency').each(function() {
        var value = $(this).text();

        var show_symbol = $(this).data('currency_symbol');
        if (show_symbol == undefined || show_symbol != true) {
            show_symbol = false;
        }

        //If data-use_page_currency is present in the element use_page_currency 
        //value will be over written 
        if (typeof $(this).data('use_page_currency') !== 'undefined') {
            use_page_currency = $(this).data('use_page_currency');
        }

        var highlight = $(this).data('highlight');
        if (highlight == true) {
            __highlight(value, $(this));
        }

        var is_quantity = $(this).data('is_quantity');
        if (is_quantity == undefined || is_quantity != true) {
            is_quantity = false;
        }

        if (is_quantity) {
            show_symbol = false;
        }

        $(this).text(__currency_trans_from_en(value, show_symbol, use_page_currency, __currency_precision, is_quantity));
    });
}

function __translate(str, obj = []) {
    var trans = LANG[str];
    $.each(obj, function(key, value) {
        trans = trans.replace(':' + key, value);
    });
    if (trans) {
        return trans;
    } else {
        return str;
    }
}

//If the value is positive, text-success class will be applied else text-danger
function __highlight(value, obj) {
    obj.removeClass('text-success').removeClass('text-danger');
    if (value > 0) {
        obj.addClass('text-success');
    } else if (value < 0) {
        obj.addClass('text-danger');
    }
}

//Unformats the currency/number
function __number_uf(input, use_page_currency = false) {
    if (use_page_currency && __currency_decimal_separator) {
        var decimal = __p_currency_decimal_separator;
    } else {
        var decimal = __currency_decimal_separator;
    }

    return accounting.unformat(input, decimal);
}

//Alias of currency format, formats number
function __number_f(
    input,
    show_symbol = false,
    use_page_currency = false,
    precision = __currency_precision
) {
    return __currency_trans_from_en(input, show_symbol, use_page_currency, precision);
}

//Read input and convert it into natural number
function __read_number(input_element, use_page_currency = false) {
    return __number_uf(input_element.val(), use_page_currency);
}

//Write input by converting to formatted number
function __write_number(
    input_element,
    value,
    use_page_currency = false,
    precision = __currency_precision
) {
    if(input_element.hasClass('input_quantity')) {
        precision = __quantity_precision;
    }

    input_element.val(__number_f(value, false, use_page_currency, precision));
}

//Return the font-awesome html based on class value
function __fa_awesome($class = 'fa-sync fa-spin fa-fw ') {
    return '<i class="fa ' + $class + '"></i>';
}

//Converts standard dates (YYYY-MM-DD) to human readable dates
function __show_date_diff_for_human(element) {
    moment.locale(app_locale);
    element.find('.time-to-now').each(function() {
        var string = $(this).text();
        $(this).text(moment(string).toNow(true));
    });

    element.find('.time-from-now').each(function() {
        var string = $(this).text();
        $(this).text(moment(string).from(moment()));
    });
}

//Rounds a number to Iraqi dinnar
function round_to_iraqi_dinnar(value) {
    //Adjsustment
    var remaining = value % 250;
    if (remaining >= 125) {
        value += 250 - remaining;
    } else {
        value -= remaining;
    }

    return value;
}

function __select2(selector) {
    if ($('html').attr('dir') == 'rtl') selector.select2({ dir: 'rtl' });
    else selector.select2();
}

function update_font_size() {
    var font_size = localStorage.getItem('upos_font_size');
    var font_size_array = [];
    font_size_array['s'] = ' - 3px';
    font_size_array['m'] = '';
    font_size_array['l'] = ' + 3px';
    font_size_array['xl'] = ' + 6px';
    if (typeof font_size !== 'undefined') {
        $('header').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
        $('footer').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
        $('section').each(function() {
            if (!$(this).hasClass('print_section')) {
                $(this).css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
            }
        });
        $('div.modal').css('font-size', 'calc(100% ' + font_size_array[font_size] + ')');
    }
}

function sum_table_col(table, class_name) {
    var sum = 0;
    table
        .find('tbody')
        .find('tr')
        .each(function() {
            if (
                parseFloat(
                    $(this)
                        .find('.' + class_name)
                        .data('orig-value')
                )
            ) {
                sum += parseFloat(
                    $(this)
                        .find('.' + class_name)
                        .data('orig-value')
                );
            }
        });

    return sum;
}

function __count_status(data, key) {
    var statuses = [];
    for (var r in data){
        var element = $(data[r][key]);
        if (element.data('orig-value')) {
            var status_name = element.data('orig-value');
            if (!(status_name in statuses)) {
                statuses[status_name] = [];
                statuses[status_name]['count'] = 1;
                statuses[status_name]['display_name'] = element.data('status-name');
            } else {
                statuses[status_name]['count'] += 1;
            }
        }
    }

    //generate html
    var html = '<p class="text-left"><small>';
    for (var key in statuses) {
        html +=
            statuses[key]['display_name'] + ' - ' + statuses[key]['count'] + '</br>';
    }

    html += '</small></p>';

    return html;
}

function __sum_status(table, class_name) {
    var statuses = [];
    var status_html = [];
    table
        .find('tbody')
        .find('tr')
        .each(function() {
            element = $(this).find('.' + class_name);
            if (element.data('orig-value')) {
                var status_name = element.data('orig-value');
                if (!(status_name in statuses)) {
                    statuses[status_name] = [];
                    statuses[status_name]['count'] = 1;
                    statuses[status_name]['display_name'] = element.data('status-name');
                } else {
                    statuses[status_name]['count'] += 1;
                }
            }
        });

    return statuses;
}

function __sum_status_html(table, class_name) {
    var statuses_sum = __sum_status(table, class_name);
    var status_html = '<p class="text-left"><small>';
    for (var key in statuses_sum) {
        status_html +=
            statuses_sum[key]['display_name'] + ' - ' + statuses_sum[key]['count'] + '</br>';
    }

    status_html += '</small></p>';

    return status_html;
}

function __sum_stock(table, class_name, label_direction = 'right') {
    var stocks = [];
    table
        .find('tbody')
        .find('tr')
        .each(function() {
            element = $(this).find('.' + class_name);
            if (element.data('orig-value')) {
                var unit_name = element.data('unit');
                if (!(unit_name in stocks)) {
                    stocks[unit_name] = parseFloat(element.data('orig-value'));
                } else {
                    stocks[unit_name] += parseFloat(element.data('orig-value'));
                }
            }
        });
    var stock_html = '<p class="text-left"><small>';

    for (var key in stocks) {
        if (label_direction == 'left') {
            stock_html +=
                key +
                ' : <span class="display_currency" data-is_quantity="true">' +
                stocks[key] +
                '</span> ' +
                '</br>';
        } else {
            stock_html +=
                '<span class="display_currency" data-is_quantity="true">' +
                stocks[key] +
                '</span> ' +
                key +
                '</br>';
        }
    }

    stock_html += '</small></p>';

    return stock_html;
}

function __vspos_dedupe_print_preview() {
    // Keep only the last modal/backdrop/iframe if Blade was included twice
    var modals = document.querySelectorAll('#vspos_print_preview');
    var backs = document.querySelectorAll('#vspos_pp_backdrop');
    var frames = document.querySelectorAll('#vspos_pp_print_frame');
    var i;
    for (i = 0; i < modals.length - 1; i++) {
        modals[i].parentNode && modals[i].parentNode.removeChild(modals[i]);
    }
    for (i = 0; i < backs.length - 1; i++) {
        backs[i].parentNode && backs[i].parentNode.removeChild(backs[i]);
    }
    for (i = 0; i < frames.length - 1; i++) {
        frames[i].parentNode && frames[i].parentNode.removeChild(frames[i]);
    }
}

function __vspos_ensure_print_preview() {
    __vspos_dedupe_print_preview();
    if (document.getElementById('vspos_print_preview')) {
        return true;
    }
    var wrap = document.createElement('div');
    wrap.id = 'vspos_pp_root';
    wrap.innerHTML =
        '<div class="vspos-pp-backdrop" id="vspos_pp_backdrop" aria-hidden="true"></div>' +
        '<div class="vspos-pp-modal" id="vspos_print_preview" role="dialog" aria-modal="true" aria-hidden="true">' +
        '<div class="vspos-pp-shell"><header class="vspos-pp-head"><div class="vspos-pp-brand">' +
        '<span class="vspos-pp-mark">Q</span><div><h3>Print Preview</h3>' +
        '<p class="vspos-pp-sub">Thermal receipt</p></div></div>' +
        '<button type="button" class="vspos-pp-x" id="vspos_pp_close" aria-label="Close">&times;</button></header>' +
        '<div class="vspos-pp-stage"><div class="vspos-pp-badge"><span>thermal</span></div>' +
        '<div class="vspos-pp-paper-wrap"><div class="vspos-pp-paper" id="vspos_pp_paper"></div></div></div>' +
        '<footer class="vspos-pp-foot">' +
        '<button type="button" class="vspos-pp-btn vspos-pp-btn--ghost" id="vspos_pp_cancel">Close</button>' +
        '<button type="button" class="vspos-pp-btn vspos-pp-btn--print" id="vspos_pp_print"><i class="fas fa-print"></i> Print Receipt</button>' +
        '</footer></div></div>' +
        '<iframe id="vspos_pp_print_frame" title="print" style="position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;"></iframe>';
    document.body.appendChild(wrap);
    return true;
}

function __vspos_close_print_preview() {
    var modals = document.querySelectorAll('#vspos_print_preview');
    var backs = document.querySelectorAll('#vspos_pp_backdrop');
    var i;
    for (i = 0; i < modals.length; i++) {
        modals[i].classList.remove('is-open');
        modals[i].setAttribute('aria-hidden', 'true');
    }
    for (i = 0; i < backs.length; i++) {
        backs[i].classList.remove('is-open');
        backs[i].setAttribute('aria-hidden', 'true');
    }
    if (document.body) {
        document.body.classList.remove('vspos-pp-open', 'vspos-pp-printing', 'printing-receipt');
    }
}

function __vspos_after_print_preview_dismissed() {
    if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
        PosReceiptReturn.handleDismiss();
    }
}

function __vspos_run_browser_print(printWindow, onComplete, beforePrint) {
    var finished = false;
    function finish(ok, message) {
        if (finished) {
            return;
        }
        finished = true;
        if (typeof onComplete === 'function') {
            onComplete({ ok: !!ok, message: message || '' });
        }
    }
    if (!printWindow || typeof printWindow.print !== 'function') {
        finish(false, 'Printing was blocked or the printer is unavailable.');
        return;
    }
    try {
        if (typeof beforePrint === 'function') {
            beforePrint();
        }
        printWindow.focus();
        printWindow.print();
        finish(true);
        if (typeof beforePrint === 'function' && typeof pos_focus_for_next_sale === 'function') {
            pos_focus_for_next_sale();
        }
    } catch (err) {
        finish(false, 'Printing was blocked or the printer is unavailable.');
    }
}

function __vspos_bind_print_preview_once() {
    if (window.__vspos_pp_bound) {
        return;
    }
    window.__vspos_pp_bound = true;
    $(document).on('click', '#vspos_pp_close, #vspos_pp_cancel, #vspos_pp_backdrop', function (e) {
        e.preventDefault();
        __vspos_close_print_preview();
        __vspos_after_print_preview_dismissed();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            var m = document.getElementById('vspos_print_preview');
            if (m && m.classList.contains('is-open')) {
                __vspos_close_print_preview();
                __vspos_after_print_preview_dismissed();
            }
        }
    });
    $(document).on('click', '#vspos_pp_print', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var btn = this;
        if (btn.getAttribute('data-busy') === '1') {
            return;
        }
        btn.setAttribute('data-busy', '1');
        var returnToPosBeforePrint = window.PosReceiptReturn && PosReceiptReturn.isActive();
        __vspos_print_preview_to_printer(function (result) {
            btn.removeAttribute('data-busy');
            if (!result || !result.ok) {
                if (window.toastr) {
                    toastr.warning((result && result.message) || 'Printing was blocked or the printer is unavailable.');
                }
            }
            if (returnToPosBeforePrint && window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                PosReceiptReturn.handleAction(result && result.ok ? 'print' : 'print-failed');
            }
        }, function () {
            if (returnToPosBeforePrint && window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                PosReceiptReturn.handleDismiss();
            }
        });
    });
}

function __vspos_paper_profile(html, explicitWidth, explicitHeight, designOrLabel) {
    var design = (designOrLabel || '').toString().toLowerCase();
    var w = parseInt(explicitWidth, 10);
    var h = parseInt(explicitHeight, 10);
    if (!h || isNaN(h)) {
        h = null;
    }

    // Prefer known designs / labels
    if (design.indexOf('a5') !== -1 || design === 'a5_half_landscape') {
        return { width: 210, height: 148, key: 'a5', label: 'A5 Landscape', page: 'A5 landscape', thermal: false };
    }
    if (design.indexOf('a4') !== -1 || design === 'classic' || design === 'elegant' ||
        design === 'elegant_modified' || design === 'detailed' || design === 'columnize-taxes' ||
        design === 'english-arabic') {
        return { width: 210, height: 297, key: 'a4', label: 'A4', page: 'A4', thermal: false };
    }
    if (w === 210 && h === 148) {
        return { width: 210, height: 148, key: 'a5', label: 'A5 Landscape', page: 'A5 landscape', thermal: false };
    }
    if (w === 210 && (h === 297 || h === null)) {
        // Only treat as A4 when height says so, or design already handled; width-only 210 without height falls through
        if (h === 297) {
            return { width: 210, height: 297, key: 'a4', label: 'A4', page: 'A4', thermal: false };
        }
    }
    if (w === 58 || (html && String(html).indexOf('data-paper-width="58"') !== -1) || design.indexOf('58') !== -1) {
        return { width: 58, height: null, key: '58', label: '58mm thermal', page: '58mm auto', thermal: true };
    }
    if (w === 80 || !w) {
        return { width: 80, height: null, key: '80', label: '80mm thermal', page: '80mm auto', thermal: true };
    }
    // Unknown explicit width — treat as thermal roll of that width
    return { width: w, height: null, key: String(w), label: w + 'mm', page: w + 'mm auto', thermal: true };
}

function __vspos_paper_width_mm(html, explicit) {
    return __vspos_paper_profile(html, explicit, null, null).width;
}

/** Immediate print (no preview modal) — used by handheld POS setting. */
function __vspos_print_html_immediate(html, paperWidth, onComplete, beforePrint) {
    if (!html || !String(html).trim()) {
        if (typeof onComplete === 'function') {
            onComplete({ ok: false, message: 'Receipt is empty.' });
        }
        return;
    }
    if (typeof __vspos_ensure_print_preview === 'function') {
        __vspos_ensure_print_preview();
    }
    var frame = document.getElementById('vspos_pp_print_frame');
    if (!frame) {
        // Fallback: classic browser print on a temp container
        var tmp = document.createElement('div');
        tmp.id = 'vspos_immediate_print_tmp';
        tmp.style.cssText = 'position:fixed;left:-9999px;top:0;';
        tmp.innerHTML = html;
        document.body.appendChild(tmp);
        if (typeof __currency_convert_recursively === 'function') {
            __currency_convert_recursively($(tmp));
        }
        __vspos_run_browser_print(window, onComplete, beforePrint);
        setTimeout(function () {
            if (tmp.parentNode) {
                tmp.parentNode.removeChild(tmp);
            }
        }, 800);
        return;
    }
    var profile;
    if (typeof paperWidth === 'object' && paperWidth) {
        profile = __vspos_paper_profile(
            html,
            paperWidth.paper_width || paperWidth.width || null,
            paperWidth.paper_height || paperWidth.height || null,
            paperWidth.design || paperWidth.paper_label || paperWidth.label || null
        );
    } else {
        profile = __vspos_paper_profile(html, paperWidth, null, null);
    }
    window.__vspos_pp_paper = profile;
    window.__vspos_pp_paper_width = profile.width;
    var pad = profile.thermal ? (profile.width === 58 ? '1.5mm 1.2mm 2.5mm' : '2mm 2mm 3mm') : '0';
    var pageSize = profile.key === 'a5'
        ? 'A5 landscape'
        : (profile.height ? (profile.page || (profile.width + 'mm ' + profile.height + 'mm')) : (profile.width + 'mm auto'));
    var bodySize = profile.key === 'a5'
        ? 'width:210mm;max-width:210mm;height:148mm;overflow:hidden;'
        : ('width:' + profile.width + 'mm;max-width:' + profile.width + 'mm;');
    var doc = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(
        '<!DOCTYPE html><html><head><meta charset="utf-8">' +
        '<title>Print</title>' +
        '<style>' +
        '@page{size:' + pageSize + ';margin:0}' +
        'html,body{margin:0;padding:0;background:#fff}' +
        'body{' + bodySize + 'padding:' + pad + ';box-sizing:border-box}' +
        '.row{margin-left:0!important;margin-right:0!important;}' +
        '*{-webkit-print-color-adjust:exact;print-color-adjust:exact}' +
        '</style></head><body>' + html + '</body></html>'
    );
    doc.close();
    setTimeout(function () {
        __vspos_run_browser_print(frame.contentWindow, onComplete, beforePrint);
    }, 250);
}

function __vspos_print_preview_to_printer(onComplete, beforePrint) {
    __vspos_dedupe_print_preview();
    var paper = document.getElementById('vspos_pp_paper');
    var frame = document.getElementById('vspos_pp_print_frame');
    if (!paper || !frame) {
        if (typeof onComplete === 'function') {
            onComplete({ ok: false, message: 'Print preview is not available.' });
        }
        return;
    }
    var content = paper.innerHTML;
    if (!content || !String(content).trim()) {
        if (typeof onComplete === 'function') {
            onComplete({ ok: false, message: 'Receipt is empty.' });
        }
        return;
    }
    var profile = window.__vspos_pp_paper || __vspos_paper_profile(content, window.__vspos_pp_paper_width, null, null);
    var mm = profile.width;
    var pad = profile.thermal ? (mm === 58 ? '1.5mm 1.2mm 2.5mm' : '2mm 2mm 3mm') : '0';
    var fs = profile.thermal ? (mm === 58 ? '10px' : '11px') : '12px';
    var pageSize = profile.key === 'a5'
        ? 'A5 landscape'
        : (profile.height ? (profile.page || (mm + 'mm ' + profile.height + 'mm')) : (mm + 'mm auto'));
    var bodyW = mm + 'mm';
    var bodyH = profile.height ? (profile.height + 'mm') : 'auto';
    var ticketRule = profile.thermal
        ? ('.ticket{width:' + mm + 'mm;max-width:' + mm + 'mm;margin:0;padding:' + pad + ';box-sizing:border-box;overflow:hidden;}')
        : ('.ticket{width:100%;max-width:100%;margin:0;padding:0;box-sizing:border-box;}');
    var sheetRule = profile.key === 'a5'
        ? 'html,body{width:210mm!important;height:148mm!important;overflow:hidden!important;}'
        : '';
    var html =
        '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Receipt</title>' +
        '<style>' +
        '@page{size:' + pageSize + ';margin:0;}' +
        'html,body{margin:0;padding:0;background:#fff;color:#111;}' +
        'body{font-family:Arial,Helvetica,sans-serif;font-size:' + fs + ';width:' + bodyW + ';height:' + bodyH + ';overflow:hidden;box-sizing:border-box;}' +
        sheetRule +
        ticketRule +
        'img{max-width:100%!important;height:auto!important;}' +
        '.a5-sheet .a4-logo,.a5-sheet .a5-brand-logo img{max-width:40px!important;max-height:38px!important;width:auto!important;height:auto!important;margin:0!important;display:block!important;object-fit:contain!important;}' +
        '.a5-sheet .a5-brand-row{display:flex!important;flex-direction:row!important;align-items:center!important;gap:8px!important;}' +
        '.a5-sheet .a5-brand-logo{flex:0 0 40px!important;width:40px!important;max-width:40px!important;overflow:hidden!important;}' +
        '.a5-sheet .a5-brand-text{flex:1 1 auto!important;min-width:0!important;}' +
        'table{width:100%!important;border-collapse:collapse;' + (profile.thermal ? 'table-layout:fixed!important;' : 'table-layout:fixed!important;') + '}' +
        '.textbox-info,.flex-box,.table-info{display:flex!important;justify-content:space-between!important;gap:4px!important;width:100%!important;}' +
        '.textbox-info>p:first-child,.flex-box>p:first-child{flex:1 1 auto!important;text-align:left!important;min-width:0!important;}' +
        '.textbox-info>p:last-child,.flex-box>p:last-child{flex:0 0 auto!important;text-align:right!important;white-space:nowrap!important;}' +
        'th,td{word-break:break-word;font-size:' + fs + ';}' +
        /* Kill Bootstrap-style negative row margins that clip left edge */
        '.row{margin-left:0!important;margin-right:0!important;}' +
        '[class*="col-"]{padding-left:4px!important;padding-right:4px!important;}' +
        '@media print{' +
        '  html,body{width:' + bodyW + ';height:' + bodyH + ';margin:0;overflow:hidden;}' +
        '}' +
        '</style></head><body>' +
        content +
        '</body></html>';

    var doc = frame.contentWindow || frame.contentDocument;
    if (doc.document) {
        doc = doc.document;
    }
    doc.open();
    doc.write(html);
    doc.close();

    setTimeout(function () {
        var printWindow = frame.contentWindow;
        if (printWindow && printWindow.document) {
            __vspos_run_browser_print(printWindow, onComplete, beforePrint);
            return;
        }
        if (typeof beforePrint === 'function') {
            beforePrint();
        }
        if (typeof onComplete === 'function') {
            onComplete({ ok: false, message: 'Printing was blocked or the printer is unavailable.' });
        }
    }, 250);
}

function __vspos_show_print_preview(html, title, paperWidth, designOrOpts) {
    if (!html || !String(html).trim()) {
        return;
    }
    __vspos_ensure_print_preview();
    __vspos_dedupe_print_preview();
    __vspos_bind_print_preview_once();

    var height = null;
    var design = designOrOpts;
    if (designOrOpts && typeof designOrOpts === 'object') {
        height = designOrOpts.paper_height || designOrOpts.height || null;
        design = designOrOpts.design || designOrOpts.paper_label || designOrOpts.label || null;
        if (designOrOpts.paper_width && (paperWidth === null || typeof paperWidth === 'undefined')) {
            paperWidth = designOrOpts.paper_width;
        }
    }
    var profile = __vspos_paper_profile(html, paperWidth, height, design);
    window.__vspos_pp_paper = profile;
    window.__vspos_pp_paper_width = profile.width;

    var modal = document.getElementById('vspos_print_preview');
    var back = document.getElementById('vspos_pp_backdrop');
    var paper = document.getElementById('vspos_pp_paper');
    if (!modal || !paper) {
        return;
    }

    paper.innerHTML = html;
    var hasContent = !!(html && String(html).replace(/<[^>]*>/g, '').trim());
    paper.classList.toggle('is-empty', !hasContent);
    if (!hasContent) {
        paper.innerHTML = '<div>No receipt content to preview.</div>';
    }
    var printBtn = document.getElementById('vspos_pp_print');
    if (printBtn) {
        printBtn.disabled = !hasContent;
    }
    if (hasContent && typeof __currency_convert_recursively === 'function') {
        __currency_convert_recursively($(paper));
    }

    var heading = modal.querySelector('.vspos-pp-head h3');
    if (heading) {
        heading.textContent = 'Print Preview';
    }
    var sub = modal.querySelector('.vspos-pp-sub');
    if (sub) {
        sub.textContent = profile.thermal ? (profile.label + ' receipt') : (profile.label + ' receipt');
    }
    var badge = modal.querySelector('.vspos-pp-badge span');
    if (badge) {
        badge.textContent = profile.label;
    }
    var wrap = modal.querySelector('.vspos-pp-paper-wrap');
    if (wrap) {
        wrap.classList.remove('is-58mm', 'is-80mm', 'is-a4', 'is-a5');
        wrap.classList.add('is-' + profile.key);
    }
    modal.classList.remove('vspos-pp-58', 'vspos-pp-80', 'vspos-pp-a4', 'vspos-pp-a5');
    modal.classList.add('vspos-pp-' + profile.key);
    var shell = modal.querySelector('.vspos-pp-shell');
    if (shell) {
        shell.classList.toggle('is-wide', !profile.thermal);
    }

    if (back) {
        back.classList.add('is-open');
        back.setAttribute('aria-hidden', 'false');
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('vspos-pp-open');
}

function __print_receipt(section_id = null) {
    var sectionEl = null;
    if (section_id) {
        // Prefer last #receipt_section if duplicates exist
        var nodes = document.querySelectorAll('#' + section_id.replace(/(:|\.|\[|\]|,|=|@)/g, '\\$1'));
        if (!nodes.length && section_id === 'receipt_section') {
            nodes = document.querySelectorAll('#receipt_section');
        }
        if (!nodes.length) {
            sectionEl = document.getElementById(section_id);
        } else {
            // Pick the node that actually has content
            for (var n = nodes.length - 1; n >= 0; n--) {
                if (nodes[n].innerHTML && nodes[n].innerHTML.trim()) {
                    sectionEl = nodes[n];
                    break;
                }
            }
            if (!sectionEl) {
                sectionEl = nodes[nodes.length - 1];
            }
        }
    }
    var html = sectionEl ? sectionEl.innerHTML : '';

    // Prefer in-app preview (POS / offline desktop feel)
    if (html && String(html).trim() && document.body) {
        var previewTitle = 'Receipt';
        if (typeof document !== 'undefined' && document.title && document.title !== 'VsoftPOS') {
            previewTitle = document.title;
        }
        __vspos_show_print_preview(html, previewTitle);
        return;
    }

    // Legacy fallback: direct browser print
    if (section_id) {
        var imgs = document.getElementById(section_id).getElementsByTagName('img');
    } else {
        var imgs = document.images;
    }

    function runPrint() {
        document.body.classList.add('printing-receipt');
        var cleanup = function () {
            document.body.classList.remove('printing-receipt');
            window.removeEventListener('afterprint', cleanup);
        };
        window.addEventListener('afterprint', cleanup);
        setTimeout(cleanup, 2000);
        window.print();
    }

    img_len = imgs.length;
    if (img_len) {
        img_counter = 0;
        window.__print_receipt_run = runPrint;
        [].forEach.call(imgs, function (img) {
            img.addEventListener('load', incrementImageCounter, false);
        });
    } else {
        setTimeout(function () {
            runPrint();
        }, 1000);
    }
}

function incrementImageCounter() {
    img_counter++;
    if (img_counter === img_len) {
        if (typeof window.__print_receipt_run === 'function') {
            window.__print_receipt_run();
        } else {
            document.body.classList.add('printing-receipt');
            window.print();
            setTimeout(function () {
                document.body.classList.remove('printing-receipt');
            }, 2000);
        }
    }
}

function __getUnitMultiplier(row){
    multiplier = row.find('select.sub_unit').find(':selected').data('multiplier');
    if(multiplier == undefined){
        return 1;
    } else {
        return parseFloat(multiplier);
    }
}

//Rounds a number to the nearest given multiple
function __round(number, multiple = 0){

    rounded_number = number;
    if(multiple > 0) {
        x = new Decimal(number)
        rounded_number = x.toNearest(multiple);
    }

    var output = {
        number: rounded_number,
        diff: rounded_number - number
    }
    
    return output;
}

//This method removes unwanted get parameter from the data.
function __datatable_ajax_callback(data){
    for (var i = 0, len = data.columns.length; i < len; i++) {
        if (! data.columns[i].search.value) delete data.columns[i].search;
        if (data.columns[i].searchable === true) delete data.columns[i].searchable;
        if (data.columns[i].orderable === true) delete data.columns[i].orderable;
        if (data.columns[i].data === data.columns[i].name) delete data.columns[i].name;
    }
    delete data.search.regex;

    return data;
}

//Confirmation before page load.
function __page_leave_confirmation(form) {
    var form_obj = $(form);
    var orig_form_data = form_obj.serialize();

    setTimeout(function(){ orig_form_data = form_obj.serialize(); }, 1000);
    
    $(document).on("submit", "form", function(event){
        window.onbeforeunload = null;
    });
    window.onbeforeunload = function() {
        if (form_obj.serialize() != orig_form_data) {
            return LANG.sure;
        }
    }
}

//initialize tinyMCE editor for invoice template
function init_tinymce(editor_id) {
    tinymce.init({
        selector: 'textarea#' + editor_id,
        plugins: [
        'advlist autolink link image lists charmap print preview hr anchor pagebreak',
        'searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking',
        'table template paste help'
        ],
        toolbar: 'undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify |' +
          ' bullist numlist outdent indent | link image | print preview fullpage | ' +
          'forecolor backcolor',
        menu: {
          favs: {title: 'My Favorites', items: 'code | searchreplace'}
        },
        menubar: 'favs file edit view insert format tools table help'
    });
}

function getSelectedRows() {
    var selected_rows = [];
    var i = 0;
    $('.row-select:checked').each(function () {
        selected_rows[i++] = $(this).val();
    });

    return selected_rows; 
}

function __is_online() {
    return true;
    
    //if localhost always return true
    if ($('#__is_localhost').length > 0) {
        return true;
    }

    return window.navigator.onLine;
}

function __disable_submit_button(element) {
    if (__is_online()) {
        element.attr('disable', true);
    }
}

function __current_datetime() {
    return moment().format(moment_date_format + ' ' + moment_time_format);
}

//Converts formatted number string to float (similar to PHP num_uf function)
function __num_uf(input_number, use_page_currency = false) {
    var thousand_separator = '';
    var decimal_separator = '';

    if (use_page_currency && __p_currency_thousand_separator) {
        thousand_separator = __p_currency_thousand_separator;
        decimal_separator = __p_currency_decimal_separator;
    } else {
        thousand_separator = __currency_thousand_separator;
        decimal_separator = __currency_decimal_separator;
    }

    // Remove thousand separators
    var num = String(input_number || '0').replace(new RegExp('\\' + thousand_separator, 'g'), '');
    // Replace decimal separator with dot
    num = num.replace(decimal_separator, '.');

    return parseFloat(num) || 0;
}
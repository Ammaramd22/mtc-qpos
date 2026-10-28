/**
 * Communication module — Photocopy / Printout POS helpers.
 * Adds paper product lines with custom unit price; stock deducts on sale finalize.
 */
(function ($) {
    'use strict';

    window.__communication_enabled = window.__communication_enabled || false;
    window.vspos_communication_options_url = window.vspos_communication_options_url || '';

    var cache = null;
    var state = {
        service: 'photocopy',
        color: 'bw',
        side: 'single',
        activeField: 'qty',
        freshInput: true
    };

    function enabled() {
        return window.__communication_enabled === true || window.__communication_enabled === 'true';
    }

    function locationId() {
        return $('input#location_id').val() || '';
    }

    function num(v) {
        if (typeof __read_number === 'function' && (v instanceof $ || (v && v.jquery))) {
            return __read_number(v) || 0;
        }
        var n = parseFloat(String(v == null ? '' : v).replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }

    function writeNum($el, v) {
        if (typeof __write_number === 'function') {
            __write_number($el, v);
        } else {
            $el.val(v);
        }
    }

    function loadOptions(cb) {
        if (!window.vspos_communication_options_url) {
            toastr.error('Communication module is not available');
            return;
        }
        $.getJSON(window.vspos_communication_options_url, { location_id: locationId() })
            .done(function (res) {
                if (!res || !res.success) {
                    toastr.error((res && res.msg) ? res.msg : 'Could not load communication options');
                    return;
                }
                cache = res;
                if (typeof cb === 'function') {
                    cb(res);
                }
            })
            .fail(function (xhr) {
                var msg = 'Could not load communication options';
                if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            });
    }

    function papersForService() {
        if (!cache) {
            return [];
        }
        return state.service === 'printout'
            ? (cache.printout_papers || [])
            : (cache.photocopy_papers || []);
    }

    function selectedPaper() {
        var id = parseInt($('#vspos_comm_paper').val(), 10);
        var list = papersForService();
        for (var i = 0; i < list.length; i++) {
            if (parseInt(list[i].variation_id, 10) === id) {
                return list[i];
            }
        }
        return list[0] || null;
    }

    function inkCost() {
        if (!cache || !cache.ink) {
            return 0;
        }
        if (state.service === 'photocopy') {
            return parseFloat(cache.ink.photocopy) || 0;
        }
        return state.color === 'color'
            ? (parseFloat(cache.ink.print_color) || 0)
            : (parseFloat(cache.ink.print_bw) || 0);
    }

    function defaultPrice() {
        if (!cache || !cache.defaults) {
            return 0;
        }
        var d = cache.defaults;
        if (state.service === 'photocopy') {
            return state.side === 'double'
                ? (parseFloat(d.photocopy_double) || 0)
                : (parseFloat(d.photocopy_single) || 0);
        }
        if (state.color === 'color') {
            return state.side === 'double'
                ? (parseFloat(d.print_color_double) || 0)
                : (parseFloat(d.print_color_single) || 0);
        }
        return state.side === 'double'
            ? (parseFloat(d.print_bw_double) || 0)
            : (parseFloat(d.print_bw_single) || 0);
    }

    function updateAvgCost() {
        var paper = selectedPaper();
        var paperCost = paper ? (parseFloat(paper.cost) || 0) : 0;
        var ink = inkCost();
        // Double-sided uses ink on both sides
        var sides = state.side === 'double' ? 2 : 1;
        var avg = paperCost + (ink * sides);
        var $el = $('#vspos_comm_avg_cost');
        if (typeof __currency_trans_from_en === 'function') {
            $el.text(__currency_trans_from_en(avg, true));
        } else {
            $el.text(avg.toFixed(2));
        }
        var stock = paper ? (parseFloat(paper.stock) || 0) : 0;
        $('#vspos_comm_stock_hint').text(
            paper
                ? ('Stock: ' + stock + (paper.sku ? ' · ' + paper.sku : ''))
                : ''
        );
    }

    function fillPaperSelect() {
        var $sel = $('#vspos_comm_paper');
        var papers = papersForService();
        $sel.empty();
        if (!papers.length) {
            $sel.append($('<option>').val('').text('No paper products configured'));
            return;
        }
        papers.forEach(function (p) {
            $sel.append(
                $('<option>')
                    .val(p.variation_id)
                    .text(p.label + (p.stock != null ? ' (' + p.stock + ')' : ''))
            );
        });
    }

    function syncUi() {
        var isPrint = state.service === 'printout';
        var title = isPrint ? 'Printout' : 'Photocopy';
        $('#vspos_comm_title_text').text(title);
        $('#vspos_comm_product_label').text(title);
        $('#vspos_comm_color_wrap').toggle(isPrint);
        $('#vspos_comm_bw_label_wrap').toggle(!isPrint);
        if (!isPrint) {
            state.color = 'bw';
        }
        $('.vspos-comm-color-btn').removeClass('is-active');
        $('.vspos-comm-color-btn[data-color="' + state.color + '"]').addClass('is-active');
        $('.vspos-comm-side-btn').removeClass('is-active');
        $('.vspos-comm-side-btn[data-side="' + state.side + '"]').addClass('is-active');
        writeNum($('#vspos_comm_price'), defaultPrice());
        setActiveField('qty');
        updateAvgCost();
        var paper = selectedPaper();
        if (paper && paper.label) {
            $('#vspos_comm_product_label').text(title + ' · ' + paper.label);
        }
    }

    function setActiveField(field) {
        state.activeField = field === 'rate' ? 'rate' : 'qty';
        state.freshInput = true;
        $('#vspos_communication_modal .vspos-touch-qty-display-wrap').removeClass('is-active');
        if (state.activeField === 'rate') {
            $('#vspos_comm_rate_wrap').addClass('is-active');
            $('#vspos_comm_price').focus().select();
        } else {
            $('#vspos_comm_qty_wrap').addClass('is-active');
            $('#vspos_comm_qty').focus().select();
        }
    }

    function activeInput() {
        return state.activeField === 'rate' ? $('#vspos_comm_price') : $('#vspos_comm_qty');
    }

    function applyKey(key) {
        var $input = activeInput();
        var cur = String($input.val() == null ? '' : $input.val());
        if (key === 'clear') {
            $input.val(state.activeField === 'qty' ? '1' : '0');
            state.freshInput = true;
            return;
        }
        if (key === 'one') {
            writeNum($('#vspos_comm_qty'), 1);
            setActiveField('qty');
            return;
        }
        if (key === 'back') {
            if (state.freshInput || cur.length <= 1) {
                $input.val('');
                state.freshInput = false;
            } else {
                $input.val(cur.slice(0, -1));
            }
            return;
        }
        if (key === '.') {
            if (state.freshInput) {
                $input.val('0.');
                state.freshInput = false;
                return;
            }
            if (cur.indexOf('.') === -1) {
                $input.val(cur + '.');
            }
            return;
        }
        if (/^\d$/.test(key)) {
            if (state.freshInput || cur === '0') {
                $input.val(key);
                state.freshInput = false;
            } else {
                $input.val(cur + key);
            }
        }
    }

    function openModal(service) {
        try {
            state.service = service === 'printout' ? 'printout' : 'photocopy';
            state.color = 'bw';
            state.side = 'single';
            state.activeField = 'qty';
            state.freshInput = true;

            var $modal = $('#vspos_communication_modal');
            if (!$modal.length) {
                if (typeof window.vsposOpenCommunicationFallback === 'function') {
                    window.vsposOpenCommunicationFallback(service);
                    return;
                }
                toastr.error('Communication popup missing — refresh POS page');
                return;
            }

            if ($modal.parent()[0] !== document.body) {
                $modal.appendTo(document.body);
            }
            $modal.css({ 'z-index': 30000 });
            // Keep backdrop under this modal
            setTimeout(function () {
                $('.modal-backdrop').last().css('z-index', 29990);
            }, 10);

            $('#vspos_comm_service').val(state.service);
            try { writeNum($('#vspos_comm_qty'), 1); } catch (e1) { $('#vspos_comm_qty').val('1'); }

            // Show FIRST so user always sees the popup
            $modal.modal('show');

            try {
                fillPaperSelect();
                syncUi();
            } catch (e2) {
                console.error('Communication syncUi failed', e2);
            }

            // Refresh papers/stock in background
            loadOptions(function () {
                try {
                    fillPaperSelect();
                    syncUi();
                } catch (e3) {}
            });

            setTimeout(function () {
                try { setActiveField('qty'); } catch (e4) {}
            }, 300);
        } catch (err) {
            console.error('Communication openModal failed', err);
            toastr.error('Could not open communication: ' + (err && err.message ? err.message : 'error'));
            if (typeof window.vsposOpenCommunicationFallback === 'function') {
                window.vsposOpenCommunicationFallback(service);
            }
        }
    }

    function lineLabel(paper) {
        var parts = [];
        parts.push(state.service === 'printout' ? 'Printout' : 'Photocopy');
        if (paper && paper.label) {
            parts.push(paper.label);
        }
        parts.push(state.color === 'color' ? 'Colour' : 'B/W');
        parts.push(state.side === 'double' ? 'Double' : 'Single');
        return parts.join(' · ');
    }

    function addToSale() {
        var paper = selectedPaper();
        if (!paper || !paper.variation_id) {
            toastr.error('Select a paper product first (Products → Communication)');
            return;
        }
        var qty = num($('#vspos_comm_qty'));
        var price = num($('#vspos_comm_price'));
        if (qty <= 0) {
            toastr.error('Enter a valid quantity');
            $('#vspos_comm_qty').focus();
            return;
        }
        if (price < 0) {
            toastr.error('Enter a valid unit price');
            $('#vspos_comm_price').focus();
            return;
        }

        var location = locationId();
        if (!location) {
            toastr.error('Select a business location');
            return;
        }

        var note = lineLabel(paper);
        window.__vspos_entry_committing = true;

        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + paper.variation_id + '/' + location,
            data: {
                product_row: $('input#product_row_count').val(),
                customer_id: $('select#customer_id').val(),
                price_group: $('#price_group').length ? $('#price_group').val() : '',
                quantity: qty,
                disable_qty_alert: true
            },
            dataType: 'json',
            success: function (result) {
                window.__vspos_entry_committing = false;
                if (!result || !result.success) {
                    toastr.error((result && result.msg) ? result.msg : 'Could not add to sale');
                    return;
                }
                if (typeof pos_insert_product_row === 'function') {
                    pos_insert_product_row(result);
                }

                var $row = $('table#pos_table tbody tr').last();
                if (typeof __write_number === 'function') {
                    __write_number($row.find('input.pos_unit_price'), price);
                    __write_number($row.find('input.pos_quantity'), qty);
                } else {
                    $row.find('input.pos_unit_price').val(price);
                    $row.find('input.pos_quantity').val(qty);
                }

                // Display service label on the line
                var $name = $row.find('.vspos-line-name');
                if ($name.length) {
                    var html = $name.html() || '';
                    // Keep edit icon if present
                    if (html.indexOf('vspos-line-edit-ico') !== -1) {
                        $name.html(note + ' <i class="fa fa-pen vspos-line-edit-ico"></i>');
                    } else {
                        $name.text(note);
                    }
                }

                var $note = $row.find('textarea[name*="[sell_line_note]"]');
                if ($note.length) {
                    $note.val(note);
                }
                var $hiddenNote = $row.find('input.pos_sell_line_note');
                if (!$hiddenNote.length) {
                    var vName = $row.find('input[name*="[variation_id]"]').attr('name') || '';
                    if (vName) {
                        $row.find('td.vspos-pos-hidden-fields, td.hide').first().append(
                            '<input type="hidden" name="' + vName.replace('[variation_id]', '[sell_line_note]') + '" class="pos_sell_line_note" value="">'
                        );
                        $hiddenNote = $row.find('input.pos_sell_line_note');
                    }
                }
                if ($hiddenNote.length) {
                    $hiddenNote.val(note);
                }

                $row.find('input.pos_unit_price').trigger('change');
                $row.find('input.pos_quantity').trigger('change');

                if (typeof vspos_sync_all_row_edit_modals === 'function') {
                    vspos_sync_all_row_edit_modals();
                } else if (typeof pos_each_row === 'function') {
                    pos_each_row($row);
                }
                if (typeof pos_total_row === 'function') {
                    pos_total_row();
                }

                $('#vspos_communication_modal').modal('hide');
                toastr.success(note + ' added');
            },
            error: function () {
                window.__vspos_entry_committing = false;
                toastr.error('Could not add to sale');
            }
        });
    }

    function renderCommunicationTiles() {
        var $body = $('#product_list_body');
        var $items = $('#product_list_items');
        var target = $items.length ? $items : $body;
        if (!target.length) {
            return;
        }
        var html = ''
            + '<div class="col-md-3 col-xs-4 no-print !tw-px-[3px]">'
            + '  <div class="product_box vspos-comm-service-tile tw-w-full tw-mb-1 tw-text-center tw-cursor-pointer tw-font-semibold tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-[#0f766e] tw-shadow" data-comm-service="photocopy" role="button" tabindex="0">'
            + '    <div style="font-size:28px;color:#0f766e;margin-bottom:6px;"><i class="fa fa-copy"></i></div>'
            + '    <div>Photocopy</div>'
            + '  </div>'
            + '</div>'
            + '<div class="col-md-3 col-xs-4 no-print !tw-px-[3px]">'
            + '  <div class="product_box vspos-comm-service-tile tw-w-full tw-mb-1 tw-text-center tw-cursor-pointer tw-font-semibold tw-bg-white tw-rounded-lg tw-p-3 tw-border tw-border-[#0369a1] tw-shadow" data-comm-service="printout" role="button" tabindex="0">'
            + '    <div style="font-size:28px;color:#0369a1;margin-bottom:6px;"><i class="fa fa-print"></i></div>'
            + '    <div>Printout</div>'
            + '  </div>'
            + '</div>';
        target.html(html);
    }

    window.vsposOpenCommunication = openModal;
    window.vsposRenderCommunicationTiles = renderCommunicationTiles;

    $(function () {
        // Always bind — buttons only render when module is enabled
        $(document).on('click', '#vspos_pos_photocopy_btn, #vspos_touch_photocopy_btn, .vspos-touch-comm-btn[data-comm-service="photocopy"]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal('photocopy');
        });
        $(document).on('click', '#vspos_pos_printout_btn, #vspos_touch_printout_btn, .vspos-touch-comm-btn[data-comm-service="printout"]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal('printout');
        });

        $(document).on('click', '.vspos-comm-service-tile', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal($(this).data('comm-service'));
        });

        $(document).on('click', '.vspos-comm-color-btn', function () {
            state.color = $(this).data('color');
            syncUi();
        });
        $(document).on('click', '.vspos-comm-side-btn', function () {
            state.side = $(this).data('side');
            syncUi();
        });
        $(document).on('change', '#vspos_comm_paper', function () {
            updateAvgCost();
            var paper = selectedPaper();
            var title = state.service === 'printout' ? 'Printout' : 'Photocopy';
            $('#vspos_comm_product_label').text(paper && paper.label ? (title + ' · ' + paper.label) : title);
        });
        $(document).on('click', '#vspos_comm_add_btn', addToSale);

        $(document).on('click', '#vspos_communication_modal .vspos-touch-qty-display-wrap', function () {
            setActiveField($(this).data('field'));
        });
        $(document).on('focus', '#vspos_comm_qty', function () {
            setActiveField('qty');
        });
        $(document).on('focus', '#vspos_comm_price', function () {
            setActiveField('rate');
        });
        $(document).on('click', '#vspos_comm_keypad [data-comm-key]', function (e) {
            e.preventDefault();
            applyKey(String($(this).data('comm-key')));
        });

        // Enter on price → add
        $(document).on('keydown', '#vspos_comm_qty, #vspos_comm_price', function (e) {
            if (e.key === 'Enter' || e.which === 13) {
                e.preventDefault();
                if (state.activeField === 'qty') {
                    setActiveField('rate');
                } else {
                    addToSale();
                }
            }
        });
    });
})(jQuery);

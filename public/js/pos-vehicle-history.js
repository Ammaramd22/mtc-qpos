/**
 * Vehicle Modification POS — Vehicle Job History lookup.
 * Search by registration or customer phone; select vehicle for current sale.
 */
(function () {
    if (window.__vsposVehicleHistoryBound) {
        return;
    }
    window.__vsposVehicleHistoryBound = true;

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        var $ = window.jQuery;
        if (!$('#pos_vehicle_history_modal').length) {
            return;
        }

        var searchType = 'registration';
        var selectedVehicle = null;
        var urls = window.__vehicle_history_urls || {};

        function setType(type) {
            searchType = type;
            $('.vspos-vh-type').each(function () {
                var active = $(this).data('type') === type;
                $(this).toggleClass('active', active);
                if (active) {
                    $(this).css({ background: '#f5c518', borderColor: '#f5c518', color: '#111' });
                } else {
                    $(this).css({ background: '#2a2a2a', borderColor: '#444', color: '#ddd' });
                }
            });
            var ph = type === 'phone'
                ? (urls.phone_placeholder || 'Customer phone')
                : (urls.reg_placeholder || 'Vehicle number');
            $('#vspos_vh_q').attr('placeholder', ph);
        }

        function resetPanels() {
            $('#vspos_vh_empty').addClass('hide').text('');
            $('#vspos_vh_results').addClass('hide');
            $('#vspos_vh_jobs_wrap').addClass('hide');
            $('#vspos_vh_job_detail').addClass('hide');
            $('#vspos_vh_vehicle_list').empty();
            $('#vspos_vh_job_list').empty();
            $('#vspos_vh_job_detail_body').empty();
        }

        function showEmpty(msg) {
            $('#vspos_vh_empty').removeClass('hide').text(msg || 'No results');
        }

        function money(n) {
            if (typeof __currency_trans_from_en === 'function') {
                return __currency_trans_from_en(n, true);
            }
            return (Number(n) || 0).toFixed(2);
        }

        function renderVehicles(list) {
            var $box = $('#vspos_vh_vehicle_list').empty();
            if (!list || !list.length) {
                $('#vspos_vh_results').addClass('hide');
                showEmpty(urls.no_vehicles || 'No vehicles found');
                return;
            }
            $('#vspos_vh_empty').addClass('hide');
            $('#vspos_vh_results').removeClass('hide');
            list.forEach(function (v) {
                var $a = $('<a href="#" class="list-group-item vspos-vh-vehicle"/>')
                    .data('vehicle', v)
                    .html(
                        '<strong>' + $('<div/>').text(v.display || v.registration_number || ('#' + v.id)).html() + '</strong>' +
                        '<div class="vspos-vh-meta">' +
                        $('<div/>').text((v.customer_name || '') + (v.customer_mobile ? ' · ' + v.customer_mobile : '')).html() +
                        ' · ' + (v.job_count || 0) + ' ' + (urls.jobs_label || 'jobs') +
                        '</div>'
                    );
                $box.append($a);
            });
        }

        function loadJobs(vehicleId) {
            $('#vspos_vh_loading').removeClass('hide');
            $('#vspos_vh_job_detail').addClass('hide');
            $.getJSON((urls.vehicle || '/vehicle-modification/history/vehicle/') + vehicleId)
                .done(function (res) {
                    $('#vspos_vh_loading').addClass('hide');
                    if (!res || !res.success) {
                        showEmpty((res && res.msg) || 'Failed');
                        return;
                    }
                    selectedVehicle = res.vehicle;
                    $('#vspos_vh_selected_vehicle').text(
                        (res.vehicle.display || '') +
                        (res.vehicle.customer_name ? ' — ' + res.vehicle.customer_name : '')
                    );
                    $('#vspos_vh_jobs_wrap').removeClass('hide');
                    var $list = $('#vspos_vh_job_list').empty();
                    if (!res.jobs || !res.jobs.length) {
                        $list.append('<div class="list-group-item">' + (urls.no_jobs || 'No previous jobs') + '</div>');
                        return;
                    }
                    res.jobs.forEach(function (j) {
                        var title = (j.reference_no || ('#' + j.id)) +
                            (j.invoice_no ? ' / ' + j.invoice_no : '');
                        var meta = (j.date || '') + ' · ' + (j.status_label || j.status) +
                            ' · ' + (j.payment_label || j.payment_status) +
                            ' · ' + money(j.estimated_total);
                        var lines = [];
                        if (j.services_summary) lines.push(j.services_summary);
                        if (j.parts_summary) lines.push(j.parts_summary);
                        var $a = $('<a href="#" class="list-group-item vspos-vh-job"/>')
                            .data('job-id', j.id)
                            .html(
                                '<strong>' + $('<div/>').text(title).html() + '</strong>' +
                                '<div class="vspos-vh-meta">' + $('<div/>').text(meta).html() + '</div>' +
                                (lines.length ? '<div class="vspos-vh-meta">' + $('<div/>').text(lines.join(' | ')).html() + '</div>' : '')
                            );
                        $list.append($a);
                    });
                })
                .fail(function () {
                    $('#vspos_vh_loading').addClass('hide');
                    showEmpty(urls.error || 'Request failed');
                });
        }

        function loadJobDetail(jobId) {
            $('#vspos_vh_loading').removeClass('hide');
            $.getJSON((urls.job || '/vehicle-modification/history/job/') + jobId)
                .done(function (res) {
                    $('#vspos_vh_loading').addClass('hide');
                    if (!res || !res.success || !res.job) {
                        showEmpty((res && res.msg) || 'Failed');
                        return;
                    }
                    var j = res.job;
                    var html = '';
                    html += '<h4 style="color:#f5c518;margin-top:0;">' + $('<div/>').text(j.reference_no || ('#' + j.id)).html() + '</h4>';
                    html += '<p class="vspos-vh-meta">' +
                        $('<div/>').text((j.date || '') + ' · ' + (j.status_label || '') + ' · ' + (j.payment_label || '')).html() +
                        '</p>';
                    html += '<p><b>' + (urls.customer || 'Customer') + ':</b> ' +
                        $('<div/>').text((j.customer_name || '') + (j.customer_mobile ? ' · ' + j.customer_mobile : '')).html() + '</p>';
                    if (j.vehicle) {
                        html += '<p><b>' + (urls.vehicle_label || 'Vehicle') + ':</b> ' +
                            $('<div/>').text(j.vehicle.display || '').html() + '</p>';
                    }
                    if (j.invoice_no) {
                        html += '<p><b>' + (urls.invoice || 'Invoice') + ':</b> ' +
                            $('<div/>').text(j.invoice_no).html() + '</p>';
                    }
                    if (j.description) {
                        html += '<p><b>' + (urls.description || 'Description') + ':</b> ' +
                            $('<div/>').text(j.description).html() + '</p>';
                    }
                    if (j.notes) {
                        html += '<p><b>' + (urls.notes || 'Notes') + ':</b> ' +
                            $('<div/>').text(j.notes).html() + '</p>';
                    }
                    html += '<p><b>' + (urls.total || 'Total') + ':</b> ' + money(j.estimated_total) + '</p>';
                    html += '<table class="table table-condensed vspos-vh-items"><thead><tr>' +
                        '<th>' + (urls.item || 'Item') + '</th><th>' + (urls.type || 'Type') + '</th>' +
                        '<th>Qty</th><th>' + (urls.price || 'Price') + '</th><th>' + (urls.total || 'Total') + '</th>' +
                        '</tr></thead><tbody>';
                    (j.items || []).forEach(function (it) {
                        html += '<tr><td>' + $('<div/>').text(it.name || '').html() +
                            '</td><td>' + $('<div/>').text(it.item_type || '').html() +
                            '</td><td>' + it.quantity +
                            '</td><td>' + money(it.unit_price) +
                            '</td><td>' + money(it.line_total) + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    if (j.transaction_id && urls.sale_url) {
                        html += '<p><a class="btn btn-xs btn-primary" target="_blank" href="' +
                            urls.sale_url + '/' + j.transaction_id + '"><i class="fa fa-external-link-alt"></i> ' +
                            (urls.view_sale || 'View sale') + '</a></p>';
                    }
                    $('#vspos_vh_jobs_wrap').addClass('hide');
                    $('#vspos_vh_job_detail_body').html(html);
                    $('#vspos_vh_job_detail').removeClass('hide');
                })
                .fail(function () {
                    $('#vspos_vh_loading').addClass('hide');
                    showEmpty(urls.error || 'Request failed');
                });
        }

        function doSearch() {
            var q = $.trim($('#vspos_vh_q').val() || '');
            if (!q) {
                showEmpty(urls.enter_search || 'Enter a search term');
                return;
            }
            resetPanels();
            $('#vspos_vh_loading').removeClass('hide');
            $.getJSON(urls.search || '/vehicle-modification/history/search', {
                type: searchType,
                q: q
            }).done(function (res) {
                $('#vspos_vh_loading').addClass('hide');
                if (!res || !res.success) {
                    showEmpty((res && res.msg) || 'Failed');
                    return;
                }
                renderVehicles(res.vehicles || []);
            }).fail(function (xhr) {
                $('#vspos_vh_loading').addClass('hide');
                var msg = urls.error || 'Request failed';
                if (xhr && xhr.status === 403) {
                    msg = urls.denied || 'Not allowed';
                }
                showEmpty(msg);
            });
        }

        function useVehicleInPos() {
            if (!selectedVehicle || !selectedVehicle.contact_id) {
                return;
            }
            var $cust = $('#customer_id');
            if ($cust.length) {
                var opt = new Option(
                    selectedVehicle.customer_name || ('#' + selectedVehicle.contact_id),
                    selectedVehicle.contact_id,
                    true,
                    true
                );
                $cust.append(opt).val(selectedVehicle.contact_id).trigger('change');
            }
            if (!$('#workshop_vehicle_id').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'workshop_vehicle_id',
                    name: 'workshop_vehicle_id'
                }).appendTo('#add_pos_sell_form, #edit_pos_sell_form');
            }
            $('#workshop_vehicle_id').val(selectedVehicle.id);
            var banner = selectedVehicle.display || selectedVehicle.registration_number || '';
            if (banner) {
                var $note = $('#vspos_vh_pos_banner');
                if (!$note.length) {
                    $note = $('<div id="vspos_vh_pos_banner" class="alert alert-warning" style="margin:8px 0;"/>')
                        .prependTo($('.vspos-touch-cart-inner, .vspos-left-panel, #product_row_section').first());
                }
                $note.html('<i class="fa fa-car"></i> ' + $('<div/>').text(banner).html() +
                    (selectedVehicle.customer_name ? ' — ' + $('<div/>').text(selectedVehicle.customer_name).html() : ''));
            }
            $('#pos_vehicle_history_modal').modal('hide');
            if (typeof toastr !== 'undefined') {
                toastr.success(urls.vehicle_applied || 'Vehicle selected for this sale');
            }
        }

        $(document).on('click', '.vspos-vh-type', function () {
            setType($(this).data('type'));
        });
        $(document).on('click', '#vspos_vh_search_btn', function () {
            doSearch();
        });
        $(document).on('keydown', '#vspos_vh_q', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                doSearch();
            }
        });
        $(document).on('click', '.vspos-vh-vehicle', function (e) {
            e.preventDefault();
            var v = $(this).data('vehicle');
            if (!v || !v.id) return;
            selectedVehicle = v;
            loadJobs(v.id);
        });
        $(document).on('click', '.vspos-vh-job', function (e) {
            e.preventDefault();
            loadJobDetail($(this).data('job-id'));
        });
        $(document).on('click', '#vspos_vh_back_jobs', function () {
            $('#vspos_vh_job_detail').addClass('hide');
            $('#vspos_vh_jobs_wrap').removeClass('hide');
        });
        $(document).on('click', '#vspos_vh_use_vehicle', function () {
            useVehicleInPos();
        });
        $('#pos_vehicle_history_modal').on('shown.bs.modal', function () {
            $('#vspos_vh_q').trigger('focus');
        });

        setType('registration');
    }

    if (window.jQuery) {
        window.jQuery(boot);
    } else {
        document.addEventListener('DOMContentLoaded', boot);
    }
})();

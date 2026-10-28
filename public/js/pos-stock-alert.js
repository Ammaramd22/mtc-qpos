/**
 * POS header — low stock alert dropdown
 */
(function ($) {
    'use strict';

    function locationId() {
        var loc = $('#location_id').val() || $('#select_location_id').val() || '';
        return loc || '';
    }

    function formatStock(n) {
        var v = parseFloat(n);
        if (isNaN(v)) return '0';
        return v.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function setBadge(count) {
        var $badge = $('#vspos_stock_alert_badge');
        if (!$badge.length) return;
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : String(count)).prop('hidden', false);
            $('#vspos_stock_alert_btn').addClass('has-alerts');
        } else {
            $badge.prop('hidden', true).text('0');
            $('#vspos_stock_alert_btn').removeClass('has-alerts');
        }
    }

    function renderList(products) {
        var $body = $('#vspos_stock_alert_body');
        if (!products || !products.length) {
            $body.html('<div class="vspos-stock-alert-empty">No low stock products</div>');
            return;
        }
        var html = '<ul class="vspos-stock-alert-list">';
        products.forEach(function (p) {
            var stockClass = parseFloat(p.stock) <= 0 ? 'is-zero' : 'is-low';
            html += '<li class="vspos-stock-alert-item">';
            html += '<div class="vspos-stock-alert-name" title="' + $('<div>').text(p.name || '').html() + '">' +
                $('<div>').text(p.name || '').html() + '</div>';
            if (p.sku) {
                html += '<div class="vspos-stock-alert-sku">' + $('<div>').text(p.sku).html() + '</div>';
            }
            html += '<div class="vspos-stock-alert-meta">';
            html += '<span class="vspos-stock-alert-qty ' + stockClass + '">' +
                formatStock(p.stock) + ' ' + $('<div>').text(p.unit || '').html() + '</span>';
            if (p.location) {
                html += '<span class="vspos-stock-alert-loc">' + $('<div>').text(p.location).html() + '</span>';
            }
            html += '</div></li>';
        });
        html += '</ul>';
        $body.html(html);
    }

    function loadAlerts(opts) {
        opts = opts || {};
        var $body = $('#vspos_stock_alert_body');
        if (opts.showLoading) {
            $body.html('<div class="vspos-stock-alert-empty">Loading…</div>');
        }
        return $.ajax({
            method: 'GET',
            url: '/home/product-stock-alert-list',
            dataType: 'json',
            data: { location_id: locationId() },
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (res) {
            var products = (res && res.products) ? res.products : [];
            var count = (res && typeof res.count !== 'undefined') ? res.count : products.length;
            setBadge(count);
            renderList(products);
        }).fail(function () {
            if (opts.showLoading) {
                $body.html('<div class="vspos-stock-alert-empty">Could not load alerts</div>');
            }
        });
    }

    function openPanel() {
        var $panel = $('#vspos_stock_alert_panel');
        var $btn = $('#vspos_stock_alert_btn');
        $panel.prop('hidden', false);
        $btn.attr('aria-expanded', 'true').addClass('is-open');
        loadAlerts({ showLoading: true });
    }

    function closePanel() {
        $('#vspos_stock_alert_panel').prop('hidden', true);
        $('#vspos_stock_alert_btn').attr('aria-expanded', 'false').removeClass('is-open');
    }

    function togglePanel() {
        if ($('#vspos_stock_alert_panel').prop('hidden')) {
            openPanel();
        } else {
            closePanel();
        }
    }

    $(function () {
        if (!$('#vspos_stock_alert').length) return;

        loadAlerts({ showLoading: false });

        $(document).on('click', '#vspos_stock_alert_btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            togglePanel();
        });

        $(document).on('click', '#vspos_stock_alert_refresh', function (e) {
            e.preventDefault();
            e.stopPropagation();
            loadAlerts({ showLoading: true });
        });

        $(document).on('click', '#vspos_stock_alert_panel', function (e) {
            e.stopPropagation();
        });

        $(document).on('click', function () {
            closePanel();
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') closePanel();
        });

        $(document).on('change', '#location_id, #select_location_id', function () {
            loadAlerts({ showLoading: false });
        });
    });
})(jQuery);

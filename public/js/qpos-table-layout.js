/**
 * DataTables scrollX measures columns inside the visible card, then nowrap
 * headings paint over the next column. After each draw, expand any column
 * that is narrower than its heading (and a capped sample of its cells) and
 * keep the header, body, and footer on the same widths.
 */
(function ($) {
    'use strict';

    window.qposListScrollY = function () {
        if (window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches) {
            var h = Math.round(window.innerHeight * 0.42);
            if (h < 240) h = 240;
            if (h > 360) h = 360;
            return h + 'px';
        }
        return '75vh';
    };

    function textNeed(el, cap) {
        if (!el) return 0;
        var cs = window.getComputedStyle(el);
        var probe = document.createElement('span');
        probe.textContent = (el.innerText || '').replace(/\s+/g, ' ').trim();
        probe.style.cssText = 'position:absolute;left:-9999px;top:0;visibility:hidden;white-space:nowrap;';
        probe.style.font = cs.font;
        probe.style.letterSpacing = cs.letterSpacing;
        probe.style.textTransform = cs.textTransform;
        document.body.appendChild(probe);
        var text = probe.getBoundingClientRect().width;
        probe.parentNode.removeChild(probe);
        var pad = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.paddingRight) || 0)
            + (parseFloat(cs.borderLeftWidth) || 0) + (parseFloat(cs.borderRightWidth) || 0);
        var child = 0;
        var nodes = el.querySelectorAll('button, a.btn, .btn, img, select, input');
        for (var i = 0; i < nodes.length && i < 4; i++) {
            var w = nodes[i].getBoundingClientRect().width;
            if (w > child) child = w;
        }
        var need = Math.ceil(Math.max(text, child) + pad + 8);
        if (cap && need > cap) need = cap;
        return need;
    }

    function applyCols(table, widths) {
        if (!table) return;
        var cg = table.querySelector('colgroup');
        if (!cg) {
            cg = document.createElement('colgroup');
            table.insertBefore(cg, table.firstChild);
        }
        while (cg.children.length < widths.length) {
            cg.appendChild(document.createElement('col'));
        }
        for (var i = 0; i < widths.length; i++) {
            cg.children[i].style.width = widths[i] + 'px';
        }
        table.style.tableLayout = 'fixed';
        table.style.width = widths.reduce(function (sum, w) { return sum + w; }, 0) + 'px';
    }

    function fit(api) {
        if (!api || !api.table) return;
        var container = $(api.table().container());
        var headTable = container.find('.dataTables_scrollHead table').get(0);
        var bodyTable = container.find('.dataTables_scrollBody table').get(0);
        if (!headTable || !bodyTable) return;
        if (!container.is(':visible') || container.width() < 40) return;

        var heads = headTable.querySelectorAll('thead th');
        if (!heads.length) return;
        var sample = bodyTable.querySelector('tbody tr');
        var empty = sample && sample.querySelector('.dataTables_empty');
        var widths = [];
        var changed = false;

        for (var i = 0; i < heads.length; i++) {
            var current = Math.ceil(heads[i].getBoundingClientRect().width);
            var headNeed = textNeed(heads[i], 0);
            var bodyNeed = 0;
            if (sample && !empty && sample.cells[i]) {
                bodyNeed = textNeed(sample.cells[i], 320);
            }
            var next = Math.max(current, headNeed, bodyNeed, 36);
            if (Math.abs(next - current) > 2) changed = true;
            widths.push(next);
        }
        if (!changed) return;

        var total = widths.reduce(function (sum, w) { return sum + w; }, 0);
        applyCols(headTable, widths);
        applyCols(bodyTable, widths);
        var footTable = container.find('.dataTables_scrollFoot table').get(0);
        if (footTable) applyCols(footTable, widths);

        container.find('.dataTables_scrollHeadInner, .dataTables_scrollFootInner').each(function () {
            this.style.width = total + 'px';
        });

        var headTh = headTable.querySelectorAll('thead th');
        for (var h = 0; h < headTh.length && h < widths.length; h++) {
            headTh[h].style.width = widths[h] + 'px';
            headTh[h].style.minWidth = widths[h] + 'px';
            headTh[h].style.maxWidth = widths[h] + 'px';
        }
    }

    var timers = {};

    function schedule(settings) {
        if (!settings || !settings.oScroll || !settings.oScroll.sX) return;
        if (document.body.classList.contains('lockscreen')) return;
        var id = settings.sInstance || 'dt';
        clearTimeout(timers[id]);
        timers[id] = setTimeout(function () {
            try {
                fit(new $.fn.dataTable.Api(settings));
            } catch (e) {}
        }, 60);
    }

    $(document).on('draw.dt column-sizing.dt', function (e, settings) {
        schedule(settings);
    });

    $(document).on('shown.bs.tab', function () {
        if (!$.fn.dataTable) return;
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
})(jQuery);

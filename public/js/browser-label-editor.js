/* Live label editor for the browser print page.
   Layout (position, font, align) is shared by every sticker.
   Price, name, size and color text belong to one product row and are not saved on the product. */
(function (window) {
    var FONT_KEYS = {
        '--biz-size': 'biz',
        '--name-size': 'name',
        '--sku-size': 'sku',
        '--price-size': 'price',
        '--costcode-size': 'costcode'
    };
    var WEIGHT_KEYS = { '--name-weight': 'name', '--price-weight': 'price' };
    var FONT_FIELDS = {
        biz: 'business_name_size',
        name: 'name_size',
        sku: 'sku_size',
        price: 'price_size',
        costcode: 'costcode_size'
    };
    var WEIGHT_FIELDS = { name: 'name_weight', price: 'price_weight' };
    var SHOW = {
        biz: '.biz',
        name: '.pname',
        barcode: '.barcode-wrap',
        sku: '.sku',
        price: '.price',
        costcode: '.cc',
        brand: '.brand',
        category: '.category',
        size: '.label-size',
        color: '.label-color'
    };
    var ORDER = ['biz', 'name', 'size', 'color', 'barcode', 'sku', 'price', 'costcode', 'brand', 'category'];
    var TEXT_KEYS = { biz: 1, name: 1, sku: 1, price: 1, costcode: 1, brand: 1, category: 1, barcode: 1 };

    var layout = {};
    var ready = false;
    var silent = false;
    var activeRow = '0';
    var selectedKey = '';
    var captions = {};
    var notes = {};
    var getRotation = function () { return '0'; };
    var fitPreview = function () {};
    var drag = null;

    function esc(value) {
        return String(value == null ? '' : value).replace(/[&<>"]/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch];
        });
    }

    function zoomScale() {
        var zoom = document.querySelector('.live-preview-zoom');
        if (!zoom) return 1;
        var transform = getComputedStyle(zoom).transform;
        if (!transform || transform === 'none') return 1;
        try {
            var matrix = new DOMMatrix(transform);
            return matrix.a || 1;
        } catch (e) {
            return 1;
        }
    }

    function clientToLocal(inner, clientX, clientY) {
        var rect = inner.getBoundingClientRect();
        var dx = clientX - (rect.left + rect.width / 2);
        var dy = clientY - (rect.top + rect.height / 2);
        var rot = parseInt(getRotation(), 10) || 0;
        var rad = -rot * Math.PI / 180;
        var cos = Math.cos(rad);
        var sin = Math.sin(rad);
        var lx = dx * cos - dy * sin;
        var ly = dx * sin + dy * cos;
        var rotScale = 1;
        if (rot === 90 || rot === 270) {
            var lw = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--label-w'));
            var lh = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--label-h'));
            if (lw) rotScale = lh / lw;
        }
        var scale = zoomScale() * (rotScale || 1);
        if (!scale) scale = 1;
        return {
            x: inner.clientWidth / 2 + lx / scale,
            y: inner.clientHeight / 2 + ly / scale
        };
    }

    function previewLabel() {
        return document.querySelector('.live-preview-zoom .label');
    }

    function applyKey(key) {
        var item = layout[key] || {};
        var placed = item.x != null && item.y != null && item.x !== '' && item.y !== '';
        document.querySelectorAll('[data-el="' + key + '"]').forEach(function (el) {
            if (placed) {
                el.setAttribute('data-placed', '1');
                el.style.position = 'absolute';
                el.style.left = item.x + '%';
                el.style.top = item.y + '%';
                el.style.margin = '0';
                el.style.zIndex = '2';
                el.style.overflow = 'hidden';
                el.style.maxWidth = Math.max(8, 100 - (parseFloat(item.x) || 0)) + '%';
                if (key === 'barcode') {
                    var barW = getComputedStyle(document.documentElement).getPropertyValue('--barcode-w').trim();
                    var barH = getComputedStyle(document.documentElement).getPropertyValue('--barcode-h').trim();
                    if (barW) el.style.width = barW;
                    if (barH) el.style.height = barH;
                }
            } else {
                el.removeAttribute('data-placed');
                el.style.position = '';
                el.style.left = '';
                el.style.top = '';
                el.style.margin = '';
                el.style.zIndex = '';
                el.style.maxWidth = '';
                el.style.overflow = '';
                if (key === 'barcode') {
                    el.style.width = '';
                    el.style.height = '';
                }
            }
            el.style.fontSize = item.size ? item.size + 'px' : '';
            el.style.fontWeight = item.weight ? String(item.weight) : '';
            el.style.textAlign = item.align ? item.align : '';
        });
    }

    function applyAll() {
        ORDER.forEach(applyKey);
    }

    function readLayoutField() {
        var field = document.getElementById('element_layout');
        if (!field) return;
        try {
            layout = JSON.parse(field.value || '{}') || {};
        } catch (e) {
            layout = {};
        }
        if (!layout || typeof layout !== 'object' || Array.isArray(layout)) layout = {};
    }

    function rows() {
        var seen = {};
        var list = [];
        document.querySelectorAll('.sheet .label').forEach(function (lab) {
            var row = lab.getAttribute('data-row');
            if (row == null || seen[row]) return;
            seen[row] = true;
            list.push(lab);
        });
        return list;
    }

    function fillRowSelect() {
        var sel = document.getElementById('editor_row');
        if (!sel) return;
        var html = '';
        rows().forEach(function (lab, index) {
            var row = lab.getAttribute('data-row');
            var nameNode = lab.querySelector('[data-el="name"]');
            var name = nameNode ? nameNode.textContent.replace(/\s+/g, ' ').trim() : '';
            if (!name) name = 'Label ' + (index + 1);
            if (name.length > 48) name = name.slice(0, 46) + '…';
            html += '<option value="' + esc(row) + '">' + esc(name) + '</option>';
        });
        sel.innerHTML = html || '<option value="0">Label</option>';
        if (activeRow) sel.value = activeRow;
    }

    function fillElementSelect() {
        var sel = document.getElementById('editor_pick');
        if (!sel) return;
        var html = '<option value="">—</option>';
        ORDER.forEach(function (key) {
            html += '<option value="' + key + '">' + esc(captions[key] || key) + '</option>';
        });
        sel.innerHTML = html;
        sel.value = selectedKey || '';
    }

    function showPreview(row) {
        var src = document.querySelector('.sheet .label[data-row="' + row + '"]');
        var zoom = document.querySelector('.live-preview-zoom');
        if (!src || !zoom) return;
        var clone = src.cloneNode(true);
        clone.querySelectorAll('[contenteditable]').forEach(function (node) {
            node.removeAttribute('contenteditable');
        });
        clone.querySelectorAll('.is-selected').forEach(function (node) {
            node.classList.remove('is-selected');
        });
        zoom.textContent = '';
        zoom.appendChild(clone);
        activeRow = String(row);
        fitPreview();
    }

    function previewNode(key) {
        var label = previewLabel();
        return label ? label.querySelector('[data-el="' + key + '"]') : null;
    }

    function shown(el, key) {
        if (!el) return false;
        if (key === 'price' || key === 'costcode') return el.style.visibility !== 'hidden';
        return el.style.display !== 'none';
    }

    function optionList(kind) {
        var label = document.querySelector('.sheet .label[data-row="' + activeRow + '"]') || previewLabel();
        if (!label) return [];
        var raw = label.getAttribute(kind === 'size' ? 'data-sizes' : 'data-colors') || '[]';
        try {
            var list = JSON.parse(raw);
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    function setNote(text) {
        var note = document.getElementById('editor_note');
        if (note) note.textContent = text || '';
    }

    function paintChoice(kind, value) {
        var caption = captions[kind] || kind;
        document.querySelectorAll('.label').forEach(function (lab) {
            if (String(lab.getAttribute('data-row')) !== String(activeRow)) return;
            lab.setAttribute(kind === 'size' ? 'data-size' : 'data-color', value);
            var node = lab.querySelector('[data-el="' + kind + '"]');
            if (node) node.textContent = value ? (caption + ': ' + value) : '';
            var line = lab.querySelector('.attr-line');
            if (!line) return;
            var sizeOn = (lab.getAttribute('data-size') || '') !== '' && lab.querySelector('[data-el="size"]') && lab.querySelector('[data-el="size"]').style.display !== 'none';
            var colorOn = (lab.getAttribute('data-color') || '') !== '' && lab.querySelector('[data-el="color"]') && lab.querySelector('[data-el="color"]').style.display !== 'none';
            if (lab.querySelector('[data-el="size"][data-placed="1"], [data-el="color"][data-placed="1"]')) return;
            line.style.display = (sizeOn || colorOn) ? '' : 'none';
        });
    }

    function writeText(key, text, source) {
        text = String(text == null ? '' : text).replace(/[\r\n]+/g, ' ');
        if (key !== 'price') text = text.replace(/\s+/g, ' ').trim();
        else text = text.trim();
        document.querySelectorAll('.label').forEach(function (lab) {
            if (key !== 'biz' && String(lab.getAttribute('data-row')) !== String(activeRow)) return;
            if (key === 'barcode') {
                lab.setAttribute('data-barcode', text);
                lab.setAttribute('data-barcode-custom', '1');
                return;
            }
            if (key === 'sku') lab.setAttribute('data-sku', text);
            var node = lab.querySelector('[data-el="' + key + '"]');
            if (!node || node === source) return;
            node.textContent = text;
        });
        if (key === 'sku') {
            document.querySelectorAll('.label').forEach(function (lab) {
                if (String(lab.getAttribute('data-row')) !== String(activeRow)) return;
                if (lab.getAttribute('data-barcode-custom') === '1') return;
                lab.setAttribute('data-barcode', text);
            });
            if (window.qposRefreshLabelBarcodes) window.qposRefreshLabelBarcodes();
        }
        if (key === 'barcode' && window.qposRefreshLabelBarcodes) window.qposRefreshLabelBarcodes();
        if (key === 'biz') {
            var business = document.getElementById('opt_business_name');
            if (business && business.value !== text) business.value = text;
        }
        var editorText = document.getElementById('editor_text');
        if (editorText && document.activeElement !== editorText && selectedKey === key) editorText.value = text;
        if (key === 'name') fillRowSelect();
    }

    function panelField(name) {
        var nodes = document.querySelectorAll('#browser_label_opts [name="' + name + '"]');
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].type !== 'hidden') return nodes[i];
        }
        return null;
    }

    function pushPanel(name, value) {
        var field = panelField(name);
        if (!field || String(field.value) === String(value)) return;
        silent = true;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        silent = false;
    }

    function setStyle(key, prop, value) {
        layout[key] = layout[key] || {};
        if (value === '' || value == null) delete layout[key][prop];
        else layout[key][prop] = prop === 'size' ? parseInt(value, 10) : value;
        applyKey(key);
        if (prop === 'size' && FONT_FIELDS[key]) pushPanel(FONT_FIELDS[key], value);
        if (prop === 'weight' && WEIGHT_FIELDS[key] && value) pushPanel(WEIGHT_FIELDS[key], value);
    }

    function toggleShow(key, want) {
        var box = document.querySelector('.live-toggle[data-target="' + SHOW[key] + '"]');
        if (!box || box.checked === want) return;
        box.checked = want;
        box.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function selectKey(key) {
        selectedKey = key || '';
        var pick = document.getElementById('editor_pick');
        if (pick) pick.value = selectedKey;
        document.querySelectorAll('.live-preview-zoom .is-selected').forEach(function (node) {
            node.classList.remove('is-selected');
        });
        var node = previewNode(key);
        if (node) node.classList.add('is-selected');
        var textWrap = document.getElementById('editor_text_wrap');
        var choiceWrap = document.getElementById('editor_choice_wrap');
        var text = document.getElementById('editor_text');
        var choice = document.getElementById('editor_choice');
        var show = document.getElementById('editor_show');
        var size = document.getElementById('editor_font_size');
        var weight = document.getElementById('editor_weight');
        var align = document.getElementById('editor_align');
        var item = layout[key] || {};
        if (size) size.value = item.size || (node ? parseInt(getComputedStyle(node).fontSize, 10) || '' : '');
        if (weight) weight.value = item.weight || '';
        if (align) align.value = item.align || '';
        if (show) show.checked = !!(node && shown(node, key));
        var structured = key === 'size' || key === 'color';
        if (textWrap) textWrap.hidden = !TEXT_KEYS[key];
        if (choiceWrap) choiceWrap.hidden = !structured;
        if (structured && choice) {
            var list = optionList(key);
            var current = '';
            var label = document.querySelector('.sheet .label[data-row="' + activeRow + '"]');
            if (label) current = label.getAttribute(key === 'size' ? 'data-size' : 'data-color') || '';
            var html = '';
            list.forEach(function (opt) {
                html += '<option value="' + esc(opt) + '"' + (opt === current ? ' selected' : '') + '>' + esc(opt) + '</option>';
            });
            choice.innerHTML = html;
            document.getElementById('editor_choice_label').textContent = captions[key] || key;
            setNote(list.length ? notes.choice : notes.none);
        } else if (key === 'barcode' && text) {
            var lab = document.querySelector('.sheet .label[data-row="' + activeRow + '"]');
            text.value = lab ? (lab.getAttribute('data-barcode') || '') : '';
            text.maxLength = 80;
            document.getElementById('editor_text_label').textContent = captions.barcode || 'Barcode';
            setNote(notes.move);
        } else if (TEXT_KEYS[key] && text && node) {
            text.value = node.textContent.replace(/\s+/g, ' ').trim();
            text.maxLength = key === 'price' || key === 'costcode' ? 40 : (key === 'sku' ? 80 : 120);
            document.getElementById('editor_text_label').textContent = captions[key] || key;
            setNote(key === 'price' ? notes.price : notes.move);
        } else {
            setNote(notes.move);
        }
    }

    function clampKey(key) {
        var el = previewNode(key);
        var inner = el && el.closest('.label-inner');
        var item = layout[key];
        if (!el || !inner || !item || item.x == null || item.y == null) return;
        if (!inner.clientWidth || !inner.clientHeight) return;
        var maxX = Math.max(0, 100 - (Math.min(el.scrollWidth, inner.clientWidth) / inner.clientWidth * 100));
        var maxY = Math.max(0, 100 - (Math.min(el.scrollHeight, inner.clientHeight) / inner.clientHeight * 100));
        item.x = Math.round(Math.max(0, Math.min(maxX, parseFloat(item.x))) * 100) / 100;
        item.y = Math.round(Math.max(0, Math.min(maxY, parseFloat(item.y))) * 100) / 100;
        applyKey(key);
    }

    function place(key, localX, localY, width, height, inner) {
        var maxX = Math.max(0, inner.clientWidth - width);
        var maxY = Math.max(0, inner.clientHeight - height);
        var x = Math.max(0, Math.min(localX, maxX));
        var y = Math.max(0, Math.min(localY, maxY));
        layout[key] = layout[key] || {};
        layout[key].x = Math.round((inner.clientWidth ? x / inner.clientWidth * 100 : 0) * 100) / 100;
        layout[key].y = Math.round((inner.clientHeight ? y / inner.clientHeight * 100 : 0) * 100) / 100;
        applyKey(key);
        clampKey(key);
    }

    function overlaps(el) {
        var inner = el.closest('.label-inner');
        if (!inner) return false;
        var mine = el.getBoundingClientRect();
        var hit = false;
        inner.querySelectorAll('[data-el]').forEach(function (other) {
            if (hit || other === el || !shown(other, other.getAttribute('data-el'))) return;
            if ((other.textContent || '').trim() === '' && other.getAttribute('data-el') !== 'barcode') return;
            var rect = other.getBoundingClientRect();
            if (mine.left < rect.right - 2 && mine.right > rect.left + 2 && mine.top < rect.bottom - 2 && mine.bottom > rect.top + 2) {
                hit = true;
            }
        });
        return hit;
    }

    function separate(el) {
        var key = el.getAttribute('data-el');
        var guard = 0;
        while (overlaps(el) && guard++ < 4) {
            var item = layout[key];
            if (!item) return;
            var top = parseFloat(item.y) || 0;
            var left = parseFloat(item.x) || 0;
            if (top <= 70) item.y = Math.round((top + 3) * 100) / 100;
            else if (left <= 70) item.x = Math.round((left + 3) * 100) / 100;
            else break;
            clampKey(key);
        }
        clampKey(key);
    }

    function onPointerDown(event) {
        var zoom = document.querySelector('.live-preview-zoom');
        if (!zoom || !zoom.contains(event.target)) return;
        var el = event.target.closest('[data-el]');
        if (!el) return;
        if (el.getAttribute('contenteditable') === 'true') return;
        selectKey(el.getAttribute('data-el'));
        var inner = el.closest('.label-inner');
        if (!inner) return;
        var local = clientToLocal(inner, event.clientX, event.clientY);
        drag = {
            key: el.getAttribute('data-el'),
            startX: event.clientX,
            startY: event.clientY,
            ox: local.x - el.offsetLeft,
            oy: local.y - el.offsetTop,
            moved: false,
            pointerId: event.pointerId
        };
        if (zoom.setPointerCapture) {
            try { zoom.setPointerCapture(event.pointerId); } catch (e) {}
        }
    }

    function onPointerMove(event) {
        if (!drag || event.pointerId !== drag.pointerId) return;
        if (!drag.moved && Math.abs(event.clientX - drag.startX) + Math.abs(event.clientY - drag.startY) < 5) return;
        drag.moved = true;
        var el = previewNode(drag.key);
        if (!el) return;
        var inner = el.closest('.label-inner');
        if (!inner) return;
        var local = clientToLocal(inner, event.clientX, event.clientY);
        place(drag.key, local.x - drag.ox, local.y - drag.oy, el.offsetWidth, el.offsetHeight, inner);
        event.preventDefault();
    }

    function onPointerUp(event) {
        if (!drag || event.pointerId !== drag.pointerId) return;
        var moved = drag.moved;
        var key = drag.key;
        drag = null;
        if (!moved) return;
        var el = previewNode(key);
        if (el) separate(el);
    }

    function bind() {
        var zoom = document.querySelector('.live-preview-zoom');
        if (zoom) {
            zoom.addEventListener('pointerdown', onPointerDown);
            zoom.addEventListener('pointermove', onPointerMove);
            zoom.addEventListener('pointerup', onPointerUp);
            zoom.addEventListener('pointercancel', onPointerUp);
            zoom.addEventListener('dblclick', function (event) {
                var el = event.target.closest('[data-el]');
                if (!el) return;
                var key = el.getAttribute('data-el');
                if (!TEXT_KEYS[key] || key === 'barcode') {
                    var field = document.getElementById(key === 'size' || key === 'color' ? 'editor_choice' : 'editor_text');
                    if (field) field.focus();
                    return;
                }
                el.setAttribute('contenteditable', 'true');
                el.focus();
            });
            zoom.addEventListener('input', function (event) {
                var el = event.target.closest ? event.target.closest('[data-el]') : null;
                if (!el || el.getAttribute('contenteditable') !== 'true') return;
                writeText(el.getAttribute('data-el'), el.textContent, el);
            });
            zoom.addEventListener('focusout', function (event) {
                var el = event.target.closest ? event.target.closest('[data-el]') : null;
                if (el) el.removeAttribute('contenteditable');
            });
        }
        var rowSel = document.getElementById('editor_row');
        if (rowSel) rowSel.addEventListener('change', function () {
            showPreview(rowSel.value);
            selectKey(selectedKey);
        });
        var pick = document.getElementById('editor_pick');
        if (pick) pick.addEventListener('change', function () { selectKey(pick.value); });
        var text = document.getElementById('editor_text');
        if (text) {
            text.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') event.preventDefault();
            });
            text.addEventListener('input', function () {
                if (silent || !selectedKey) return;
                writeText(selectedKey, text.value, null);
            });
        }
        var choice = document.getElementById('editor_choice');
        if (choice) choice.addEventListener('change', function () {
            if (selectedKey === 'size' || selectedKey === 'color') paintChoice(selectedKey, choice.value);
        });
        var size = document.getElementById('editor_font_size');
        if (size) size.addEventListener('input', function () {
            if (silent || !selectedKey) return;
            setStyle(selectedKey, 'size', size.value);
        });
        var weight = document.getElementById('editor_weight');
        if (weight) weight.addEventListener('change', function () {
            if (silent || !selectedKey) return;
            setStyle(selectedKey, 'weight', weight.value);
        });
        var align = document.getElementById('editor_align');
        if (align) align.addEventListener('change', function () {
            if (silent || !selectedKey) return;
            setStyle(selectedKey, 'align', align.value);
        });
        var show = document.getElementById('editor_show');
        if (show) show.addEventListener('change', function () {
            if (selectedKey) toggleShow(selectedKey, show.checked);
        });
        var reset = document.getElementById('editor_reset');
        if (reset) reset.addEventListener('click', function () {
            if (!selectedKey || !layout[selectedKey]) return;
            delete layout[selectedKey].x;
            delete layout[selectedKey].y;
            applyKey(selectedKey);
        });
        document.addEventListener('keydown', function (event) {
            if (!selectedKey) return;
            if (event.target && (event.target.closest && event.target.closest('input, select, textarea, [contenteditable="true"]'))) return;
            var item = layout[selectedKey] || {};
            var x = item.x != null ? parseFloat(item.x) : null;
            var y = item.y != null ? parseFloat(item.y) : null;
            if (x == null || y == null) {
                var current = previewNode(selectedKey);
                var box = current && current.closest('.label-inner');
                if (!current || !box || !box.clientWidth || !box.clientHeight) return;
                x = current.offsetLeft / box.clientWidth * 100;
                y = current.offsetTop / box.clientHeight * 100;
            }
            var step = event.shiftKey ? 5 : 2;
            if (event.key === 'ArrowLeft') x -= step;
            else if (event.key === 'ArrowRight') x += step;
            else if (event.key === 'ArrowUp') y -= step;
            else if (event.key === 'ArrowDown') y += step;
            else return;
            event.preventDefault();
            layout[selectedKey] = item;
            layout[selectedKey].x = Math.max(0, Math.min(100, Math.round(x * 100) / 100));
            layout[selectedKey].y = Math.max(0, Math.min(100, Math.round(y * 100) / 100));
            applyKey(selectedKey);
        });
        document.querySelectorAll('.live-toggle').forEach(function (box) {
            box.addEventListener('change', function () {
                if (!selectedKey || SHOW[selectedKey] !== box.getAttribute('data-target')) return;
                var showBox = document.getElementById('editor_show');
                if (showBox) showBox.checked = box.checked;
            });
        });
        var layoutField = document.getElementById('element_layout');
        if (layoutField) layoutField.addEventListener('input', function () {
            if (silent) return;
            readLayoutField();
            applyAll();
        });
        var business = document.getElementById('opt_business_name');
        if (business) business.addEventListener('input', function () {
            if (selectedKey === 'biz') {
                var text = document.getElementById('editor_text');
                if (text && document.activeElement !== text) text.value = business.value;
            }
        });
    }

    function collectOverrides() {
        var out = {};
        rows().forEach(function (lab) {
            var row = lab.getAttribute('data-row');
            function textOf(key) {
                var node = lab.querySelector('[data-el="' + key + '"]');
                return node ? node.textContent.replace(/\s+/g, ' ').trim() : '';
            }
            out[row] = {
                name: textOf('name'),
                sku: lab.getAttribute('data-sku') || textOf('sku'),
                price_text: textOf('price'),
                costcode: textOf('costcode'),
                brand: textOf('brand'),
                category: textOf('category'),
                size: lab.getAttribute('data-size') || '',
                color: lab.getAttribute('data-color') || '',
                barcode_value: lab.getAttribute('data-barcode') || ''
            };
        });
        return out;
    }

    window.QposLabelEditor = {
        init: function (options) {
            options = options || {};
            captions = options.captions || {};
            notes = options.notes || {};
            if (typeof options.getRotation === 'function') getRotation = options.getRotation;
            if (typeof options.fitPreview === 'function') fitPreview = options.fitPreview;
            readLayoutField();
            applyAll();
            bind();
            var first = rows()[0];
            activeRow = first ? String(first.getAttribute('data-row')) : '0';
            fillRowSelect();
            fillElementSelect();
            showPreview(activeRow);
            ready = true;
        },
        noteFont: function (cssVar, px) {
            if (!ready || silent) return;
            var key = FONT_KEYS[cssVar];
            if (!key) return;
            layout[key] = layout[key] || {};
            layout[key].size = px;
            applyKey(key);
            if (selectedKey === key) {
                silent = true;
                var input = document.getElementById('editor_font_size');
                if (input) input.value = px;
                silent = false;
            }
        },
        noteWeight: function (cssVar, weight) {
            if (!ready || silent) return;
            var key = WEIGHT_KEYS[cssVar];
            if (!key) return;
            layout[key] = layout[key] || {};
            layout[key].weight = weight;
            applyKey(key);
            if (selectedKey === key) {
                silent = true;
                var input = document.getElementById('editor_weight');
                if (input) input.value = weight;
                silent = false;
            }
        },
        clearPositions: function () {
            Object.keys(layout).forEach(function (key) {
                if (!layout[key]) return;
                delete layout[key].x;
                delete layout[key].y;
                applyKey(key);
            });
        },
        syncFields: function () {
            var field = document.getElementById('element_layout');
            if (field) field.value = JSON.stringify(layout || {});
            var overrides = document.getElementById('label_overrides');
            if (overrides) overrides.value = JSON.stringify(collectOverrides());
        },
        preparePrint: function () {
            document.querySelectorAll('.is-selected').forEach(function (node) {
                node.classList.remove('is-selected');
            });
            document.querySelectorAll('[contenteditable]').forEach(function (node) {
                node.removeAttribute('contenteditable');
            });
            selectedKey = '';
        },
        layout: function () { return layout; },
        overrides: function () { return collectOverrides(); },
        activeRow: function () { return activeRow; }
    };
})(window);

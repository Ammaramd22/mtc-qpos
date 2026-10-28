/**
 * Hardware VFD / LED customer display via Web Serial API.
 * Shows cart total (and change when paying) on rear-mount / pole displays.
 */
(function (window, $) {
    'use strict';

    var port = null;
    var writer = null;
    var writeQueue = Promise.resolve();
    var lastPayload = '';
    var reconnectTried = false;

    function cfg() {
        var $el = $('#vfd_display_config');
        if (!$el.length) {
            return null;
        }
        return {
            enabled: $el.data('enabled') == 1 || $el.data('enabled') === true || $el.data('enabled') === '1',
            protocol: String($el.data('protocol') || 'numeric'),
            baud: parseInt($el.data('baud') || 9600, 10) || 9600,
            chars: parseInt($el.data('chars') || 8, 10) || 8,
        };
    }

    function supported() {
        return !!(navigator.serial && typeof navigator.serial.requestPort === 'function');
    }

    function padRight(str, len) {
        str = String(str || '');
        if (str.length > len) {
            return str.substring(0, len);
        }
        while (str.length < len) {
            str += ' ';
        }
        return str;
    }

    function padLeft(str, len) {
        str = String(str || '');
        if (str.length > len) {
            return str.substring(str.length - len);
        }
        while (str.length < len) {
            str = ' ' + str;
        }
        return str;
    }

    function formatAmount(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) {
            n = 0;
        }
        return n.toFixed(2);
    }

    function encodeBytes(parts) {
        var bytes = [];
        for (var i = 0; i < parts.length; i++) {
            var part = parts[i];
            if (typeof part === 'number') {
                bytes.push(part & 0xff);
            } else if (part instanceof Uint8Array) {
                for (var j = 0; j < part.length; j++) {
                    bytes.push(part[j]);
                }
            } else {
                var s = String(part);
                for (var k = 0; k < s.length; k++) {
                    bytes.push(s.charCodeAt(k) & 0xff);
                }
            }
        }
        return new Uint8Array(bytes);
    }

    function buildPayload(line1, line2, amountOnly) {
        var c = cfg() || {};
        var width = Math.max(4, Math.min(40, c.chars || 8));
        var protocol = c.protocol || 'numeric';
        var l1 = padRight(line1 || '', width);
        var l2 = padRight(line2 || '', width);
        var amount = padLeft(amountOnly || '0.00', width);

        if (protocol === 'numeric') {
            // Clear + amount — works for many USB rear LED / 7-seg style units
            return encodeBytes([0x0c, amount, '\r']);
        }

        if (protocol === 'epson') {
            // ESC @ init, then ESC Q A / home-ish clear + lines
            return encodeBytes([
                0x1b, 0x40,
                0x0c,
                l1, '\r\n',
                l2, '\r\n',
            ]);
        }

        if (protocol === 'cd7220') {
            // Partner CD7220: CLS then text
            return encodeBytes(['CLS\r\n', l1, '\r\n', l2, '\r\n']);
        }

        // generic 2-line ASCII
        return encodeBytes([0x0c, l1, '\r\n', l2, '\r\n']);
    }

    function setButtonState(connected) {
        var $btn = $('#vfd_customer_display_btn');
        if (!$btn.length) {
            return;
        }
        $btn.toggleClass('is-connected', !!connected);
        $btn.attr('title', connected
            ? (LANG.vfd_connected || 'VFD connected')
            : (LANG.vfd_connect || 'Connect VFD'));
        $btn.find('.vfd-btn-label').text(connected
            ? (LANG.vfd_connected || 'VFD connected')
            : (LANG.vfd_connect || 'Connect VFD'));
    }

    async function openPort(selectedPort) {
        var c = cfg();
        if (!c) {
            return false;
        }

        await selectedPort.open({
            baudRate: c.baud,
            dataBits: 8,
            stopBits: 1,
            parity: 'none',
            flowControl: 'none',
        });

        port = selectedPort;
        writer = port.writable.getWriter();

        port.addEventListener('disconnect', function () {
            port = null;
            writer = null;
            lastPayload = '';
            setButtonState(false);
        });

        setButtonState(true);
        await writeRaw(buildPayload('TOTAL', formatAmount(0), formatAmount(0)));
        return true;
    }

    function writeRaw(bytes) {
        if (!writer) {
            return Promise.resolve();
        }
        writeQueue = writeQueue.then(function () {
            return writer.write(bytes);
        }).catch(function () {
            // ignore write errors (device unplugged mid-write)
        });
        return writeQueue;
    }

    async function connect(interactive) {
        var c = cfg();
        if (!c || !c.enabled) {
            return false;
        }
        if (!supported()) {
            if (interactive && typeof toastr !== 'undefined') {
                toastr.error(LANG.vfd_not_supported || 'Web Serial not supported');
            }
            return false;
        }
        if (port && writer) {
            return true;
        }

        try {
            var selected = null;
            if (interactive) {
                selected = await navigator.serial.requestPort();
            } else {
                var ports = await navigator.serial.getPorts();
                if (ports && ports.length) {
                    selected = ports[0];
                }
            }
            if (!selected) {
                return false;
            }
            await openPort(selected);
            if (interactive && typeof toastr !== 'undefined') {
                toastr.success(LANG.vfd_connected || 'VFD connected');
            }
            refreshFromPos();
            return true;
        } catch (e) {
            port = null;
            writer = null;
            setButtonState(false);
            if (interactive && typeof toastr !== 'undefined') {
                toastr.error(LANG.vfd_connect_failed || 'Could not open port');
            }
            return false;
        }
    }

    async function disconnect() {
        try {
            if (writer) {
                writer.releaseLock();
            }
        } catch (e) {}
        writer = null;
        try {
            if (port) {
                await port.close();
            }
        } catch (e) {}
        port = null;
        lastPayload = '';
        setButtonState(false);
        if (typeof toastr !== 'undefined') {
            toastr.info(LANG.vfd_disconnected || 'VFD disconnected');
        }
    }

    function showAmount(total, change) {
        var c = cfg();
        if (!c || !c.enabled || !writer) {
            return;
        }

        var totalStr = formatAmount(total);
        var changeNum = parseFloat(change);
        var hasChange = !isNaN(changeNum) && changeNum > 0;
        var line1 = hasChange ? 'CHANGE' : 'TOTAL';
        var line2 = hasChange ? formatAmount(changeNum) : totalStr;
        var amountOnly = hasChange ? formatAmount(changeNum) : totalStr;

        var bytes = buildPayload(line1, line2, amountOnly);
        var key = Array.prototype.join.call(bytes, ',');
        if (key === lastPayload) {
            return;
        }
        lastPayload = key;
        writeRaw(bytes);
    }

    function refreshFromPos() {
        var total = 0;
        if (typeof __read_number === 'function' && $('input#final_total_input').length) {
            total = __read_number($('input#final_total_input'));
        } else {
            var txt = ($('span#total_payable').first().text() || '0').replace(/[^0-9.\-]/g, '');
            total = parseFloat(txt) || 0;
        }

        var change = 0;
        var $change = $('span.change_return_span, #change_return_span, span.change_return').first();
        if ($change.length) {
            var cTxt = ($change.text() || '0').replace(/[^0-9.\-]/g, '');
            change = parseFloat(cTxt) || 0;
        } else if (typeof __read_number === 'function' && $('input#change_return').length) {
            change = __read_number($('input#change_return'));
        }

        showAmount(total, change);
    }

    function init() {
        var c = cfg();
        if (!c || !c.enabled) {
            return;
        }

        setButtonState(false);

        $('#vfd_customer_display_btn').on('click', function (e) {
            e.preventDefault();
            if (port && writer) {
                disconnect();
            } else {
                connect(true);
            }
        });

        $(document).on('invoice_total_calculated', function () {
            refreshFromPos();
        });

        $(document).on('change keyup', 'input.payment-amount, input#change_return, .payment_types_dropdown', function () {
            setTimeout(refreshFromPos, 50);
        });

        // Auto-reconnect previously permitted port (no picker needed)
        if (!reconnectTried && supported()) {
            reconnectTried = true;
            connect(false);
        }
    }

    window.VsposVfdDisplay = {
        connect: connect,
        disconnect: disconnect,
        showAmount: showAmount,
        refresh: refreshFromPos,
        isConnected: function () {
            return !!(port && writer);
        },
    };

    $(document).ready(init);
})(window, jQuery);

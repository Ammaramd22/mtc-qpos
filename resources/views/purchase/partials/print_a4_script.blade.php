<script type="text/javascript">
(function() {
	if (typeof window.printPurchaseA4 === 'function') {
		return;
	}

	window.printPurchaseA4 = function($root) {
		if (!$root || !$root.length) {
			return;
		}

		var title = ($root.find('.modal-title').first().text() || 'Purchase Details').replace(/\s+/g, ' ').trim();
		var $clone = $root.clone();

		$clone.find('.no-print, .modal-footer, .close, script, .fa-sync').remove();
		$clone.find('.purchase-print-barcode, .print_section').css('display', 'block');

		var bodyHtml = '';
		var $header = $clone.find('.modal-header').first();
		var $body = $clone.find('.modal-body').first();
		if ($header.length) {
			bodyHtml += '<div class="doc-title">' + $header.find('.modal-title').html() + '</div>';
		}
		if ($body.length) {
			bodyHtml += $body.html();
		} else {
			bodyHtml += $clone.html();
		}

		var brand = @json(config('app.name', 'QPOS'));
		var win = window.open('', '_blank', 'width=900,height=1100');
		if (!win) {
			if (typeof toastr !== 'undefined') {
				toastr.error('Please allow popups to print this document.');
			} else {
				alert('Please allow popups to print this document.');
			}
			return;
		}

		win.document.open();
		win.document.write(
			'<!DOCTYPE html><html><head><meta charset="utf-8"><title>' + title + '</title>' +
			'<style>' +
			'@page { size: A4; margin: 14mm 12mm; }' +
			'* { box-sizing: border-box; }' +
			'body { font-family: Arial, Helvetica, sans-serif; color: #111; background: #fff; font-size: 12px; line-height: 1.45; margin: 0; padding: 0; }' +
			'.sheet { width: 100%; max-width: 210mm; margin: 0 auto; }' +
			'.doc-title { font-size: 18px; font-weight: 700; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 2px solid #111; }' +
			'.row { width: 100%; margin: 0 0 10px; overflow: hidden; }' +
			'.col-sm-4 { width: 33%; float: left; padding-right: 8px; }' +
			'.col-md-6, .col-sm-6 { width: 50%; float: left; padding-right: 8px; }' +
			'.col-sm-12, .col-xs-12, .col-md-12 { width: 100%; float: left; }' +
			'address { font-style: normal; margin: 4px 0 0; color: #333; }' +
			'strong, b { color: #000; }' +
			'h4 { font-size: 13px; margin: 12px 0 8px; }' +
			'table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }' +
			'th, td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; color: #111; background: #fff; }' +
			'thead th, tr.bg-green th, .bg-green th { background: #f0f0f0 !important; color: #000 !important; font-weight: 700; font-size: 11px; }' +
			'.text-right { text-align: right; }' +
			'.text-center { text-align: center; }' +
			'.pull-right { float: right; }' +
			'.well, .bg-gray { background: #f7f7f7 !important; border: 1px solid #ddd; padding: 8px 10px; border-radius: 4px; color: #111 !important; }' +
			'.label { display: inline-block; padding: 2px 6px; border: 1px solid #999; border-radius: 3px; font-size: 10px; background: #fff !important; color: #111 !important; }' +
			'.table-responsive { overflow: visible !important; }' +
			'.print_section, .purchase-print-barcode { display: block !important; text-align: center; margin-top: 18px; }' +
			'.print_section img, .purchase-print-barcode img { max-width: 280px; }' +
			'.doc-footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 10px; color: #666; text-align: center; }' +
			'@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }' +
			'</style></head><body>' +
			'<div class="sheet">' + bodyHtml +
			'<div class="doc-footer">' + brand + ' &mdash; ' + title + '</div>' +
			'</div></body></html>'
		);
		win.document.close();
		win.focus();
		setTimeout(function() {
			win.print();
			win.close();
		}, 400);
	};
})();
</script>

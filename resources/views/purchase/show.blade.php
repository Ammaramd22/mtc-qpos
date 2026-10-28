<div class="modal-dialog modal-xl" role="document">
  <div class="modal-content purchase-print-root">
    @include('purchase.partials.show_details')
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" id="btn_print_purchase" aria-label="Print">
        <i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

@include('purchase.partials.print_a4_script')

<script type="text/javascript">
	$(document).ready(function(){
		var element = $('div.modal-xl');
		__currency_convert_recursively(element);

		$('#btn_print_purchase').off('click').on('click', function() {
			window.printPurchaseA4($(this).closest('.purchase-print-root'));
		});
	});
</script>

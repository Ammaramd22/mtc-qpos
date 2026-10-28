<div class="modal fade vspos-pos-modal" tabindex="-1" role="dialog" id="weighing_scale_modal">
	<div class="modal-dialog" role="document">
		<div class="modal-content vspos-pos-modal-content">
			<div class="modal-header vspos-pos-modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">@lang('lang_v1.weighing_scale')</h4>
			</div>
			<div class="modal-body vspos-pos-modal-body">
				<div class="row">
					<div class="col-xs-12">
				        <div class="form-group">
				            {!! Form::label('weighing_scale_barcode', __('lang_v1.weighing_scale_barcode') . ':' ) !!} @show_tooltip(__('lang_v1.weighing_scale_barcode_help'))
				            {!! Form::text('weighing_scale_barcode', null, ['class' => 'form-control']); !!}
				        </div>
				    </div>
				</div>
			</div>
			<div class="modal-footer vspos-pos-modal-footer">
				<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="weighing_scale_submit">@lang('messages.submit')</button>
			    <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal fade vspos-pos-modal" tabindex="-1" role="dialog" id="confirmSuspendModal">
	<div class="modal-dialog" role="document">
		<div class="modal-content vspos-pos-modal-content">
			<div class="modal-header vspos-pos-modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">@lang('lang_v1.suspend_sale')</h4>
			</div>
			<div class="modal-body vspos-pos-modal-body">
				<div class="row">
					<div class="col-xs-12">
				        <div class="form-group">
				            {!! Form::label('additional_notes', __('lang_v1.suspend_note') . ':' ) !!}
				            {!! Form::textarea('additional_notes', !empty($transaction->additional_notes) ? $transaction->additional_notes : null, ['class' => 'form-control','rows' => '4']); !!}
				            {!! Form::hidden('is_suspend', 0, ['id' => 'is_suspend']); !!}
				        </div>
				    </div>
				</div>
			</div>
			<div class="modal-footer vspos-pos-modal-footer">
				<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-suspend">@lang('messages.save')</button>
			    <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
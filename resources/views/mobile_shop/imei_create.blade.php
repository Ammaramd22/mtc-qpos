<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'imeiStore']), 'method' => 'post', 'id' => 'mobile_imei_add_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.add_imei')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('product_id', __('product.product_name') . ':') !!}
                        {!! Form::select('product_id', $products->pluck('name', 'id'), null, ['class' => 'form-control select2', 'required', 'id' => 'imei_product_id']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('variation_id', __('product.variation') . ':') !!}
                        {!! Form::select('variation_id', [], null, ['class' => 'form-control', 'required', 'id' => 'imei_variation_id']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('business.location') . ':') !!}
                        {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="vspos-imei-bulk">
                <div class="vspos-imei-bulk-head">
                    <strong><i class="fa fa-barcode"></i> @lang('lang_v1.imei_numbers')</strong>
                    <span class="text-muted" id="vspos_imei_line_count">1 @lang('lang_v1.imei_lines')</span>
                </div>

                <div id="vspos_imei_lines">
                    <div class="vspos-imei-line">
                        <span class="vspos-imei-line-num">1</span>
                        <input type="text" name="imeis[]" class="form-control vspos-imei-input" maxlength="20" placeholder="35xxxxxxxxxxxxxx" required autocomplete="off">
                        <button type="button" class="btn btn-danger btn-sm vspos-imei-remove" title="@lang('messages.remove')" disabled>
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="vspos-imei-bulk-actions">
                    <button type="button" class="btn btn-default btn-sm" id="vspos_imei_add_line">
                        <i class="fa fa-plus"></i> @lang('lang_v1.add_imei_line')
                    </button>
                    <div class="vspos-imei-add-many">
                        <input type="number" id="vspos_imei_add_count" class="form-control input-sm" min="1" max="50" value="10" title="@lang('lang_v1.add_n_imei_lines')">
                        <button type="button" class="btn btn-primary btn-sm" id="vspos_imei_add_n">
                            <i class="fa fa-list"></i> @lang('lang_v1.add_n_lines')
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px;margin-bottom:0;">
                    <label>@lang('lang_v1.paste_imeis') <small class="text-muted">(@lang('lang_v1.paste_imeis_help'))</small></label>
                    <textarea id="vspos_imei_paste" class="form-control" rows="3" placeholder="351234567890101&#10;351234567890102&#10;351234567890103"></textarea>
                    <button type="button" class="btn btn-default btn-sm" id="vspos_imei_apply_paste" style="margin-top:6px;">
                        <i class="fa fa-paste"></i> @lang('lang_v1.apply_pasted_imeis')
                    </button>
                </div>
            </div>

            <div class="form-group" style="margin-top:12px;margin-bottom:0;">
                {!! Form::label('notes', __('brand.note') . ':') !!}
                {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('lang_v1.optional')]) !!}
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="mobile_imei_save_btn">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<style>
.vspos-imei-bulk {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px;
    background: #fafafa;
}
.vspos-imei-bulk-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.vspos-imei-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.vspos-imei-line-num {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #e2e8f0;
    color: #334155;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.vspos-imei-bulk-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
}
.vspos-imei-add-many {
    display: flex;
    align-items: center;
    gap: 6px;
}
.vspos-imei-add-many input {
    width: 70px;
}
body.lockscreen .vspos-imei-bulk {
    background: #161616;
    border-color: #333;
}
body.lockscreen .vspos-imei-line-num {
    background: #333;
    color: #f5f5f5;
}
</style>

<script>
(function () {
    @php
        $imei_products_json = $products->map(function ($p) {
            $variations = [];
            foreach ($p->variations as $v) {
                $label = trim(($v->name && $v->name !== 'DUMMY' ? $v->name.' · ' : '').($v->sub_sku ?? ''));
                $variations[(string) $v->id] = $label !== '' ? $label : ('#'.$v->id);
            }

            return [
                'id' => $p->id,
                'variations' => $variations,
            ];
        })->values();
    @endphp
    var products = @json($imei_products_json);

    var $lines = $('#vspos_imei_lines');

    function renumberImeiLines() {
        var $rows = $lines.find('.vspos-imei-line');
        $rows.each(function (i) {
            $(this).find('.vspos-imei-line-num').text(i + 1);
        });
        $rows.find('.vspos-imei-remove').prop('disabled', $rows.length <= 1);
        $('#vspos_imei_line_count').text($rows.length + ' {{ __("lang_v1.imei_lines") }}');
    }

    function addImeiLine(value) {
        var html =
            '<div class="vspos-imei-line">' +
            '<span class="vspos-imei-line-num"></span>' +
            '<input type="text" name="imeis[]" class="form-control vspos-imei-input" maxlength="20" placeholder="35xxxxxxxxxxxxxx" autocomplete="off" value="' +
            $('<div>').text(value || '').html() +
            '">' +
            '<button type="button" class="btn btn-danger btn-sm vspos-imei-remove" title="{{ __("messages.remove") }}"><i class="fa fa-times"></i></button>' +
            '</div>';
        $lines.append(html);
        renumberImeiLines();
        $lines.find('.vspos-imei-input').last().focus();
    }

    $('#imei_product_id').on('change', function () {
        var pid = $(this).val();
        var $var = $('#imei_variation_id').empty();
        var p = products.find(function (x) { return String(x.id) === String(pid); });
        if (p && p.variations) {
            $.each(p.variations, function (id, label) {
                $var.append($('<option>', { value: id, text: label }));
            });
        }
    }).trigger('change');

    $('#vspos_imei_add_line').on('click', function () {
        addImeiLine('');
    });

    $('#vspos_imei_add_n').on('click', function () {
        var n = parseInt($('#vspos_imei_add_count').val(), 10) || 1;
        n = Math.min(50, Math.max(1, n));
        var emptyFirst = $lines.find('.vspos-imei-input').length === 1 && !$lines.find('.vspos-imei-input').first().val();
        var toAdd = emptyFirst ? n - 1 : n;
        for (var i = 0; i < toAdd; i++) {
            addImeiLine('');
        }
        renumberImeiLines();
        if (emptyFirst) {
            $lines.find('.vspos-imei-input').first().focus();
        }
    });

    $(document).on('click', '#vspos_imei_lines .vspos-imei-remove', function () {
        if ($lines.find('.vspos-imei-line').length <= 1) return;
        $(this).closest('.vspos-imei-line').remove();
        renumberImeiLines();
    });

    $('#vspos_imei_apply_paste').on('click', function () {
        var raw = String($('#vspos_imei_paste').val() || '');
        var list = raw.split(/[\r\n,;\t]+/).map(function (s) {
            return s.trim();
        }).filter(Boolean);
        if (!list.length) {
            toastr.warning('{{ __("lang_v1.paste_imeis_empty") }}');
            return;
        }
        $lines.empty();
        list.forEach(function (imei) {
            addImeiLine(imei);
        });
        $('#vspos_imei_paste').val('');
        toastr.success(list.length + ' {{ __("lang_v1.imei_lines_loaded") }}');
    });

    $(document).on('keydown', '#vspos_imei_lines .vspos-imei-input', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            addImeiLine('');
        }
    });

    renumberImeiLines();

    $('#mobile_imei_add_form').submit(function (e) {
        e.preventDefault();
        var form = $(this);
        var imeis = [];
        form.find('input[name="imeis[]"]').each(function () {
            var v = String($(this).val() || '').trim();
            if (v) imeis.push(v);
        });
        if (!imeis.length) {
            toastr.error('{{ __("lang_v1.imei_required") }}');
            return;
        }

        var $btn = $('#mobile_imei_save_btn').prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            success: function (result) {
                $btn.prop('disabled', false);
                if (result.success) {
                    toastr.success(result.msg);
                    $('.view_modal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    if ($.fn.DataTable.isDataTable('#mobile_imei_table')) {
                        $('#mobile_imei_table').DataTable().ajax.reload();
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    });
})();
</script>

@php
    $size_color_source = null;
    if (isset($product)) {
        $size_color_source = $product;
    } elseif (isset($duplicate_product)) {
        $size_color_source = $duplicate_product;
    }

    $size_color_ready = \App\Utils\ProductSizeColor::tablesReady();
    $business_id = session('user.business_id');

    $selected_size_ids = old('size_ids');
    $linked_size_ids = [];
    if ($size_color_ready && ! empty($size_color_source) && ! empty($size_color_source->id) && \Illuminate\Support\Facades\Schema::hasTable('product_size')) {
        $linked_size_ids = \Illuminate\Support\Facades\DB::table('product_size')
            ->where('product_id', $size_color_source->id)
            ->orderBy('position')
            ->pluck('size_id')
            ->all();
    }
    if ($selected_size_ids === null) {
        $selected_size_ids = $linked_size_ids;
        if (empty($selected_size_ids) && ! empty($size_color_source->size) && ! $size_color_ready) {
            $selected_size_ids = [];
        }
    }

    $size_options = $size_color_ready
        ? \App\Size::forDropdown($business_id, array_merge((array) $selected_size_ids, $linked_size_ids))
        : collect();

    $selected_colors = old('colors');
    if ($selected_colors === null) {
        $selected_colors = [];
        if ($size_color_ready && ! empty($size_color_source) && ! empty($size_color_source->id)) {
            $selected_colors = \App\ProductColor::where('product_id', $size_color_source->id)
                ->orderBy('id')
                ->pluck('name')
                ->all();
        }
        if (empty($selected_colors) && ! empty($size_color_source->color)) {
            $selected_colors = \App\Utils\ProductSizeColor::splitList($size_color_source->color);
        }
    }

    $selected_size_ids = array_map('strval', (array) $selected_size_ids);
    $selected_colors = array_values(array_filter((array) $selected_colors, function ($color) {
        return trim((string) $color) !== '';
    }));
@endphp

@if(! $size_color_ready)
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('size', __('product.size') . ':') !!}
            {!! Form::text('size', old('size', $size_color_source->size ?? null), [
                'class' => 'form-control',
                'placeholder' => __('product.size_placeholder'),
                'maxlength' => 191,
            ]); !!}
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('color', __('product.color') . ':') !!}
            {!! Form::text('color', old('color', $size_color_source->color ?? null), [
                'class' => 'form-control',
                'placeholder' => __('product.color_placeholder'),
                'maxlength' => 191,
            ]); !!}
        </div>
    </div>
@else
    {!! Form::hidden('size_color_managed', 1) !!}
    <div class="col-sm-4">
        <div class="form-group qpos-size-field">
            {!! Form::label('product_size_ids', __('product.size') . ':') !!}
            <div class="input-group">
                <select name="size_ids[]" id="product_size_ids" class="form-control select2" multiple
                    data-placeholder="@lang('product.select_sizes')">
                    @foreach($size_options as $size_id => $size_name)
                        <option value="{{ $size_id }}" @if(in_array((string) $size_id, $selected_size_ids, true)) selected @endif>{{ $size_name }}</option>
                    @endforeach
                </select>
                <span class="input-group-btn">
                    <button type="button" @if(!auth()->user()->can('product.create')) disabled @endif
                        class="btn btn-default bg-white btn-flat btn-modal"
                        data-href="{{ action([\App\Http\Controllers\SizeController::class, 'create'], ['quick_add' => true]) }}"
                        title="@lang('size.add_size')"
                        data-container=".view_modal">
                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                    </button>
                </span>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('product_color_tags', __('product.color') . ':') !!}
            <div class="qpos-color-tags" data-colors="{{ e(json_encode(array_values($selected_colors))) }}" data-duplicate-msg="{{ e(__('product.color_duplicate')) }}">
                <div class="qpos-color-tag-list"></div>
                <div class="qpos-color-entry">
                    <input type="text" class="form-control qpos-color-input"
                        placeholder="@lang('product.color_placeholder')" maxlength="150" autocomplete="off">
                    <button type="button" class="btn btn-default qpos-color-add">@lang('product.add_color')</button>
                </div>
                <div class="qpos-color-hidden"></div>
            </div>
            <script>
            (function () {
                var box = document.currentScript.previousElementSibling;
                if (!box || box.getAttribute('data-qpos-color-bound') === '1') {
                    return;
                }
                box.setAttribute('data-qpos-color-bound', '1');
                var input = box.querySelector('.qpos-color-input');
                var addBtn = box.querySelector('.qpos-color-add');
                var list = box.querySelector('.qpos-color-tag-list');
                var hidden = box.querySelector('.qpos-color-hidden');
                var colors = [];
                try {
                    colors = JSON.parse(box.getAttribute('data-colors') || '[]');
                } catch (err) {
                    colors = [];
                }
                if (!colors || !colors.length) {
                    colors = [];
                }

                function keyOf(value) {
                    return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
                }

                function render() {
                    list.innerHTML = '';
                    hidden.innerHTML = '';
                    colors.forEach(function (name, index) {
                        var tag = document.createElement('span');
                        tag.className = 'qpos-color-tag';
                        var label = document.createElement('span');
                        label.textContent = name;
                        var remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'qpos-color-remove';
                        remove.setAttribute('aria-label', 'Remove');
                        remove.textContent = '×';
                        remove.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            colors.splice(index, 1);
                            render();
                        });
                        tag.appendChild(label);
                        tag.appendChild(remove);
                        list.appendChild(tag);
                        var field = document.createElement('input');
                        field.type = 'hidden';
                        field.name = 'colors[]';
                        field.value = name;
                        hidden.appendChild(field);
                    });
                }

                function addColor(silent) {
                    var name = String(input.value || '').replace(/\s+/g, ' ').trim();
                    if (!name) {
                        return;
                    }
                    if (name.length > 150) {
                        name = name.substring(0, 150);
                    }
                    var key = keyOf(name);
                    var exists = colors.some(function (existing) {
                        return keyOf(existing) === key;
                    });
                    if (exists) {
                        input.value = '';
                        if (!silent && window.toastr) {
                            window.toastr.error(box.getAttribute('data-duplicate-msg') || 'This colour is already added');
                        }
                        return;
                    }
                    colors.push(name);
                    input.value = '';
                    render();
                    if (!silent) {
                        input.focus();
                    }
                }

                function block(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (e.stopImmediatePropagation) {
                        e.stopImmediatePropagation();
                    }
                }

                input.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' && e.keyCode !== 13) {
                        return;
                    }
                    block(e);
                    addColor(false);
                });
                input.addEventListener('keypress', function (e) {
                    if (e.key !== 'Enter' && e.keyCode !== 13) {
                        return;
                    }
                    block(e);
                });
                addBtn.addEventListener('click', function (e) {
                    block(e);
                    addColor(false);
                });

                var form = box.closest('form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        var active = document.activeElement;
                        if (active && box.contains(active)) {
                            block(e);
                            addColor(false);
                            return;
                        }
                        if (String(input.value || '').trim() !== '') {
                            addColor(true);
                        }
                    }, true);
                }

                box._qposAddColor = addColor;
                render();
            })();
            </script>
        </div>
    </div>
@endif

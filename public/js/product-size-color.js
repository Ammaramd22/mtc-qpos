(function ($) {
    function qposColorKey(value) {
        return $.trim(value || '').replace(/\s+/g, ' ').toLowerCase();
    }

    function qposRenderColors($box) {
        var colors = $box.data('qposColors') || [];
        var $list = $box.find('.qpos-color-tag-list');
        var $hidden = $box.find('.qpos-color-hidden');
        $list.empty();
        $hidden.empty();
        $.each(colors, function (i, name) {
            var $tag = $('<span class="qpos-color-tag"></span>');
            $tag.append($('<span></span>').text(name));
            var $remove = $('<button type="button" class="qpos-color-remove" aria-label="Remove">&times;</button>');
            $remove.attr('data-index', i);
            $tag.append($remove);
            $list.append($tag);
            $hidden.append($('<input type="hidden" name="colors[]">').val(name));
        });
    }

    function qposAddColor($box, fromSubmit) {
        var $input = $box.find('.qpos-color-input');
        var name = $.trim($input.val() || '').replace(/\s+/g, ' ');
        if (!name) {
            return;
        }
        if (name.length > 150) {
            name = name.substring(0, 150);
        }
        var colors = $box.data('qposColors') || [];
        if (!$.isArray(colors)) {
            colors = [];
        }
        var key = qposColorKey(name);
        var exists = false;
        $.each(colors, function (_, existing) {
            if (qposColorKey(existing) === key) {
                exists = true;
            }
        });
        if (exists) {
            if (fromSubmit) {
                $input.val('');
                return;
            }
            if (typeof toastr !== 'undefined') {
                toastr.error($box.attr('data-duplicate-msg') || $box.data('duplicateMsg') || 'This colour is already added');
            }
            $input.focus();
            return;
        }
        colors.push(name);
        $box.data('qposColors', colors.slice());
        $input.val('');
        qposRenderColors($box);
        if (!fromSubmit) {
            $input.focus();
        }
    }

    function qposColorBoxFrom(node) {
        if (!node || !node.closest) {
            return null;
        }
        var box = node.closest('.qpos-color-tags');
        return box ? $(box) : null;
    }

    window.qposInitColorTags = function (root) {
        $(root || document).find('.qpos-color-tags').each(function () {
            var $box = $(this);
            if ($box.data('qposBound')) {
                return;
            }
            $box.data('qposBound', 1);
            if (!$.isArray($box.data('qposColors'))) {
                var initial = [];
                var raw = $box.attr('data-colors');
                if (raw) {
                    try {
                        initial = JSON.parse(raw);
                    } catch (err) {
                        initial = [];
                    }
                }
                if (!$.isArray(initial)) {
                    initial = [];
                }
                $box.data('qposColors', initial.slice());
            }
            if (!$box.data('duplicateMsg')) {
                $box.data('duplicateMsg', 'This colour is already added');
            }
            qposRenderColors($box);
        });
    };

    window.qposInitSizeSelect = function (root) {
        var $el = $(root || document).find('#product_size_ids');
        if (!$el.length || !$.fn.select2) {
            return;
        }
        $el.each(function () {
            var $one = $(this);
            if ($one.data('qposSizeReady')) {
                return;
            }
            if ($one.data('select2')) {
                $one.select2('destroy');
            }
            var $parent = $one.closest('.modal');
            var opts = {
                width: '100%',
                placeholder: $one.data('placeholder') || 'Select sizes',
                closeOnSelect: false,
                allowClear: true
            };
            if ($parent.length) {
                opts.dropdownParent = $parent;
            }
            $one.select2(opts);
            $one.data('qposSizeReady', 1);
        });
    };

    document.addEventListener('keydown', function (e) {
        var input = e.target;
        if (!input || !input.classList || !input.classList.contains('qpos-color-input')) {
            return;
        }
        var box = input.closest('.qpos-color-tags');
        if (box && box.getAttribute('data-qpos-color-bound') === '1') {
            return;
        }
        if (e.key !== 'Enter' && e.keyCode !== 13) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var $box = qposColorBoxFrom(input);
        if ($box) {
            qposAddColor($box, false);
        }
    }, true);

    document.addEventListener('click', function (e) {
        var remove = e.target && e.target.closest ? e.target.closest('.qpos-color-remove') : null;
        if (remove) {
            e.preventDefault();
            e.stopPropagation();
            var $box = qposColorBoxFrom(remove);
            if (!$box) {
                return;
            }
            var colors = ($box.data('qposColors') || []).slice();
            colors.splice(parseInt(remove.getAttribute('data-index'), 10), 1);
            $box.data('qposColors', colors);
            qposRenderColors($box);
            return;
        }
        var add = e.target && e.target.closest ? e.target.closest('.qpos-color-add') : null;
        if (!add) {
            return;
        }
        var addBox = add.closest('.qpos-color-tags');
        if (addBox && addBox.getAttribute('data-qpos-color-bound') === '1') {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var $addBox = qposColorBoxFrom(add);
        if ($addBox) {
            qposAddColor($addBox, false);
        }
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.querySelectorAll) {
            return;
        }
        var active = document.activeElement;
        if (active && active.closest) {
            var activeBox = active.closest('.qpos-color-tags');
            if (activeBox && form.contains(activeBox)) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) {
                    e.stopImmediatePropagation();
                }
                if (typeof activeBox._qposAddColor === 'function') {
                    activeBox._qposAddColor(false);
                } else if (activeBox.getAttribute('data-qpos-color-bound') !== '1') {
                    qposAddColor($(activeBox), false);
                }
                return;
            }
        }
        var boxes = form.querySelectorAll('.qpos-color-tags');
        for (var i = 0; i < boxes.length; i++) {
            if (boxes[i].getAttribute('data-qpos-color-bound') === '1') {
                continue;
            }
            qposAddColor($(boxes[i]), true);
        }
    }, true);

    $(document).on('submit', 'form#size_add_form', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            beforeSend: function () {
                if (typeof __disable_submit_button === 'function') {
                    __disable_submit_button(form.find('button[type="submit"]'));
                }
            },
            success: function (result) {
                if (result.success == true) {
                    $('div.sizes_modal, div.view_modal').modal('hide');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(result.msg);
                    }
                    if ($('#sizes_table').length) {
                        window.location.reload();
                        return;
                    }
                    if (result.data && $('#product_size_ids').length) {
                        var id = String(result.data.id);
                        var name = result.data.name;
                        if ($('#product_size_ids option[value="' + id + '"]').length === 0) {
                            $('#product_size_ids').append(new Option(name, id, true, true));
                        }
                        var vals = $('#product_size_ids').val() || [];
                        if ($.inArray(id, vals) === -1) {
                            vals.push(id);
                        }
                        $('#product_size_ids').val(vals).trigger('change');
                    }
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(result.msg);
                }
            },
            complete: function () {
                form.find('button[type="submit"]').attr('disabled', false);
            }
        });
    });

    $(document).on('submit', 'form#size_edit_form', function (e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            beforeSend: function () {
                if (typeof __disable_submit_button === 'function') {
                    __disable_submit_button(form.find('button[type="submit"]'));
                }
            },
            success: function (result) {
                if (result.success == true) {
                    $('div.sizes_modal').modal('hide');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(result.msg);
                    }
                    if ($('#sizes_table').length) {
                        window.location.reload();
                        return;
                    }
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(result.msg);
                }
            },
            complete: function () {
                form.find('button[type="submit"]').attr('disabled', false);
            }
        });
    });

    $(document).on('click', 'button.edit_size_button', function () {
        $('div.sizes_modal').load($(this).data('href'), function () {
            $(this).modal('show');
        });
    });

    $(document).on('click', 'button.delete_size_button', function () {
        var href = $(this).data('href');
        var run = function () {
            $.ajax({
                method: 'DELETE',
                url: href,
                dataType: 'json',
                success: function (result) {
                    if (result.success == true) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.msg);
                        }
                        if ($('#sizes_table').length) {
                            window.location.reload();
                        }
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(result.msg);
                    }
                }
            });
        };
        if (typeof swal === 'function') {
            swal({
                title: (window.LANG && LANG.sure) ? LANG.sure : 'Are you sure?',
                icon: 'warning',
                buttons: true,
                dangerMode: true
            }).then(function (willDelete) {
                if (willDelete) {
                    run();
                }
            });
        } else if (window.confirm('Delete this size?')) {
            run();
        }
    });

    $(function () {
        if ($('#sizes_table').length && $.fn.DataTable && ! $.fn.DataTable.isDataTable('#sizes_table')) {
            window.sizes_table = $('#sizes_table').DataTable({
                fixedHeader: false,
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: [1, 2], orderable: false, searchable: false }
                ]
            });
        }
        qposInitColorTags(document);
        qposInitSizeSelect(document);
    });

    $(document).on('shown.bs.modal', function (e) {
        qposInitColorTags(e.target);
        qposInitSizeSelect(e.target);
    });
})(jQuery);

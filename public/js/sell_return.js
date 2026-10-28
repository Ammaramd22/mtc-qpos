$(document).ready(function() {
    //For edit pos form
    if ($('form#sell_return_form').length > 0) {
        pos_form_obj = $('form#sell_return_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#sell_return_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            var cnf = true;

            if (cnf) {
                var data = $(form).serialize();
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            //Check if enabled or not
                            if (result.receipt && result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }
                            // Exchange: after return, go back to POS with credit note ready
                            if (result.exchange && result.pos_url) {
                                var delay = (result.receipt && result.receipt.is_enabled) ? 1800 : 500;
                                setTimeout(function() {
                                    window.location.href = result.pos_url;
                                }, delay);
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
            return false;
        },
    });
});

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket.readyState != 1) {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        } else {
            socket.send(JSON.stringify(content));
        }
    } else if (receipt.html_content != '') {
        var title = document.title;
        // Prefer in-app 80mm credit-note preview
        if (typeof __vspos_show_print_preview === 'function') {
            __vspos_show_print_preview(
                receipt.html_content,
                receipt.print_title || 'Credit Note',
                receipt.paper_width || 80,
                {
                    paper_height: receipt.paper_height || null,
                    design: receipt.design || null,
                    paper_label: receipt.paper_label || null
                }
            );
            return;
        }
        if (typeof receipt.print_title != 'undefined') {
            document.title = receipt.print_title;
        }

        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        if (typeof __print_receipt === 'function') {
            __print_receipt('receipt_section');
        } else {
            setTimeout(function() {
                document.body.classList.add('printing-receipt');
                window.print();
                setTimeout(function() {
                    document.body.classList.remove('printing-receipt');
                }, 2000);
            }, 1000);
        }
        setTimeout(function() {
            document.title = title;
        }, 2500);
    }
}

// //Set the location and initialize printer
// function set_location(){
// 	if($('input#location_id').length == 1){
// 	       $('input#location_id').val($('select#select_location_id').val());
// 	       //$('input#location_id').data('receipt_printer_type', $('select#select_location_id').find(':selected').data('receipt_printer_ty
// 	}

// 	if($('input#location_id').val()){
// 	       $('input#search_product').prop( "disabled", false ).focus();
// 	} else {
// 	       $('input#search_product').prop( "disabled", true );
// 	}

// 	initialize_printer();
// }

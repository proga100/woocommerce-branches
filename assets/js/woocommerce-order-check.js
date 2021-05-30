jQuery(document).ready()
{
    function save_paking_products(e) {

        let product_ids = {};
        jQuery('.package_split_exclude').each(function (i, obj) {
            if (jQuery(obj).prop("checked") == false) {
                product_ids[i] = jQuery(obj).data('stm_product_id');
            }
        });

        var formData = new FormData();
        jQuery.each(product_ids, function (id, value) {
            formData.append('product_ids[' + id + ']', value);
        });

        jQuery.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            url: stm_packing_products_admin_url['admin_url'],
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 800000,
            success: function (data) {
                console.log(data);
            },
            error: function (data) {
                console.log(data);
            }
        });

    }

    jQuery("body").on("click", '.package_split_exclude', function (e) {
        save_paking_products(e);
    });

    function myTimer(pdf_id) {
        document.getElementById(pdf_id).click();
    }

    function print_paking_products(e) {
        let product_ids = {};
        jQuery('.package_split_exclude').each(function (i, obj) {
            if (jQuery(obj).prop("checked") == true) {
                let product_id = jQuery(obj).data('stm_product_id');
                // jQuery('#print_spdf_'+ product_id).trigger( "click" );
                if (product_id != '')
                    setInterval(myTimer('print_spdf_' + product_id), 1000);
            }
        });
    }

    jQuery("body").on("click", '.pdf-general', function (e) {
        print_paking_products(e);
    });
}
jQuery(document).ready()
{

    let count = jQuery('.stm-container .stm-row.stm-add').find('.add_pdf').data('item_id');
    count = count;
    let c = 0;
    let first_run = 'yes';
    // console.log('count', count);

    jQuery("body .products_packing_added").each(function (i, obj) {
        jQuery(obj).multipleSelect({});
    });

    jQuery("body").on("change", '.products_packing_added', function (e) {
        let product_ids = jQuery(this).val();
        let item_id = jQuery(this).data('item_id');

        let url = stm_packing_products_admin_url['pdf_link_generate_wpo_wcpdf'];
        jQuery.each(product_ids, function (i, product_id) {
            // url += '&stm_product_id[]=' + product_id;
        });
        //console.log(url, item_id);
        jQuery('.pdf_slip_' + item_id).find('a').attr("href", url);
    });

    jQuery("body").on("click", '.add_pdf', function (e) {
        c = count + 1;
        let button_number = c + 1;
        jQuery('body #products_packing_added_' + count).multipleSelect('destroy');
        let row_html = jQuery(this).closest('.stm-add').html();
        let el = jQuery(this).closest('.stm-add');

        el.find('select').attr('data-item_id', c);
        el.find('select').attr('id', 'products_packing_added_' + c);
        el.find('.add_pdf').attr('data-item_id', c);
        el.find('.stm-col-3').removeClass('pdf_slip_' + count);
        el.find('.stm-col-3').addClass('pdf_slip_' + c);
        el.find('.stm-col-2').removeClass('add_pdf_slip_' + count);
        el.find('.stm-col-2').addClass('add_pdf_slip_' + c);
        el.find('.add_pdf').attr('id', 'add_packing_id_' + c);

        el.find('.stm-col-3 a').text( ' PDF Packing Slip');

        el.before('<div class="stm-row stm-addable" >' + row_html + '</div>');


        jQuery('body #products_packing_added_' + count).multipleSelect();
        jQuery('body #products_packing_added_' + c).multipleSelect();
        count = c;

        save_add_packing_slip(e);
    });

    jQuery("body").on("click", '.rem_pdf', function (e) {
        jQuery(this).closest('.stm-row').remove();
         save_add_packing_slip(e);
    });

    function save_add_packing_slip(e) {

        let product_ids = {};
        let packing_slip_id = 0;
        jQuery('body .stm-container .stm-row.stm-addable').each(function (i, obj) {

                packing_slip_id = jQuery(obj).find('select').data('item_id');
                // console.log(packing_slip_id);
                product_ids[packing_slip_id] = jQuery('body select#products_packing_added_' + packing_slip_id).val();
                //console.log(jQuery(obj).find('select'));

        });
        //  console.log(product_ids);

        var formData = new FormData();
        jQuery.each(product_ids, function (id, value) {
            formData.append('product_ids[' + id + ']', value);
        });

        jQuery.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            url: stm_packing_products_admin_url['admin_url'],
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 800000,
            success: function (data) {
                console.log(data);
            },
            error: function (data) {
                console.log(data);
            }
        });

    }

    jQuery("body").on("change", 'select', function (e) {
        if ((!(jQuery(this).closest('.stm-row').hasClass('stm-add'))) &&
            ((jQuery(this).closest('.stm-row').hasClass('stm-addable')))
        ) {
            save_add_packing_slip(e);
        }
    });
}
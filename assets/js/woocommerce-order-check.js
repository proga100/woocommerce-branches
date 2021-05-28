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
            url: stm_packing_products_admin_url,
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
        document.getElementById(pdf_id).click(); // Works!
        console.log(pdf_id);
    }

    function print_paking_products(e) {
        let product_ids = {};
        jQuery('.package_split_exclude').each(function (i, obj) {
            if (jQuery(obj).prop("checked") == true) {
                let product_id = jQuery(obj).data('stm_product_id');
                // jQuery('#print_spdf_'+ product_id).trigger( "click" );
                if(product_id !='')
                setInterval(myTimer('print_spdf_' + product_id), 1000);
            }
        });
    }

    jQuery("body").on("click", '.pdf-general', function (e) {
        print_paking_products(e);
    });
}
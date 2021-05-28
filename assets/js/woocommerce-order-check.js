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

}
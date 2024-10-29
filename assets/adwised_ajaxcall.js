jQuery(document).ready(function () {

    jQuery("#Send_notification_Adwised").click(function (e) {
        e.preventDefault();

        var title = jQuery("#Adwised_form_api #title_Adwised").val();


    });
    var ajaxurl = "/wp-admin/admin-ajax.php";
    jQuery("#get_pop_url_btn").click(function (e) {
        e.preventDefault();
        jQuery.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajaxurl,
            data: {
                'action' : 'get_adwised_url_first_time',
                'popId':jQuery("#adwisedPopId").val(),
                'popSecretKey':jQuery("#adwisedPopSecretkey").val()

            },
            success: function (data) {

                if (data.status) {
                    jQuery("#pop_up_error").html(data.message).show().css("background","#08b254");
                }else{
                    jQuery("#pop_up_error").html(data.message).show().css("background","#c42323");
                }

            },
            error: function (errorThrown) {

            }
        });
    });
    // jQuery("#adwised_setting_form").submit(function (e) {
    //
    //     jQuery.get('https://popapi.adwised.com/api/home/b500766c-2820-42d6-b1bf-51bfd0953290/0',  // url
    //         function (data, textStatus, jqXHR) {  // success callback
    //         var adwisedUrl=data;
    //
    //         });
    //
    // });

});

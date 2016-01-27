/** JS functions related to pages from the post_type 'Programa' **/

jQuery( document ).ready(function() {
    var OSName="Unknown OS";
    if (navigator.appVersion.indexOf("Win")!=-1) OSName="windows";
    else if (navigator.userAgent.indexOf("Mac")!=-1) OSName="osx";
    else if (navigator.userAgent.indexOf("Linux")!=-1) OSName="linux";

    if(jQuery('#baixada_'+OSName).length) {
        jQuery('#baixada_'+OSName).show();
    } else {
        jQuery('.baixada_boto').first().show();
    }
});

jQuery('#input_rating').on('change', function () {
    var complexname = jQuery(this).attr('name');
    var name = complexname.split('_');
    var cookie_id = "sc_"+complexname;
    if(document.cookie.indexOf(cookie_id) < 0) {
        //Data
        var post_data = new FormData();
        post_data.append('post_id', name[2]);
        post_data.append('rate', jQuery("#input_rating").val());
        post_data.append('action', 'send_vote');

        jQuery.ajax({
            type: 'POST',
            url: scajax.ajax_url,
            data: post_data,
            dataType: 'json',
            contentType: false,
            processData: false,
            success : form_sent_ok,
            error : form_sent_ko
        });


        var CookieDate = new Date;
        CookieDate.setFullYear(CookieDate.getFullYear( ) +10);
        document.cookie = cookie_id+'=1; expires=' + CookieDate.toGMTString( ) + ';';
    } else {
        var message_text = 'Sembla que ja havies votat abans...';
        show_message(message_text);
    }
});

function form_sent_ok(result) {
    show_message(result.text);
}

function form_sent_ko(result) {
    show_message("S'ha produït un error en enviar les dades. Proveu de nou més tard.");
}

function show_message(text) {
    jQuery("#message_text").html(text);
    jQuery('.modal').modal('show');
}
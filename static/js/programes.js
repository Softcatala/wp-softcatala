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

/** Cerca **/
jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_programes" ).submit();
});

jQuery("#mostra_arxivat").on('click', function() {
    if(jQuery("#arxivat").val() == 1) {
        jQuery("#arxivat").val(0);
    } else {
        jQuery("#arxivat").val(1);
    }

    jQuery( "#cerca_programes" ).submit();
});

/** Rating **/
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

/** Formulari afegeix **/
jQuery(".next_step").on('click', function() {
    var button_id = jQuery(this).attr('id').split('_');
    step = button_id[1];
    jQuery("#form_"+step).hide();
    step++;
    jQuery("#form_"+step).show();
});

var $contactForm = jQuery('#second_step');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    jQuery("#loading").fadeIn();
    var nom_programa = jQuery("#nom_programa").val();

    //Data
    var post_data = new FormData();
    post_data.append('nom_programa', nom_programa);
    post_data.append('action', 'search_program');

    jQuery.ajax({
        type: 'POST',
        url: scajax.ajax_url,
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : form_search_ok,
        error : form_sent_ko
    });
});

function form_search_ok(result) {
    jQuery("#loading").hide();
    jQuery("#text_response").html(result.text+result.programs);
    jQuery("#pas_2").show();
}
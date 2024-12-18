//Search form
jQuery("#cerca_aparells .selectpicker").on('change', function() {
    jQuery( "#cerca_aparells" ).submit();
});

var $cerca_form = jQuery('#cerca_aparells');
$cerca_form.on('submit', function(){
    disable_empty_fields();
    return true;
});

function disable_empty_fields() {
    jQuery('#cerca_aparells').find('input, select').each(function(_, inp) {
        if (jQuery(inp).val() === '' || jQuery(inp).val() === null)
            inp.disabled = true;
    });
}

/** New aparell form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    post_data = new FormData();
    post_data.append('nom', jQuery('input[name=nom]').val());
    post_data.append('tipus_aparell', jQuery('#tipus_ap option:selected').val());
    post_data.append('fabricant', jQuery('input[name=fabricant]').val());
    post_data.append('sistema_operatiu', jQuery('#so_aparell option:selected').val());
    post_data.append('versio', jQuery('input[name=versio]').val());
    post_data.append('traduccio_catala', jQuery('input[name=traduccio_catala]:checked').val());
    post_data.append('correccio_catala', jQuery('input[name=correccio_catala]:checked').val());
    post_data.append('comentari', jQuery('textarea[name=comentari]').val());
    post_data.append('action', 'send_aparell');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce_aparell]').val());

    var file = jQuery(document).find('input[type="file"]');
    var individual_file = file[0].files[0];
    post_data.append("file", individual_file);

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
});

function form_sent_ok(result) {
    jQuery('#contingut-formulari').hide();
    jQuery('#aparell_initial_message').hide();
    jQuery('#contingut-formulari-response').empty().html(result.text).fadeIn();
}

function form_sent_ko(result) {
    jQuery('#contingut-formulari').hide();
    jQuery('#aparell_initial_message').hide();
    jQuery('#contingut-formulari-response').empty().html("S'ha produït un error en enviar les dades. Proveu una altra vegada més tard.").fadeIn();
}

jQuery('#afegeix_aparell_button').click(function() {
    jQuery("#contingut-formulari-response").hide();
    jQuery("input[name='nom']").val('');
    jQuery("input[name='fabricant']").val('');
    jQuery("input[name='versio']").val('');
    jQuery("textarea[name='comentari']").val('');
    jQuery("#contingut-formulari").show();
});
/** End New aparell form action **/


/** Function to load information from given aparell **/
jQuery('.collapse_aparell').click(function(){
    var div_id = jQuery(this).attr('href');
    var aparell_id = div_id.replace("#collapse", "");
    var collapse_div = jQuery('#collapse'+aparell_id);
    jQuery("#loading_"+aparell_id).show();

    if (aparell_id && ! jQuery.trim(collapse_div.html()).length ) {
        jQuery("#loading").show();

        //Data
        var post_data = new FormData();
        post_data.append('aparell_id', aparell_id);
        post_data.append('action', 'aparell_ajax_load');

        jQuery.ajax({
            url: scajax.ajax_url,
            type: 'POST',
            data: post_data,
            dataType: 'json',
            contentType: false,
            processData: false,
            success : print_results,
        });

        return false;
    } else {
        collapse_div.collapse("toggle");
        jQuery("#loading_"+aparell_id).hide();
    }
});

function print_results(result) {
    jQuery("#loading_"+result.aparell_id).hide();
    jQuery('#collapse'+result.aparell_id).html(result.aparell_detall);
    jQuery('#collapse'+result.aparell_id).collapse("toggle");
    enable_comments();
}

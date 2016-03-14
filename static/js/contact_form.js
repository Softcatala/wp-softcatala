/** Contact form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    var post_data = new FormData();
    post_data.append('nom', jQuery('input[name=nom_contacte]').val());
    post_data.append('correu', jQuery('input[name=correu_contacte]').val());
    post_data.append('tipus', jQuery('#tipus_contacte option:selected').val());
    post_data.append('comentari', jQuery('#comentari_contacte').val());
    post_data.append('to_email', jQuery('#to_email').val());
    post_data.append('nom_from', jQuery('#nom_from').val());
    post_data.append('assumpte', jQuery('#assumpte').val());
    post_data.append('action', 'contact_form');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce]').val());

    jQuery.ajax({
        type: 'POST',
        url: scajax.ajax_url,
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : contact_form_ok,
        error : contact_form_ko
    });
});

function contact_form_ok(dt) {
    if (dt.type == 'message') {
        jQuery("#contingut-formulari").hide();
        jQuery("#contingut-formulari-response").empty().html(dt.text).fadeIn();
    }
}

function contact_form_ko() {
    var message = 'Alguna cosa no ha funcionat bé en enviar les dades al servidor de traducció';
    jQuery("#contingut-formulari").hide();
    jQuery("#contingut-formulari-response").empty().html(message).fadeIn();
}

jQuery('#contact_form').click(function() {
    jQuery("#contingut-formulari-response").hide();
    jQuery("textarea[name='comentari']").val('');
    jQuery("#contingut-formulari").show();
});
/** End contact form action **/
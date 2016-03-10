/** Formulari afegeix programa **/
var $sinonims_form = jQuery('#sinonims_form');

$sinonims_form.on('submit', function(ev) {
    ev.preventDefault();

    jQuery('#_action_consulta_sinonims').trigger('click');
});

jQuery('#_action_consulta_sinonims').click(function(){

    jQuery("#loading").show();
    var url = 'https://www.softcatala.org/sinonims/api/search';
    var query = jQuery('#sinonims').val();

    var url_history = '/diccionari-de-sinonims/paraula/'+query+'/';
    history.pushState(null, null, url_history);

    update_share_links(query);

    //Data
    var post_data = new FormData();
    post_data.append('paraula', query);
    post_data.append('action', 'find_sinonim');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce_sinonim]').val());

    jQuery.ajax({
        url: scajax.ajax_url,
        type: 'POST',
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : print_synonims,
        error : errorSynsets
    });

    return false;
});

function print_synonims(result) {
    jQuery("#loading").hide();
    jQuery('#results').html(result);
    jQuery('#results').slideDown();
}

function errorSynsets() {
    show_message("S'ha produït un error en enviar les dades. Proveu de nou més tard.");
}

function show_message(text) {
    jQuery("#results").html(text);
    jQuery('#results').show();
    jQuery("#loading").fadeOut();
}

//Function to update share links on ajax calls
function update_share_links(query) {
    var url = window.location.href;
    var url_facebook = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
    var url_twitter = 'https://twitter.com/intent/tweet?text=Sinònims de la paraula ' + query + ' al diccionari de sinònims de Softcatalà ' + url;
    var url_google = 'https://plus.google.com/share?url=' + url;

    jQuery('#share_facebook').attr("href", url_facebook);
    jQuery('#share_twitter').attr("href", url_twitter);
    jQuery('#share_google').attr("href", url_google);
}

/** Contact form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    var post_data = new FormData();
    post_data.append('nom', jQuery('input[name=nom]').val());
    post_data.append('correu', jQuery('input[name=correu]').val());
    post_data.append('tipus', jQuery('#tipus_contacte option:selected').val());
    post_data.append('comentari', jQuery('#comentari').val());
    post_data.append('to_email', 'recursos@llistes.softcatala.org');
    post_data.append('nom_from', 'Diccionari de sinònims de Softcatalà');
    post_data.append('assumpte', '[Diccionari de sinònims] Contacte des del formulari');
    post_data.append('action', 'contact_form');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce]').val());

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

function form_sent_ok(dt) {
    if (dt.type == 'message') {
        jQuery("#contingut-formulari").hide();
        jQuery("#contingut-formulari-response").empty().html(dt.text).fadeIn();
    }
}

jQuery('#contact_traductor').click(function() {
    jQuery("#contingut-formulari-response").hide();
    jQuery("textarea[name='comentari']").val('');
    jQuery("#contingut-formulari").show();
});

function form_sent_ko() {
    var message = 'Alguna cosa no ha funcionat bé en enviar les dades al servidor de traducció';
    jQuery("#contingut-formulari").hide();
    jQuery("#contingut-formulari-response").empty().html(message).fadeIn();
}
/** End contact form action **/
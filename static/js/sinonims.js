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

    //Data
    var post_data = new FormData();
    post_data.append('paraula', query);
    post_data.append('action', 'find_sinonim');

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
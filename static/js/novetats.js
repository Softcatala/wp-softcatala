
function setup_novetats_form() {
    var boto = jQuery('header.main-header .bt-basic-vermell');

    if (boto) {
        boto.attr('href', '#');

        boto.click(function() {
            jQuery('.bs-novetats-modal-lg').modal();
        });
    }
}

setup_novetats_form();

//Form
var $collabora_form = jQuery('#collabora_form');

$collabora_form.on('submit', function(ev){
    ev.preventDefault();

    jQuery("#loading").fadeIn();
    var nom = jQuery("#nom_contacte").val();
    var correu = jQuery("#correu_contacte").val();
    var llista = jQuery("#llista").val();
    var projecte = jQuery("#projecte").val();
    var projecte_slug = jQuery("#projecte_slug").val();

    //Data
    var post_data = new FormData();
    post_data.append('nom', nom);
    post_data.append('correu', correu);
    post_data.append('llista', llista);
    post_data.append('projecte', projecte);
    post_data.append('projecte_slug', projecte_slug);
    post_data.append('action', 'subscribe_list');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce_subscribe]').val());

    jQuery.ajax({
        type: 'POST',
        url: scajax.ajax_url,
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : form_subscribe_ok,
        error : form_subscribe_ko
    });
});

function form_subscribe_ok(dt) {
    jQuery('#collabora_form').html();
    jQuery('#collabora_form').html(dt.text);
}

function form_subscribe_ko(dt) {
    jQuery('#collabora_form').html();
    jQuery('#collabora_form').html('Sembla que s\'ha produït un problema');
}




jQuery('.steps').click(function(){
    var data_id = jQuery(this).data('id');
    jQuery("#"+data_id).siblings('.step_div').hide();
    jQuery("#"+data_id).show();

    jQuery('.steps').each(function() {
        jQuery(this).removeClass('active');
    });

    jQuery('[data-id="'+data_id+'"]').addClass('active');

    //Check if is last
    var step_id = data_id.split('_');
    step_id[1]++;
    data_id = 'step_' + step_id[1];
    if ( ! jQuery('[data-id="'+data_id+'"').length) {
        jQuery("#bt-next").hide();
        jQuery("#bt-last").show();
    } else {
        jQuery("#bt-last").hide();
        jQuery("#bt-next").show();
    }

    //Don't show the 'Demanue col·laborar' button in the 3rd step
    if(step_id[1] > 3) {
        jQuery("#bt-last").hide();
    }
});

jQuery('#bt-next').click(function(){
    jQuery('.steps').each(function() {
        if (jQuery(this).hasClass('active')) {
            var data_id = jQuery(this).data('id');
            var step_id = data_id.split('_');
            var step_number = step_id[1];
            step_number++;
            var value_id = 'step_'+step_number;
            jQuery('[data-id="'+value_id+'"]').trigger('click');
            return false;
        }
    });
});

//Multiples projectes
jQuery('.link_colabora').on('click', function(){
    var llista = jQuery(this).attr("data-llista");
    var projecte = jQuery(this).attr("data-projecte");
    jQuery('#llista').val(llista);
    jQuery('#projecte').val(projecte);
    jQuery('.bs-formjoin-modal-lg').modal('show');
});

//Form
var $collabora_form = jQuery('#collabora_form');

$collabora_form.on('submit', function(ev){
    ev.preventDefault();

    jQuery("#loading").fadeIn();
    var nom = jQuery("#nom_contacte").val();
    var correu = jQuery("#correu_contacte").val();
    var llista = jQuery("#llista").val();
    var projecte = jQuery("#projecte").val();

    //Data
    var post_data = new FormData();
    post_data.append('nom', nom);
    post_data.append('correu', correu);
    post_data.append('llista', llista);
    post_data.append('projecte', projecte);
    post_data.append('action', 'subscribe_list');

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
    post_data.append('to_email', 'web@softcatala.org');
    post_data.append('nom_from', 'Rebost de Softcatalà');
    post_data.append('assumpte', '[Programes] Contacte des del formulari');
    post_data.append('action', 'contact_form');

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
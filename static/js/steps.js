jQuery('.steps').click(function(){
    var data_id = jQuery(this).data('id');
    jQuery("#"+data_id).siblings('.step_div').hide();
    jQuery("#"+data_id).show();

    jQuery("#title_"+data_id).siblings().hide();
    jQuery("#title_"+data_id).show();

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

    //Don't show the 'Demaneu col·laborar' button in the 3rd step
    if(step_id[1] > 3 && ! jQuery('#projecte_id').length) {
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
    var projecteslug = jQuery(this).attr("data-projecteslug");
    jQuery('#llista').val(llista);
    jQuery('#projecte').val(projecte);
    jQuery('#projecte_slug').val(projecteslug);
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

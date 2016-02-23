jQuery('.steps').click(function(){
    var data_id = jQuery(this).data('id');
    var content = jQuery("#"+data_id).html();
    jQuery('#steps_visible').html('');
    jQuery('#steps_visible').html(content);

    jQuery('.steps').each(function() {
        jQuery(this).removeClass('active');
    });

    jQuery('[data-id="'+data_id+'"]').addClass('active');

    //Check if is last
    var step_id = data_id.split('_');
    step_id[1]++;
    data_id = 'step_' + step_id[1];
    if ( ! jQuery('[data-id="'+data_id+'"').length) {
        jQuery('.bt-seguent').each(function() {
            jQuery(this).removeClass('bt-seguent');
            jQuery(this).addClass('bt-last');
            jQuery(this).html('Demaneu col·laborar en aquest projecte');
        });
    } else {
        jQuery('.bt-last').each(function() {
            jQuery(this).removeClass('bt-last');
            jQuery(this).addClass('bt-seguent');
            jQuery(this).html('següent');
        });
    }
});

jQuery('.bt-seguent').click(function(){
    jQuery('.steps').each(function() {
        if (jQuery(this).hasClass('active')) {
            var data_id = jQuery(this).data('id');
            var step_id = data_id.split('_');
            step_id[1]++;
            var value_id = 'step_'+step_id[1];
            jQuery('[data-id="'+value_id+'"]').trigger('click');

            return;
        }
    });
});
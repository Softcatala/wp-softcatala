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
        jQuery("#bt-next").hide();
        jQuery("#bt-last").show();
    } else {
        jQuery("#bt-last").hide();
        jQuery("#bt-next").show();
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
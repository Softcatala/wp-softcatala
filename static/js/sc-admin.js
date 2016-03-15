jQuery( document ).ready(function() {

    //Tabs
    jQuery(".nav-tab").on('click', function () {
        var element_id = jQuery(this).attr('data-id');

        jQuery('#' + element_id).siblings().hide();
        jQuery('#' + element_id).show();
        jQuery(this).siblings().removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active');


    });
});
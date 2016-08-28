jQuery( document ).ready(function() {
    enable_comments();
});

function enable_comments () {
    jQuery( ".respon_link" ).on('click', function() {
        var comment_ids = this.id.split("_");
        jQuery("#comment_form_flexible_"+comment_ids[0]).detach().appendTo('#comment-'+comment_ids[0]+'_'+comment_ids[1]);

        jQuery("#comment_parent_"+comment_ids[0]).val(comment_ids[1]);
        if (this.id == '0') {
            jQuery( "#comment_flexible_form_"+comment_ids[0] ).css( "margin-left", "0" );
        } else {
            jQuery( "#comment_flexible_form_"+comment_ids[0] ).css( "margin-left", "68px" );
        }
        jQuery("#comment_form_flexible_"+comment_ids[0]).fadeIn();
    });

    jQuery( ".hide_clean_form" ).click(function() {
        var comment_id = jQuery(this).attr("data-id");
        jQuery("#comment_form_flexible_"+jQuery(this).attr("data-id")).hide();
        jQuery("#author").val('Nom');
        jQuery("#comment").val('Comentari');
        jQuery("#email").val('E-mail');
    });


    jQuery('.mescomentaris').on('show.bs.collapse',function(){
            jQuery(".mescomentaris").next().find("button").toggle();
    });

    jQuery('.mescomentaris').on('hide.bs.collapse',function(){
          jQuery(".mescomentaris").next().find("button").toggle();
    });
}

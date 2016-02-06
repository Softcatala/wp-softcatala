/** Search **/
jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_noticies" ).submit();
});

var $cerca_form = jQuery('#cerca_noticies');
$cerca_form.on('submit', function(){
    disable_empty_fields();
    return true;
});

function disable_empty_fields() {
    jQuery('#cerca_noticies').find('input, select').each(function(_, inp) {
        if (jQuery(inp).val() === '' || jQuery(inp).val() === null)
            inp.disabled = true;
    });
}
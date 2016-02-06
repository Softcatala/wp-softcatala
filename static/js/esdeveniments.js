/** Cerca **/
jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_esdeveniments" ).submit();
});

/** New aparell form action **/
var $cerca_form = jQuery('#cerca_esdeveniments');

$cerca_form.on('submit', function(){
    disable_empty_fields();
    return true;
});

function disable_empty_fields() {
    jQuery('#cerca_esdeveniments').find('input, select').each(function(_, inp) {
        if (jQuery(inp).val() === '' || jQuery(inp).val() === null)
            inp.disabled = true;
    });
}
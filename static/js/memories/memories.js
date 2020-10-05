jQuery(document).ready(function(){
    jQuery(".chosen").chosen();
    jQuery("#search-samples-area").click(showHelp);
});

function showHelp() {
    jQuery('#show-samples').toggle();
}

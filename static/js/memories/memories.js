jQuery(document).ready(function(){
    jQuery(".chosen").chosen();
    jQuery("#search-samples-area").click(showHelp);
});

function showHelp() {
    jQuery('#search-samples').toggle("slow");
}

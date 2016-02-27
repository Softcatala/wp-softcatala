var $cerca_form = jQuery('#cerca_noticies');
$cerca_form.on('submit', function(ev){
    ev.preventDefault();
    var cerca = jQuery('#cerca').val();

    window.location.href = '/cerca/'+cerca+'/';

    return true;
});
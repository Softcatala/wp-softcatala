jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_aparells" ).submit();
});

/** New aparell form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    post_data = new FormData();
    post_data.append('nom', jQuery('input[name=nom]').val());
    post_data.append('tipus_aparell', jQuery('input[name=tipus_aparell]:checked').val());
    post_data.append('fabricant', jQuery('input[name=fabricant]').val());
    post_data.append('sistema_operatiu', jQuery('input[name=sistema_operatiu]:checked').val());
    post_data.append('versio', jQuery('input[name=versio]').val());
    post_data.append('traduccio_catala', jQuery('input[name=traduccio_catala]:checked').val());
    post_data.append('correccio_catala', jQuery('input[name=correccio_catala]:checked').val());
    post_data.append('comentari', jQuery('textarea[name=comentari]').val());
    post_data.append('action', 'send_aparell');

    var file = jQuery(document).find('input[type="file"]');
    var individual_file = file[0].files[0];
    post_data.append("file", individual_file);

    jQuery.ajax({
        type: 'POST',
        url: scajax.ajax_url,
        data: post_data,
        contentType: false,
        processData: false,
        success: function(response){
            alert('The server responded: ' + response);
        }
    });
});

/** End New aparell form action **/
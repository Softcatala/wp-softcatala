jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_aparells" ).submit();
});

/** New aparell form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    console.log($contactForm[0]);
    post_data = new FormData($contactForm[0]);
    console.log(post_data);

    jQuery.ajax({
        url: scajax.ajax_url,
        contentType: false,
        processData: false,
        data: {
            'action': 'send_aparell',
            'data': post_data
        },
        success: function(response){
            alert('The server responded: ' + response);
        }
    });
});

/** End New aparell form action **/
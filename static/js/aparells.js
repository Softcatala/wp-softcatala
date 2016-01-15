jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_aparells" ).submit();
});

/** Contact form action **/
var $contactForm = jQuery('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    post_data = {
        'nom'       : $('input[name=nom]').val(),
        'tipus_aparell'    : $('input[name=tipus_aparell]:checked').val(),
        'fabricant'       : $('input[name=fabricant]').val(),
        'sistema_operatiu'     : $('input[name=sistema_operatiu]:checked').val(),
        'versio'       : $('input[name=versio]').val(),
        'traduccio_catala'       : $('input[name=traduccio_catala]:checked').val(),
        'correccio_catala'       : $('input[name=correccio_catala]:checked').val(),
        'comentari' : $('textarea[name=comentari]').val()
    };

    jQuery.post(
        scajax.ajax_url,
        {
            'action': 'send_aparell',
            'data': post_data
        },
        function(response){
            alert('The server responded: ' + response);
        }
    );
});

function form_sent_ok(dt) {
    if (dt.type == 'message') {
        $("#contingut-formulari").hide();
        $("#contingut-formulari-response").empty().html(dt.text).fadeIn();
    }
}

$('#contact_traductor').click(function() {
    $("#contingut-formulari-response").hide();
    $("textarea[name='comentari']").val('');
    $("#contingut-formulari").show();
});

function form_sent_ko(dt) {
    alert('Alguna cosa no ha funcionat bé en enviar les dades al servidor de traducció');
}
/** End contact form action **/
jQuery(".selectpicker").on('change', function() {
    jQuery( "#cerca_aparells" ).submit();
});

/** Contact form action **/
var $contactForm = $('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    post_data = {
        'nom'       : $('input[name=nom]').val(),
        'tipus_aparell'    : $('input[name=correu]').val(),
        'fabricant'       : $('input[name=fabricant]').val(),
        'sistema_operatiu'     : $('input[name=sistema_operatiu]').val(),
        'versio'       : $('input[name=versio]').val(),
        'traduccio_catala'       : $('input[name=traduccio_catala]').val(),
        'correccio_catala'       : $('input[name=correccio_catala]').val(),
        'comentari' : $('textarea[name=comentari]').val()
    };

    $.ajax({
        url:"/traductor",
        type:"POST",
        data : post_data,
        dataType: 'json',
        success : form_sent_ok,
        failure : form_sent_ko
    });
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
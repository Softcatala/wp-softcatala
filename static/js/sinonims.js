jQuery(document).ready(function () {
    if (jQuery("#sinonims").val() != '') {
        jQuery('#_action_consulta_sinonims').trigger('click');
    }
});


jQuery('#_action_consulta_sinonims').click(function(){

    jQuery("#loading").show();
    var url = '/sinonims/api/search';
    var query = jQuery('#sinonims').val();

    var url_history = '/diccionari_de_sinonims/paraula/'+query;
    history.pushState(null, null, url_history);

    $.ajax({
        url : url,
        type:"POST",
        data : {'format':'application/json','q':query},
        dataType: 'json',
        success: printSynsets,
        error: errorSynsets
    });

    return false;
});

function printSynsets(data) {
    var synsets = data.synsets;
    var query = jQuery('#sinonims').val();

    var toAdd = '';

    if(synsets.length > 0) {
        toAdd = '<h1>'+query+'</h1><ol>';
        jQuery(synsets).each(function() {
            var categoria = this.categories[0];
            if(categoria == "undefined")
            {
                categoria = "";
            }

            toAdd += '<li><strong>'+categoria+'</strong>: ';
            toAdd += $.map(this.terms, printTerm).join(', ');
        })

        toAdd += '</ol>';
    } else {
        toAdd = '<br /><p>No hem trobat cap resultat al nostre diccionari</p>';
    }

    jQuery("#loading").hide();
    jQuery('#results').html(toAdd);
    jQuery('#results').slideDown();
}

function printTerm(term,index) {
    var ret = term.term;

    if(term.level) {
        ret += " ("+term.level+")";
    }

    return ret;
}

function errorSynsets() {
    show_message("S'ha produït un error en enviar les dades. Proveu de nou més tard.");
}

function show_message(text) {
    jQuery("#results").html(text);
    jQuery('#results').show();
    jQuery("#loading").fadeOut();
}
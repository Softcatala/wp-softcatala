/** Formulari afegeix programa **/
var $sinonims_form = jQuery('#sinonims_form');

$sinonims_form.on('submit', function(ev) {
    ev.preventDefault();

    jQuery('#_action_consulta_sinonims').trigger('click');
});

jQuery('#_action_consulta_sinonims').click(function(){

    jQuery("#loading").show();
    var query = jQuery('#sinonims').val();
    query = query.trim().replace("'", "’");

    var url_history = '/diccionari-de-sinonims/paraula/'+query+'/';
    history.pushState(null, null, url_history);

    jQuery("#content_header_title").html('Diccionari de sinònims: «'+query+'»');

    update_share_links(query);

    //Data
    var post_data = new FormData();
    post_data.append('paraula', query);
    post_data.append('action', 'find_sinonim');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce_sinonim]').val());

    jQuery.ajax({
        url: scajax.ajax_url,
        type: 'POST',
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success : print_synonims,
        error : errorSynsets
    });

    return false;
});

function print_synonims(result) {
    jQuery("#loading").hide();
    jQuery('#results').html(result.html);
    jQuery('#results').slideDown();
    sc_sendTracking(true);
    enableInlineLinks();
}

function errorSynsets(response) {
    
    status = response.status != '0' ? response.status : 500;
    
    sc_sendTracking(false, status);
    
    show_message(response.responseJSON.html);
}

function sc_sendTracking(success, status) {
    if (typeof(ga) == 'function')
    {
        var url = success ? '' : status;

        url += document.location.pathname;

       ga('send', 'pageview', url);
    }
}

function show_message(text) {
    jQuery("#results").html(text);
    jQuery('#results').show();
    jQuery("#loading").fadeOut();
}

//Function to update share links on ajax calls
function update_share_links(query) {
    var url = window.location.href;
    var url_facebook = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
    var url_twitter = 'https://twitter.com/intent/tweet?text=Sinònims de la paraula ' + query + ' al diccionari de sinònims de Softcatalà ' + url;

    jQuery('#share_facebook').attr("href", url_facebook);
    jQuery('#share_twitter').attr("href", url_twitter);
}

function enableInlineLinks() {
    jQuery('.diccionari-resultat#results a').click(function(ev) {
        var sinonim = jQuery(this).data('sinonim');

        if(sinonim) {
            jQuery('#sinonims').val(sinonim)

            ev.preventDefault();

            jQuery('#_action_consulta_sinonims').trigger('click');
        }
    })
}

enableInlineLinks();


//Autocomplete
jQuery('#sinonims').typeahead(
    {
        minLength: 1,
        hint: true,
    },
    {
        delay: 3500,
        limit: 12,
        async: true,
        source: function(query, processSync, processAsync) {

            var xurl = "https://api.softcatala.org/sinonims/v1/api/autocomplete/" + query;

            jQuery.ajax({
                url: xurl,
                dataType: "json",
                success: function( data ) {

                    return processAsync ( data.words );

                },
                error: function (textStatus, status, errorThrown) {
                    console.log(textStatus);
                    console.log(status);
                    console.log(errorThrown);
                }
            });
        }
    }
).on('typeahead:selected', function(evt, item) {
    jQuery('#_action_consulta_sinonims').trigger('click');
});

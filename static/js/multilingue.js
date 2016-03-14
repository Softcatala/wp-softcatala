/** Formulari afegeix programa **/
var $sinonims_form = jQuery('#multilingue_form');

$sinonims_form.on('submit', function(ev) {
    ev.preventDefault();

    jQuery('#_action_consulta').trigger('click');
});

jQuery('#_action_consulta').click(function(){
    jQuery('.typeahead').typeahead('close');

    var query = jQuery('#source').val();
    var lang = jQuery('#lang option:selected').val();

    if (query) {
        jQuery("#loading").show();
        var lang_history = '';
        if ( lang != 'ca' ) {
            lang_history = 'llengua/' + lang + '/';
        }

        var url_history = '/diccionari-multilingue/paraula/'+query+'/' + lang_history;
        history.pushState(null, null, url_history);

        update_share_links(query);

        //Data
        var post_data = new FormData();
        post_data.append('paraula', query);
        post_data.append('lang', lang);
        post_data.append('action', 'multilingue_search');
        post_data.append('_wpnonce', jQuery('input[name=_wpnonce_search]').val());

        jQuery.ajax({
            url: scajax.ajax_url,
            type: 'POST',
            data: post_data,
            dataType: 'json',
            contentType: false,
            processData: false,
            success : print_results,
            error : ko_function
        });

        return false;
    } else {
        jQuery('#results').html('Introduïu un terme de cerca...');
    }
});

function print_results(result) {
    jQuery("#loading").hide();
    jQuery('#results').html(result);
    jQuery('#results').slideDown();
}

function ko_function(result) {
    jQuery("#loading").hide();
    jQuery('#results').html(JSON.parse(result.responseText));
    jQuery('#results').slideDown();
}

//Function to update share links on ajax calls
function update_share_links(query) {
    var url = window.location.href;
    var url_facebook = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
    var url_twitter = 'https://twitter.com/intent/tweet?text=Definició de la paraula ' + query + ' al diccionari multilingüe de Softcatalà ' + url;
    var url_google = 'https://plus.google.com/share?url=' + url;

    jQuery('#share_facebook').attr("href", url_facebook);
    jQuery('#share_twitter').attr("href", url_twitter);
    jQuery('#share_google').attr("href", url_google);
}

//Autocomplete
jQuery('#source').typeahead(
    {
        minLength: 1,
        hint: true,
    },
    {
        delay: 3500,
        limit: 12,
        async: true,
        source: get_autocomplete_words
    }
).on('typeahead:selected', function(evt, item) {
    jQuery('#_action_consulta').trigger('click');
});

function get_autocomplete_words (query, processSync, processAsync) {
    var lang = jQuery('#lang option:selected').val();

    //Data
    var post_data = new FormData();
    post_data.append('paraula', query);
    post_data.append('lang', lang);
    post_data.append('action', 'multilingue_autocomplete');
    post_data.append('_wpnonce', jQuery('input[name=_wpnonce_auto]').val());

    jQuery.ajax({
        url: scajax.ajax_url,
        type: 'POST',
        data: post_data,
        dataType: 'json',
        contentType: false,
        processData: false,
        success: function (json) {
            return processAsync(json.words);
        }
    });
}
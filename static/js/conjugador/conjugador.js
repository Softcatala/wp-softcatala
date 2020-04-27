var $conjugador_form = jQuery('#conjugador_form');



jQuery('#_action_consulta').click(function(){
    jQuery('.typeahead').typeahead('close');

    var query = jQuery('#source').val();
    

    if (query) {
        jQuery("#loading").show();

        query = query.toLowerCase();

        var url_history = '/conjugador-de-verbs/verb/'+query+'/';
        history.pushState(null, null, url_history);

        jQuery("#content_header_title").html('Conjugador de verbs: «'+query+'»');

        update_share_links(query);

        //Data
        var post_data = new FormData();
        post_data.append('verb', query);
        post_data.append('action', 'conjugador_search');
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
        jQuery('#results').html('Introduïu un verb per conjugar');
    }
});

function print_results(result) {
    sc_sendTracking(true);
    jQuery("#loading").hide();
    jQuery('#results').html(result.html);
    jQuery('#results').slideDown();
}

function ko_function(result) {
    sc_sendTracking(false, result.status);
    jQuery("#loading").hide();
    jQuery('#results').html(result.responseJSON.html);
    jQuery('#results').slideDown();
}

function sc_sendTracking(success, status) {
    if (typeof(ga) == 'function')
    {
        var url = success ? '' : status;

        url += document.location.pathname;

       ga('send', 'pageview', url);
    }
}
//Function to update share links on ajax calls
function update_share_links(query) {
    var url = window.location.href;
    var url_facebook = 'https://www.facebook.com/sharer/sharer.php?u=' + url;
    var url_twitter = 'https://twitter.com/intent/tweet?text=Conjugació del verb ' + query + ' al conjugador de Softcatalà ' + url;

    jQuery('#share_facebook').attr("href", url_facebook);
    jQuery('#share_twitter').attr("href", url_twitter);
}

//Autocomplete
jQuery('#source').typeahead(
    {
        minLength: 3,
        hint: true,
    },
    {
        delay: 3500,
        limit: 12,
        async: true,
        source: function(query, processSync, processAsync) {
            
            var xurl = scajax.autocomplete_url + query;
            console.log(xurl);
            
            jQuery.ajax({
              url: xurl,
              dataType: "json",
              success: function( data ) {
                console.log(data);
                return processAsync ( data );

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
    jQuery('#_action_consulta').trigger('click');
});

var $conjugador_form = jQuery('#conjugador_form');

$conjugador_form.on('submit', function(ev) {
    ev.preventDefault();
    jQuery('#infinitiu').val('');
    jQuery('#verburl').val('');
    jQuery('#_action_consulta').trigger('click');
});

jQuery('#_action_consulta').click(function(){
    jQuery('#infinitiu').val('');
    jQuery('#verburl').val('');
    do_ajax();
});

function do_ajax (){
    
    jQuery('.typeahead').typeahead('close');
    
    var verb_form = jQuery('#source').val();
    var infinitiu = jQuery('#infinitiu').val();
    var verburl = jQuery('#verburl').val();
    var ajaxquery = true;

    if (verb_form) {
        
        jQuery("#loading").show();

        verb_form = verb_form.toLowerCase();
        infinitiu = infinitiu.toLowerCase();
        verburl = verburl.toLowerCase();

        //Data
        var post_data = new FormData();
        post_data.append('verb', verb_form);
        post_data.append('ajaxquery', ajaxquery);
        post_data.append('infinitiu', infinitiu);
        post_data.append('url', verburl);
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
}

function print_results(result) {
    var url_history = result.canonical;
    history.pushState(null, null, url_history);
    update_share_links(result.canonical);
    sc_sendTracking(true);
    jQuery('#source').val('');
    jQuery('#source').typeahead('val', '');
    jQuery('#infinitiu').focus();
    jQuery("#loading").hide();
    jQuery("#content_header_title").html(result.content_title);
    jQuery('#resultats-conjugador').html(result.html);
    document.title = result.title;
    jQuery('#resultats-conjugador').slideDown();
}

function ko_function(result) {
    var url_history = result.responseJSON.canonical;
    history.pushState(null, null, url_history);
    sc_404sendTracking(false, result.responseJSON.status, result.responseJSON.description);
    jQuery('#source').focus();
    jQuery("#content_header_title").html(result.responseJSON.content_title);
    document.title = result.responseJSON.title;
    jQuery("#loading").hide();
    jQuery('#resultats-conjugador').html(result.responseJSON.html);
    jQuery('#resultats-conjugador').slideDown();
}
function sc_404sendTracking(success, status, verb) {
    if (typeof(ga) == 'function')
    {
        var url = success ? '' : status;

        url += document.location.pathname + verb;

       ga('send', 'pageview', url);
    }
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
        hint: false,
    },
    {
        delay: 3600,
        limit: 200,
        async: true,
        source: function(query, processSync, processAsync) {
            
            var xurl = scajax.autocomplete_url + query;
            //console.log(xurl);
            
            jQuery.ajax({
              url: xurl,
              dataType: "json",
              success: function( data ) {
                items = [];
                infinitives = {};
                form_verb = {};
                url = {}
                
                var i = 0;

                data.forEach(function(verb) {
                
                    str = verb.verb_form + ' (' + verb.infinitive + ')'
                    infinitives[str] = verb.infinitive;
                    form_verb[str] = verb.verb_form;
                    url[str] = verb.url;
                    items.push(str);
                
                });
                
                return processAsync ( items );

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
        
        
        var regExp = /\(([^)]+)\)/;
        var matches = regExp.exec(item);
        
        
        if(!infinitives[item])
            infinitives[item] = matches[1]

        if(!form_verb[item])
            form_verb[item] = matches[1]

        if(!url[item])
            url[item] = matches[1]
        
        jQuery('#infinitiu').val(infinitives[item]);
        jQuery('#verburl').val(url[item]);
        jQuery('#source').val(form_verb[item]);
        jQuery('#source').typeahead('val', form_verb[item]);
        //jQuery('#_action_consulta').trigger('click');
        do_ajax();
}
);
       
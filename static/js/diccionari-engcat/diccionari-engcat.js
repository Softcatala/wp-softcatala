/** Diccionari catala angles **/

var diccionari_engcat_form = jQuery('#diccionari_engcat_form');


diccionari_engcat_form.on('submit', function(ev) {
    ev.preventDefault();
    jQuery('#_action_consulta_diccionari_engcat').trigger('click');
});

jQuery('#toggle_llengua_btn').on('click', function () {

        let $select = jQuery('#llengua_diccionari_engcat');
        let current = $select.val();

        if (current === 'cat') {
            $select.val('eng');
        } else {
            $select.val('cat');
        }
      
        $select.trigger('change');
    });


jQuery('#_action_consulta_diccionari_engcat').click(function(){
    
    jQuery("#loading").show();
    
    var query = jQuery('#cerca_diccionari_engcat').val();
    var llengua = jQuery('#llengua_diccionari_engcat').val();
       
    query = query.trim().replace("'", "’");

    if (!query || (llengua !== "cat" && llengua !== "eng") ) {
        jQuery("#loading").hide();
        return;
    }
    
    /*
    var url_history = '/diccionari-angles-catala/' + llengua + '/paraula/'+query+'/';
    history.pushState(null, null, url_history);

    let title = 'Diccionari català-anglès'; // per defecte

    if (llengua === 'eng') {
        title = 'Diccionari anglès-català';
    }

    jQuery("#content_header_title").html(title + ': «' + query + '»');

    update_share_links(query);
    */
    var post_data = new FormData();
        post_data.append('paraula', query);
        post_data.append('llengua', llengua);
        post_data.append('action', 'diccionari_engcat_search');
       
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

});

function print_results(result) {
    var url_history = result.canonical;
    history.pushState(null, null, url_history);
    update_share_links(result.canonical);
    sc_sendTracking(true);
    jQuery('#cerca_diccionari_engcat').val('');
    jQuery("#loading").hide();
    jQuery("#content_header_title").html(result.content_title);
    jQuery('.diccionari-resultat').html(result.html);
    document.title = result.title;
    jQuery('.diccionari-resultat').slideDown();
    console.log(result)
}

function ko_function(result) {
    
    var url_history = result.responseJSON.canonical;
    history.pushState(null, null, url_history);
    sc_404sendTracking(false, result.responseJSON.status, result.responseJSON.description);
    jQuery('#cerca_diccionari_engcat').focus();
    jQuery("#content_header_title").html('Diccionari anglès-català');
    document.title = result.content_title;
    jQuery("#loading").hide();
    jQuery('.diccionari-resultat').html(result.responseJSON.html);
    jQuery('.diccionari-resultat').slideDown();
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
    var url_twitter = 'https://twitter.com/intent/tweet?text=Sinònims de la paraula ' + query + ' al diccionari de sinònims de Softcatalà ' + url;

    jQuery('#share_facebook').attr("href", url_facebook);
    jQuery('#share_twitter').attr("href", url_twitter);
}


// Show and hide corpus

jQuery(document).on('click', '.mostra_corpus', function(e) {
    e.preventDefault(); 
    var $this = jQuery(this);
    var $corpus_hidden = $this.closest('table').find('tr.corpus_hidden'); 
    $corpus_hidden.toggle();
    $this.text($corpus_hidden.is(':visible') ? 'Mostra menys exemples' : 'Mostra més exemples')
});
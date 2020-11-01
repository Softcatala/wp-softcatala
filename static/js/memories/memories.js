jQuery(document).ready(function(){
    tryPredefinedQuery();
    jQuery(".chosen").chosen({width: "100%"});
    jQuery("#search-samples-area").click(showHelp);
});

function showHelp() {
    jQuery('#search-samples').toggle("slow");
}

function searchMemories(source, target, project, page) {
    url = "https://api.softcatala.org/memories/v1/search?";

    toSearch = false;
    if(source) {
        url +=  '&source=' + source;
        toSearch = true;
    }

    if(target) {
        url +=  '&target=' + target;
        toSearch = true;
    }

    if(source) {
        url +=  '&project=' + project;
        toSearch = true;
    }

    if(toSearch) {
        url += '&page=' + page
        jQuery.ajax({
            url: url,
            type: 'GET',
            success : print_results,
            error : ko_function
        });

        jQuery('#show-more').data('page', page);
        return true;
    } else {
        return false;
    }
}

jQuery('#show-more').click(function(e) {
    e.preventDefault();
    page = jQuery(this).data('page');
    max = jQuery(this).data('max');
    if(page > 0 && page < max && document.location.search) {
        s = new URLSearchParams(document.location.search)
        searchMemories(s.get('source'), s.get('target'), s.get('project'), page+1);
    }
});

jQuery('#memories').submit(function(event) {

    event.preventDefault();

    const source = jQuery("#source").val();
    const target = jQuery("#target").val();
    const project = jQuery("#project").val();
    jQuery('#search-results').html('');

    const params = new URLSearchParams(location.search);
    params.set('source', source);
    params.set('target', target);
    params.set('project', project);
    window.history.pushState({}, '', `${location.pathname}?${params}`);

    searched = searchMemories(source, target, project, 1);

    if (!searched) {
        jQuery('#search-results').hide();
        jQuery('#search-results').remove();
    }
});

function print_results(results) {
    page = jQuery('#show-more').data('page');

    jQuery('#search-results')
        .append(jQuery(`<a name="page-${page}"></a>`));

    jQuery('#search-results').show();

    if (results.num_results == 0) {
        jQuery('#search-results')
            .append(jQuery(`<div><em>No hi ha resultat amb els termes de cerca</em></div>`));
        return;
    }

    if (page == 1) {
        jQuery('#search-results')
            .append(jQuery(`<div><em>S'han trobat ${results.num_results} resultats</em></div>`));
    }

    results.results.forEach(function(r) {
        project = `<tr>
                    <td>Projecte</td>
                    <td>${r.project}</td>
                    </tr>`;
        comments = r.context ?
            `<tr style="font-size:0.9em"><td>Comentaris:</td><td>${r.comment}</td></tr>` :
            '';

        source = `<tr>
                    <td><b>Original:</b></td>
                    <td>${r.source}</td>
                    </tr>
                    <tr>`;

        target = `<td><b>Traducció:</b></td>
                    <td>${r.target}</td>
                    </tr>`
        h = jQuery(`
            <div class="single-result">
                <table class="table table-bordered taula-2col" style="margin:10px">
                    <tbody>
                       ${project}${comments}${source}${target}
                    </tbody>
                </table>
            </div>
        `)
        jQuery('#search-results').append(h);
    });

    pos = jQuery(`a[name="page-${page}"]`);
    jQuery('html,body').animate({scrollTop: pos.offset().top},'slow');

    if(page <= results.pages) {
        jQuery('#show-more').data('max', results.pages);

        const params = new URLSearchParams(location.search);
        params.set('page', page);
        window.history.pushState({}, '', `${location.pathname}?${params}#page-${page}`);

        if (page<results.pages) {
            jQuery('#show-more').show();
        } else {
            jQuery('#show-more')
                .data('page', 0)
                .data('max', 0)
                .hide();
        }
    } else {
        window.history.pushState({}, '', `${location.pathname}`);
        jQuery('#show-more')
            .data('page', 0)
            .data('max', 0)
            .hide();
    }
}

function ko_function(e) {
    console.log('error', e);
    window.history.pushState({}, '', `${location.pathname}`);
    jQuery('#show-more')
        .data('page', 0)
        .data('max', 0)
        .hide();
}


function tryPredefinedQuery() {
    if(document.location.search) {
        s = new URLSearchParams(document.location.search)
        fillForm(s.get('source'), s.get('target'), s.get('project'))
        searchMemories(s.get('source'), s.get('target'), s.get('project'), s.get('page') || 1);
    }
}

function fillForm(source, target, project) {
    jQuery('#source').val(source);
    jQuery('#target').val(target);
    projects = project.split(',');
    for(const pr of projects) {
        jQuery(`#project option[value=${pr}]`).attr('selected', 'selected')
    }
}

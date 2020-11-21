jQuery(document).ready(function(){
    tryPredefinedQuery();
    jQuery(".chosen").chosen({width: "100%"});
    jQuery("#search-samples-area a.show-samples").click(showHelp);
    jQuery('#search-samples a').click(function(e) {
        e.preventDefault();
        tryPredefinedQuery(jQuery(this).data('search'));
    });
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

    if(project) {
        url +=  '&project=' + project;
        toSearch = true;
    }

    if(toSearch) {

        const params = new URLSearchParams();
        if(source)params.set('source', source);
        if(target)params.set('target', target);
        if(project)params.set('project', project);

        url += '&page=' + page
        jQuery.ajax({
            url: url,
            type: 'GET',
            success : print_results,
            error : ko_function
        });

        jQuery('#show-more').data('pg', page);
        return true;
    } else {
        return false;
    }
}

jQuery('#show-more').click(function(e) {
    e.preventDefault();
    page = jQuery(this).data('pg');
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

    searched = searchMemories(source, target, project, 1);

    if (!searched) {
        jQuery('#search-results').hide();
        jQuery('#search-results').remove();
    }
});

function print_results(results) {
    page = jQuery('#show-more').data('pg');

    jQuery('#search-results')
        .append(jQuery(`<a name="pg-${page}"></a>`));

    jQuery('#search-results').show();

    if (results.glossary) {
        const entries = results.glossary.map(e => {
            return `${e.translation} (usada ${parseFloat(e.percentage).toFixed(2)}, coincidències ${e.frequency})`
        }).join();
        const glossary = `
            <div>
                <strong>Resum de l'extracció automàtica terminològica sobre el corpus de Softcatalà del terme:</strong>
                ${entries}
                <div class="glossary-llegenda">
                    Llegenda:<br />
                    Per a cada opció, entre parèntesis, usada indica el percentatge d'ús respecte a altres opcions i 
                    coincidències els cops que s'ha trobat en cadenes de 3 o menys paraules.<br />
                    Les formes en <span class="word-termcat">color verd</span> són les documentades en els recursos 
                    en línia del TERMCAT.
                </div>
            </div>
        `;
        jQuery('#search-results').append(jQuery(glossary));
    }

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

    pos = jQuery(`a[name="pg-${page}"]`);
    jQuery('html,body').animate({scrollTop: pos.offset().top},'slow');

    if(page <= results.pages) {
        jQuery('#show-more').data('max', results.pages);

        const params = new URLSearchParams(location.search);
        params.set('pg', page);
        window.history.pushState({}, '', `${location.pathname}?${params}`);

        if (page<results.pages) {
            jQuery('#show-more').show();
        } else {
            jQuery('#show-more')
                .data('pg', 0)
                .data('max', 0)
                .hide();
        }
    } else {
        window.history.pushState({}, '', `${location.pathname}`);
        jQuery('#show-more')
            .data('pg', 0)
            .data('max', 0)
            .hide();
    }
}

function ko_function(e) {
    console.log('error', e);
    window.history.pushState({}, '', `${location.pathname}`);
    jQuery('#show-more')
        .data('pg', 0)
        .data('max', 0)
        .hide();
}


function tryPredefinedQuery(u) {
    if (!u && document.location.search) {
        u = document.location.search
    }

    if (u) {
        s = new URLSearchParams(u)
        fillForm(s.get('source'), s.get('target'), s.get('project'))
        jQuery('#memories').submit();
    }
}

function fillForm(source, target, project) {
    jQuery('#source').val(source);
    jQuery('#target').val(target);
    if(project) {
        projects = project.split(',');
        for(const pr of projects) {
            jQuery(`#project option[value=${pr}]`).attr('selected', 'selected')
        }
    }
}


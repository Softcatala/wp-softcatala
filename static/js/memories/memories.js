jQuery(document).ready(function(){
    jQuery(".chosen").chosen();
    jQuery("#search-samples-area").click(showHelp);
});

function showHelp() {
    jQuery('#search-samples').toggle("slow");
}

jQuery('#memories').submit(function(event) {
    url = "https://api.softcatala.org/memories/v1/search?";
    event.preventDefault();
    const source = jQuery("#source").val();
    const target = jQuery("#target").val();
    const project = jQuery("#project").val();

    toSearch = false;
    if(source) {
        url +=  'source=' + source;
        toSearch = true;
    }

    if(target) {
        url +=  'target=' + target;
        toSearch = true;
    }

    if(source) {
        url +=  'project=' + project;
        toSearch = true;
    }

    if(toSearch) {
        jQuery.ajax({
            url: url,
            type: 'GET',
            success : print_results,
            error : ko_function
        });

    } else {
        jQuery('#search-results').hide();
        jQuery('#search-results').remove();
    }
});

function print_results(results) {
    results.results.forEach(function(r) {
        project = `<tr>
                    <td>Projecte</td>
                    <td>${r.project}</td>
                    </tr>`;
        comments = r.context ?
            `<tr><td>Comentaris:</td><td>${r.comments}<br></td></tr>` :
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
                <table class="table table-bordered taula-2col">
                    <tbody>
                       ${projects}${comments}${source}${target}
                    </tbody>
                </table>
            </div>
        `)
        jQuery('#search-results').append(h)
    });
    jQuery('#search-results').show();
}

function ko_function(e) {
    console.log('error', e);
}
(function( $ ) {

    function extractSections( content, delimiter ) {

        var headerGroups = jQuery(content).find(delimiter);

        var sections = [];

        for ( index = 0; index < headerGroups.length; index++ ) {

            var range = document.createRange();

            range.setStartBefore(headerGroups[index]);

            if (index < headerGroups.length - 1) {
                range.setEndBefore(headerGroups[index + 1]);
            } else {
                range.setEndAfter(jQuery(content).children().last().get(0));
            }

            var newSectionContent = range.extractContents();

            sections.push( newSectionContent );
        }

        return sections;
    }

    function extractSubsections ( section, delimiter ) {

        var subsections = [];

        var subsectionHeaders = jQuery(section).find(delimiter);

        for ( index = 0; index < subsectionHeaders.length; index++) {

            var range = document.createRange();

            range.setStartBefore(subsectionHeaders[index]);

            if (index < subsectionHeaders.length - 1) {
                range.setEndBefore(subsectionHeaders[index + 1]);
            } else {
                range.setEndAfter(jQuery(section).children().last().get(0));
            }

            var subsection = range.extractContents();

            subsections.push( subsection );
        }

        return subsections;
    }

    var innerSection = jQuery('.contingut .contingut-section');

    var sections = 1;

    var links = [];

    var first = true;

    var backToTop = '<div class="row"><div class="col-sm-12"><a class="bt-basic bt-basic-petit bt-up" href="#principi">Ves al principi</a></div></div><hr>';

    var lastH2 = 0;

    var anchorId = 0;

    var newSections = [];

    var h2sections = extractSections( innerSection, 'h2' );

    for ( sectionIndex = 0; sectionIndex < h2sections.length; sectionIndex++ ) {

        var subsections = extractSubsections( h2sections[sectionIndex], 'h3');

        var newSection = jQuery('<section>').addClass('contingut-section');

        newSection.append( jQuery( h2sections[sectionIndex] ).find('h2') );

        for ( subIndex = 0; subIndex < subsections.length; subIndex++ ) {

            anchorId++;

            var newSecctionInner = jQuery('<article>').addClass("contingut-article").attr('id', 'pmf-'+anchorId);

            newSecctionInner.append( subsections[subIndex] );

            newSection.append( newSecctionInner );
        }

        newSections.push( newSection );
    }

    anchorId = 0;
    newSections.forEach(function ( s2 ) {

        var h2 = jQuery(s2).find('h2');

        var text = h2.text();

        var h3 = jQuery(s2).find('h3');

        items = [];


        h3.each(function() {

            jQuery(this).attr('name')

            anchorId++;

            items.push(
                {
                    'text' : jQuery(this).text(),
                    'link' : "pmf-" + anchorId
                }
            )
        })

        links.push( { 'text': text, 'items' : items  } );

    });

    if ( links.length > 0 ) {
        jQuery('.contingut').addClass( 'pmf' );

        var html = '<a href="#llista-preguntes" class="bt-collapse-pmf" data-toggle="collapse"><i class="fa fa-align-right"></i></a>';

        html += '<nav class="nav-anchor collapse in" id="llista-preguntes">';

        for ( i = 0; i < links.length; ++i) {

            html += '<h2>' + links[i].text + '</h2>';

            html += '<ul class="nav" role="navigation">';

            for (j = 0; j < links[i].items.length; ++j) {

                html += '<li><a href="#' + links[i].items[j].link + '"><i class="fa fa-caret-right"></i>' + links[i].items[j].text + '</a></li>';

            }

            html += '</ul>';

        }
        html += '</nav>'
    }

    jQuery('.contingut .contingut-header').attr('id','principi').append(html);

    reverseSections = newSections.reverse();

    reverseSections.forEach(function (s) {
        jQuery(s).insertAfter(innerSection);
    });

    jQuery('.contingut .contingut-section').each( function() {

            hasSubsections = jQuery(this).find('.contingut-article').size() > 0;

            if(hasSubsections) {
                lastSubsection = jQuery(this).find('.contingut-article').last();
                jQuery(lastSubsection).append(backToTop);
            }
        }
    )

    jQuery("#llista-preguntes").collapse();

})(jQuery);

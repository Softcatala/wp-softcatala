//Constant variables

var traductor_json_url = "https://www.softcatala.org/apertium/json/translate";

(function($) {
//Set the initial default pairs on document ready
jQuery(document).ready(function(){
    jQuery('#origin_language').val('spa');
    jQuery('#target_language').val('cat');

    //Left
    jQuery('#origin-spa').addClass('select');
    jQuery('#origin-cat').removeClass('select');
    jQuery('[data-id="origin-select"]').removeClass('select');

    //Right
    jQuery('#target-spa').attr('disabled', 'disabled');
    jQuery('#target-spa').removeClass('select');
    jQuery('[data-id="target-select"]').removeClass('select');
    jQuery('[data-id="target-select"]').attr('disabled', 'disabled');
    jQuery('#target-cat').removeAttr('disabled');
    jQuery('#target-cat').addClass('select');

    //Mobile (left-right)
    jQuery('#origin-select-mobil').val('spa');
    jQuery('#target-select-mobil').val('cat');
    jQuery('#origin-select-mobil').selectpicker('render');
    jQuery('#target-select-mobil').selectpicker('render');
    jQuery('.selectpicker').selectpicker('refresh');

    /** Workaround to solve the issue when the selected language is the same marked in the dropdown **/
    jQuery('div#div_select_origin  ul.dropdown-menu.inner li').on('click', function() {
        jQuery('#origin-select').trigger('change');
    });

    jQuery('div#div_select_target  ul.dropdown-menu.inner li').on('click', function() {
        jQuery('#target-select').trigger('change');
    });
    /** End workaround **/

    //Timer for instant translation
    var timer,
        lastPunct = false, punct = [46, 33, 58, 63, 47, 45, 190, 171, 49],
        timeoutPunct = 1000, timeoutOther = 3000;
    jQuery('.primer-textarea').on('keyup paste', function (event) {
        if(lastPunct && event.keyCode === 32 || event.keyCode === 13) {
            // Don't override the short timeout for simple space-after-punctuation
            return;
        }

        if(timer) {
            clearTimeout(timer);
        }

        var timeout;
        if(punct.indexOf(event.keyCode) !== -1) {
            timeout = timeoutPunct;
            lastPunct = true;
        }
        else {
            timeout = timeoutOther;
            lastPunct = false;
        }

        timer = setTimeout(function () {
            translateText();
        }, timeout);
    });
});

/** Set the different language pairs and update menus depending on user clicks **/
//Desktop selectors
jQuery('#origin-cat').click(function() {
    var prev_origin_language = jQuery('#origin_language').val();
    var final_target_language = 'spa';

    set_origin_language('cat');

    set_origin_button('cat');
    set_origin_button_mobile('cat');

    if(prev_origin_language != 'cat') {
        final_target_language = prev_origin_language;
    }
    set_target_language(final_target_language);
    set_target_button(final_target_language);
    set_target_button_mobile(final_target_language);

    toggle_formes_valencianes('off');
});

jQuery('#origin-spa').click(function() {
    set_origin_language('spa');
    set_target_language('cat');

    set_origin_button('spa');
    set_origin_button_mobile('spa');

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('on');
});

jQuery('#origin-select').on('change', function() {
    var origin_language = jQuery('#origin-select').val();
    set_origin_language(origin_language);
    set_target_language('cat');

    set_origin_button(origin_language);
    set_origin_button_mobile(origin_language);

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('off');
});

jQuery('#target-spa').click(function() {
    set_target_language('spa');
    set_target_button('spa');
    set_target_button_mobile('spa');
});

jQuery('#target-select').on('change', function() {
    var target_language = jQuery('#target-select').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Mobile selectors
jQuery('#origin-select-mobil').on('change', function() {
    var prev_origin_language = jQuery('#origin_language').val();
    var final_target_language = 'cat';
    var origin_language = jQuery('#origin-select-mobil').val();
    set_origin_language(origin_language);

    set_origin_button(origin_language);
    set_origin_button_mobile(origin_language);

    if(origin_language == 'cat') {
        final_target_language = prev_origin_language;
    }
    set_target_language(final_target_language);
    set_target_button(final_target_language);
    set_target_button_mobile(final_target_language);

    if(origin_language == 'spa') {
        toggle_formes_valencianes('on');
    } else {
        toggle_formes_valencianes('off');
    }
});

jQuery('#target-select-mobil').on('change', function() {
    var target_language = jQuery('#target-select-mobil').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Direction change
jQuery('.direccio').on('click', function() {
    var new_target_language = jQuery('#origin_language').val();
    var new_origin_language = jQuery('#target_language').val();

    if(new_origin_language == 'spa') {
        toggle_formes_valencianes('on');
    } else {
        toggle_formes_valencianes('off');
    }

    set_target_language(new_target_language);
    set_origin_language(new_origin_language);

    set_origin_button(new_origin_language);
    set_origin_button_mobile(new_origin_language);

    set_target_button(new_target_language);
    set_target_button_mobile(new_target_language);

    exchange_texts();
});
/** End setting different language pairs **/


/** Translation AJAX action and other related actions **/
jQuery('#translate').click(function() {
    var text = jQuery('.primer-textarea').val();
    if (text.length) {
        var origin_language = jQuery('#origin_language').val();
        var target_language = jQuery('#target_language').val();
        var valencian_forms = (jQuery('#formes_valencianes:checked').length)?'_valencia':'';
        var adapted_target_language = target_language;
        if (origin_language == 'spa') {
            adapted_target_language = target_language.replace("cat","cat"+valencian_forms);
        }

        var langpair = origin_language+"|"+adapted_target_language;
        var muk = (jQuery('#mark_unknown:checked').length)?'yes':'no';

        $.ajax({
            url:traductor_json_url,
            type:"POST",
            data : {'langpair':langpair,'q':text,'markUnknown':muk,'key':'DjnAT2hnZKPHe98Ry/s2dmClDbs'},
            dataType: 'json',
            success : trad_ok,
            failure : trad_ko
        });

        return false;
    } else {
        alert('Introduïu algun text');
    }
});

function nl2br(text) {
    text=escape(text);
    return unescape(text.replace(/(%5Cr%5Cn)|(%5Cn%5Cr)|%0A|%5Cr|%5Cn/g,'<br />'));
}

function trad_ok(dt) {
    if(dt.responseStatus==200) {
        translation = nl2br(dt.responseData.translatedText);
        translation_coloured = translation.replace(/\*([^.,;:\t ]+)/gi,"<span style='background-color: #f6f291'>$1</span>").replace('*', '');
        jQuery('.second-textarea').html(translation_coloured);

        if(jQuery(".second-textarea").height() < '270') {
            jQuery('html, body').animate({
                scrollTop: jQuery(".second-textarea").offset().top
            }, 2000);
        }
    } else {
        trad_ko();
    }
}

function trad_ko(dt) {
    //Aquesta funció d'error s'ha de moure per a que siga global a tot el web, per cada vegada que es vulga fer un avís
    var error_title = 'Sembla que alguna cosa no ha funcionat com calia';
    var error_txt = 'S\'ha produït un error en executar la traducció. Proveu de nou ara o més tard. Si el problema persisteix, contacteu amb nosaltres mitjançant el formulari d\'ajuda.';
    jQuery('#error_title').html(error_title);
    jQuery('#error_description').html(error_txt);
    jQuery('#error_pagina').trigger('click');
}

/* This function just calls the translation */
function translateText() {
    if( jQuery('.primer-textarea').val() == '' ) {
        jQuery(".second-textarea").html(''); //just empty the second area
    } else {
        jQuery('#translate').trigger('click');
    }
}

jQuery('#mark_unknown').click(function() {
    translateText();
});
/** End translation AJAX action **/


/** Start functions to set the different language pairs and update menus depending on user clicks **/
function toggle_formes_valencianes(status) {
    if ( status == 'on' ) {
        //Enable 'formes valencianes' checkbox
        jQuery('#formes_valencianes').removeAttr('disabled');
        jQuery('#formes_valencianes_label').css( "color", "#333" );
    } else {
        //Disable 'formes valencianes' checkbox
        jQuery('#formes_valencianes').attr('disabled', 'disabled');
        jQuery('#formes_valencianes_label').css( "color", "#AAA" );
    }
}

function set_origin_language( language ) {
    jQuery('#origin_language').val(language);
}

function set_target_language ( language ) {
    jQuery('#target_language').val(language);
}

function set_origin_button ( language ) {
    if ( language == 'spa' ) {
        jQuery('#origin-spa').addClass('select');
        jQuery('#origin-cat').removeClass('select');
        jQuery('[data-id="origin-select"]').removeClass('select');
    } else if ( language == 'cat' ) {
        jQuery('#origin-cat').addClass('select');
        jQuery('#origin-spa').removeClass('select');
        jQuery('[data-id="origin-select"]').removeClass('select');
    } else {
        jQuery('[data-id="origin-select"]').addClass('select');
        jQuery('#origin-spa').removeClass('select');
        jQuery('#origin-cat').removeClass('select');

        jQuery('#origin-select').val( language );
        jQuery('#origin-select').selectpicker('render');
    }
}

function set_origin_button_mobile ( language ) {
    jQuery('#origin-select-mobil').val(language);
    jQuery('#origin-select-mobil').selectpicker('render');

    if( language == 'cat' ) {
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', '');
        jQuery("#target-select-mobil option[value='cat']").css('display', 'none');
        jQuery('.selectpicker-mobil').selectpicker('refresh');
    } else {
        jQuery("#target-select-mobil option[value='cat']").css('display', '');
        jQuery('.selectpicker-mobil').selectpicker('refresh');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
    }
}

function set_target_button ( language ) {
    if( language == 'cat' ) {
        jQuery('#target-cat').removeAttr('disabled', 'disabled');
        jQuery('#target-cat').addClass('select');
        jQuery('#target-spa').removeClass('select');
        jQuery('#target-spa').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').removeClass('select');
        jQuery('[data-id="target-select"]').attr('disabled', 'disabled');
    } else if ( language == 'spa' ) {
        jQuery('#target-spa').removeAttr('disabled', 'disabled');
        jQuery('#target-spa').addClass('select');
        jQuery('#target-cat').removeClass('select');
        jQuery('#target-cat').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').removeClass('select');
        jQuery('[data-id="target-select"]').removeAttr('disabled', 'disabled');
    } else {
        jQuery('#target-select').val( language );
        jQuery('#target-spa').removeAttr('disabled');
        jQuery('#target-spa').removeClass('select');
        jQuery('#target-cat').removeClass('select');
        jQuery('#target-cat').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').addClass('select');
        jQuery('[data-id="target-select"]').removeAttr('disabled', 'disabled');
        jQuery('#target-select').selectpicker('render');
    }
}

function set_target_button_mobile ( language ) {
    jQuery('#target-select-mobil').val( language );

    //Don't display other options than 'cat' in case Catalan is the target language
    if( language == 'cat' ) {
        jQuery("#target-select-mobil option[value='cat']").css('display', '');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
        jQuery('#div_select_target_mobile .filter-option').next("span").removeClass('caret');
    } else {
        jQuery("#target-select-mobil option[value='cat']").css('display', 'none');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', '');
        jQuery('#div_select_target_mobile .filter-option').next("span").addClass('caret');
    }
    jQuery('#target-select-mobil').selectpicker('render');
}

function exchange_texts() {
    var translation_text = jQuery('.second-textarea').html();
    var original_text = jQuery('.primer-textarea').val();
    jQuery('.second-textarea').html(original_text);
    jQuery('.primer-textarea').val(translation_text.replace(/<(?:.|\n)*?>/gm, ''));
}
/** End functions related to language pairs change **/

})(jQuery);

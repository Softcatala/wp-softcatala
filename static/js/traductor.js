//Constant variables

var traductor_json_url = "http://www.softcatala.org/apertium/json/translate";


//Set the initial default pairs on document ready
jQuery(document).ready(function(){
    $('#origin_language').val('cat');
    $('#target_language').val('spa');

    //Left
    $('#origin-cat').addClass('select');
    $('#origin-spa').removeClass('select');
    $('[data-id="origin-select"]').removeClass('select');

    //Right
    $('#target-cat').attr('disabled', 'disabled');
    $('#target-spa').removeAttr('disabled');
    $('[data-id="target-select"]').removeClass('select');
    $('[data-id="target-select"]').removeAttr('disabled');

    //Mobile (left-right)
    $('#origin-select-mobil').val('cat');
    $('#target-select-mobil').val('spa');
    $('#origin-select-mobil').selectpicker('render');
    $('#target-select-mobil').selectpicker('render');
    $('.selectpicker').selectpicker('refresh');

    /** Workaround to solve the issue when the selected language is the same marked in the dropdown **/
    $('div#div_select_origin  ul.dropdown-menu.inner li').on('click', function() {
        $('#origin-select').trigger('change');
    });

    $('div#div_select_target  ul.dropdown-menu.inner li').on('click', function() {
        $('#target-select').trigger('change');
    });
    /** End workaround **/

    //Timer for instant translation
    var timer,
        lastPunct = false, punct = [46, 33, 58, 63, 47, 45, 190, 171, 49],
        timeoutPunct = 1000, timeoutOther = 3000;
    $('.primer-textarea').on('keyup paste', function (event) {
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
$('#origin-cat').click(function() {
    var prev_origin_language = $('#origin_language').val();
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

$('#origin-spa').click(function() {
    set_origin_language('spa');
    set_target_language('cat');

    set_origin_button('spa');
    set_origin_button_mobile('spa');

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('on');
});

$('#origin-select').on('change', function() {
    var origin_language = $('#origin-select').val();
    set_origin_language(origin_language);
    set_target_language('cat');

    set_origin_button(origin_language);
    set_origin_button_mobile(origin_language);

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('off');
});

$('#target-spa').click(function() {
    set_target_language('spa');
    set_target_button('spa');
    set_target_button_mobile('spa');
});

$('#target-select').on('change', function() {
    var target_language = $('#target-select').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Mobile selectors
$('#origin-select-mobil').on('change', function() {
    var prev_origin_language = $('#origin_language').val();
    var final_target_language = 'cat';
    var origin_language = $('#origin-select-mobil').val();
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

$('#target-select-mobil').on('change', function() {
    var target_language = $('#target-select-mobil').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Direction change
$('.direccio').on('click', function() {
    var new_target_language = $('#origin_language').val();
    var new_origin_language = $('#target_language').val();

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
$('#translate').click(function() {
    var text = $('.primer-textarea').val();
    if (text.length) {
        var origin_language = $('#origin_language').val();
        var target_language = $('#target_language').val();
        var valencian_forms = ($('#formes_valencianes:checked').length)?'_valencia':'';
        var adapted_target_language = target_language;
        if (origin_language == 'spa') {
            adapted_target_language = target_language.replace("cat","cat"+valencian_forms);
        }

        var langpair = origin_language+"|"+adapted_target_language;
        var muk = ($('#mark_unknown:checked').length)?'yes':'no';

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
        $('.second-textarea').html(translation_coloured);

        if($(".second-textarea").height() < '270') {
            $('html, body').animate({
                scrollTop: $(".second-textarea").offset().top
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
    $('#error_title').html(error_title);
    $('#error_description').html(error_txt);
    $('#error_pagina').trigger('click');
}

/* This function just calls the translation */
function translateText() {
    if( $('.primer-textarea').val() == '' ) {
        $(".second-textarea").html(''); //just empty the second area
    } else {
        $('#translate').trigger('click');
    }
}

$('#mark_unknown').click(function() {
    translateText();
});
/** End translation AJAX action **/


/** Contact form action **/
var $contactForm = $('#report_form');

$contactForm.on('submit', function(ev){
    ev.preventDefault();

    //Data
    post_data = {
        'nom'       : $('input[name=nom]').val(),
        'correu'    : $('input[name=correu]').val(),
        'tipus'     : $('select[name=tipus]').val(),
        'comentari' : $('textarea[name=comentari]').val()
    };

    $.ajax({
        url:"/traductor",
        type:"POST",
        data : post_data,
        dataType: 'json',
        success : form_sent_ok,
        failure : form_sent_ko
    });
});

function form_sent_ok(dt) {
    if (dt.type == 'message') {
        $("#contingut-formulari").hide();
        $("#contingut-formulari-response").empty().html(dt.text).fadeIn();
    }
}

$('#contact_traductor').click(function() {
    $("#contingut-formulari-response").hide();
    $("textarea[name='comentari']").val('');
    $("#contingut-formulari").show();
});

function form_sent_ko(dt) {
    alert('Alguna cosa no ha funcionat bé en enviar les dades al servidor de traducció');
}
/** End contact form action **/


/** Start functions to set the different language pairs and update menus depending on user clicks **/
function toggle_formes_valencianes(status) {
    if ( status == 'on' ) {
        //Enable 'formes valencianes' checkbox
        $('#formes_valencianes').removeAttr('disabled');
        $('#formes_valencianes_label').css( "color", "#333" );
    } else {
        //Disable 'formes valencianes' checkbox
        $('#formes_valencianes').attr('disabled', 'disabled');
        $('#formes_valencianes_label').css( "color", "#AAA" );
    }
}

function set_origin_language( language ) {
    $('#origin_language').val(language);
}

function set_target_language ( language ) {
    $('#target_language').val(language);
}

function set_origin_button ( language ) {
    if ( language == 'spa' ) {
        $('#origin-spa').addClass('select');
        $('#origin-cat').removeClass('select');
        $('[data-id="origin-select"]').removeClass('select');
    } else if ( language == 'cat' ) {
        $('#origin-cat').addClass('select');
        $('#origin-spa').removeClass('select');
        $('[data-id="origin-select"]').removeClass('select');
    } else {
        $('[data-id="origin-select"]').addClass('select');
        $('#origin-spa').removeClass('select');
        $('#origin-cat').removeClass('select');

        $('#origin-select').val( language );
        $('#origin-select').selectpicker('render');
    }
}

function set_origin_button_mobile ( language ) {
    $('#origin-select-mobil').val(language);
    $('#origin-select-mobil').selectpicker('render');

    if( language == 'cat' ) {
        $('div.btns-llengues-desti .dropdown-menu').css('display', '');
        $("#target-select-mobil option[value='cat']").css('display', 'none');
        $('.selectpicker-mobil').selectpicker('refresh');
    } else {
        $("#target-select-mobil option[value='cat']").css('display', '');
        $('.selectpicker-mobil').selectpicker('refresh');
        $('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
    }
}

function set_target_button ( language ) {
    if( language == 'cat' ) {
        $('#target-cat').removeAttr('disabled', 'disabled');
        $('#target-cat').addClass('select');
        $('#target-spa').removeClass('select');
        $('#target-spa').attr('disabled', 'disabled');
        $('[data-id="target-select"]').removeClass('select');
        $('[data-id="target-select"]').attr('disabled', 'disabled');
    } else if ( language == 'spa' ) {
        $('#target-spa').removeAttr('disabled', 'disabled');
        $('#target-spa').addClass('select');
        $('#target-cat').removeClass('select');
        $('#target-cat').attr('disabled', 'disabled');
        $('[data-id="target-select"]').removeClass('select');
        $('[data-id="target-select"]').removeAttr('disabled', 'disabled');
    } else {
        $('#target-select').val( language );
        $('#target-spa').removeAttr('disabled');
        $('#target-spa').removeClass('select');
        $('#target-cat').removeClass('select');
        $('#target-cat').attr('disabled', 'disabled');
        $('[data-id="target-select"]').addClass('select');
        $('[data-id="target-select"]').removeAttr('disabled', 'disabled');
        $('#target-select').selectpicker('render');
    }
}

function set_target_button_mobile ( language ) {
    $('#target-select-mobil').val( language );

    //Don't display other options than 'cat' in case Catalan is the target language
    if( language == 'cat' ) {
        $("#target-select-mobil option[value='cat']").css('display', '');
        $('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
        $('#div_select_target_mobile .filter-option').next("span").removeClass('caret');
    } else {
        $("#target-select-mobil option[value='cat']").css('display', 'none');
        $('div.btns-llengues-desti .dropdown-menu').css('display', '');
        $('#div_select_target_mobile .filter-option').next("span").addClass('caret');
    }
    $('#target-select-mobil').selectpicker('render');
}

function exchange_texts() {
    var translation_text = $('.second-textarea').html();
    var original_text = $('.primer-textarea').val();
    $('.second-textarea').html(original_text);
    $('.primer-textarea').val(translation_text);
}
/** End functions related to language pairs change **/

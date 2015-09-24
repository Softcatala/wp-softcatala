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
    
    //Workaround to solve the issue when the selected language is the same marked in the dropdown
    $('.btns-llengues-origen .dropdown-menu li').on('click', function() {
        var origin_language = $('#origin-select').val();
        $('#origin-select').trigger('change');
    });
    
    $('.btns-llengues-desti .dropdown-menu li').on('click', function() {
        var target_language = $('#target-select').val();
        $('#target-select').trigger('change');
    });
    
    //Timer for instant translation
    var timer,
    lastPunct = false, punct = [46, 33, 58, 63, 47, 45, 190, 171, 49],
    timeoutPunct = 1000, timeoutOther = 3000;
    $('.primer-textarea').on('keyup paste', function (event) {
        if(lastPunct && event.keyCode === 32 || event.keyCode === 13) {
            // Don't override the short timeout for simple space-after-punctuation
            return;
        }

        if(timer)
            clearTimeout(timer);

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
$('#origin-cat').click(function() {
    //Left
    $('#origin_language').val('cat');
    $('#origin-cat').addClass('select');
    $('#origin-spa').removeClass('select');
    $('[data-id="origin-select"]').removeClass('select');
    
    //Rigth
    $('[data-id="target-select"]').removeAttr('disabled');
    $('#target-spa').removeAttr('disabled');
    
    if ($('#target_language').val() == 'cat') {
        $('#target-cat').attr('disabled', 'disabled');
        $('#target-cat').removeClass('select');
        $('#target_language').val('spa');
        $('#target-spa').addClass('select');
    }
    
    //Disable 'formes valencianes' checkbox
    $('#formes_valencianes').attr('disabled', 'disabled');
    $('#formes_valencianes_label').css( "color", "#AAA" );
});

$('#origin-spa').click(function() {
    //Left
    $('#origin_language').val('spa');
    $('#origin-spa').addClass('select');
    $('#origin-cat').removeClass('select');
    $('[data-id="origin-select"]').removeClass('select');
    
    //Right
    $('#target_language').val('cat');
    $('#target-cat').removeAttr('disabled');
    $('#target-cat').addClass('select');
    $('#target-spa').removeClass('select');
    $('#target-spa').attr('disabled', 'disabled');
    $('[data-id="target-select"]').removeClass('select');
    $('[data-id="target-select"]').attr('disabled', 'disabled');
    
    //Enable 'formes valencianes' checkbox
    $('#formes_valencianes').removeAttr('disabled');
    $('#formes_valencianes_label').css( "color", "#333" );
});

$('#origin-select').on('change', function() {
    //Left
    var origin_language = $('#origin-select').val();
    $('#origin_language').val(origin_language);
    $('#origin-cat').removeClass('select');
    $('#origin-spa').removeClass('select');
    $('[data-id="origin-select"]').addClass('select');
    
    //Right
    $('#target_language').val('cat');
    $('#target-spa').removeClass('select');
    $('#target-spa').attr('disabled', 'disabled');
    $('#target-cat').removeAttr('disabled');
    $('#target-cat').addClass('select');
    $('[data-id="target-select"]').attr('disabled', 'disabled');
    $('[data-id="target-select"]').removeClass('select');
    
    //Disable 'formes valencianes' checkbox
    $('#formes_valencianes').attr('disabled', 'disabled');
    $('#formes_valencianes_label').css( "color", "#AAA" );
});

$('#target-spa').click(function() {
    $('#target_language').val('spa');
    $('#target-spa').addClass('select');
    $('[data-id="target-select"]').removeClass('select');
});

$('#target-select').on('change', function() {
    var target_language = $('#target-select').val();
    $('#target_language').val(target_language);
    $('#target-cat').removeClass('select');
    $('#target-spa').removeClass('select');
    $('[data-id="target-select"]').addClass('select');
});

$('.direccio').on('click', function() {
    var origin_language = $('#origin_language').val();
    var target_language = $('#target_language').val();
    $('#origin_language').val(target_language);
    $('#target_language').val(origin_language);
    
    if (origin_language == 'cat') {
        //Left
        if (target_language == 'spa') {
            $('#origin-spa').trigger('click');
            $('#target-cat').trigger('click');
            
            //Enable 'formes valencianes' checkbox
            $('#formes_valencianes').removeAttr('disabled');
            $('#formes_valencianes_label').css( "color", "#333" );
        } else {
            $('#origin-select').val(target_language);
            $('#origin-select').trigger('change');
        }
    } else if (origin_language == 'spa') {
        $('#origin-cat').trigger('click');
        $('#target-spa').trigger('click');
        $('#target-cat').attr('disabled', 'disabled');
    } else {
        $('#origin-cat').trigger('click');
        $('#target-select').val(origin_language);
        $('#target-select').trigger('change');
        $('#target-cat').attr('disabled', 'disabled');
    }
    
    
});

/** End setting different language pairs **/

$('#mark_unknown').click(function() {
    translateText();
});

/** Translation AJAX action **/
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
            url:"http://www.softcatala.org/apertium/json/translate",
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
        translation_coloured = translation.replace(/(\*\S+)/gi,"<span style='background-color: #f6f291'>$1</span>").replace('*', '');
        $('.second-textarea').html(translation_coloured);
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
    $('#translate').trigger('click');
}

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
        $("#contingut-formulari").empty().html(dt.text);
    }
}

function form_sent_ko(dt) {
    alert('merda');
}

/** End contact form action **/
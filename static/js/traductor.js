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
    
    //setup before functions
    var typingTimer;                //timer identifier
    var doneTypingInterval = 100;  //time in ms, 5 second for example
    
    //on keyup, start the countdown
    $('.primer-textarea').keyup(function(){
        clearTimeout(typingTimer);
        if ($('.primer-textarea').val) {
            typingTimer = setTimeout(doneTyping, doneTypingInterval);
        }
    });
    
    //user is "finished typing," do something
    function doneTyping () {
        if ($('.primer-textarea').val() == '') {
            $('.second-textarea').html('');
        } else {
            $('#translate').trigger('click');
        }
    }
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
        $('.second-textarea').html(nl2br(dt.responseData.translatedText));
    } else {
        trad_ko();
    }
}

function trad_ko(dt) {
    alert('res');
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
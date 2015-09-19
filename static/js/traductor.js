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
    origin_language = $('#origin-select').val();
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
    target_language = $('#target-select').val();
    $('#target_language').val(target_language);
    $('#target-cat').removeClass('select');
    $('#target-spa').removeClass('select');
    $('[data-id="target-select"]').addClass('select');
});

/** End setting different language pairs **/

$('#translate').click(function() {
    text = $('.primer-textarea').val();
    var origin_language = $('#origin_language').val();
    var target_language = $('#target_language').val();
    var valencian_forms = ($('#formes_valencianes:checked').length)?'_valencia':'';
    var adapted_target_language = target_language.replace("cat","cat"+valencian_forms);
    
    var langpair = origin_language+"|"+adapted_target_language;
    var muk = ($('#mark_unknown:checked').length)?'yes':'no';
    
    var adapted_langpair = langpair.replace("cat","cat"+valencian_forms);
        
    $.ajax({
        url:"http://www.softcatala.org/apertium/json/translate",
        type:"POST",
        data : {'langpair':adapted_langpair,'q':text,'markUnknown':muk,'key':'DjnAT2hnZKPHe98Ry/s2dmClDbs'},
        dataType: 'json',
        success : trad_ok,
        failure : trad_ko
    });
    return false;
});

function nl2br(text) {
	text=escape(text);
	return unescape(text.replace(/(%5Cr%5Cn)|(%5Cn%5Cr)|%0A|%5Cr|%5Cn/g,'<br />'));
}

function trad_ok(dt) {
    if(dt.responseStatus==200) {
        jQuery('.second-textarea').html(nl2br(dt.responseData.translatedText));
    } else {
        trad_ko();
    }
}

function trad_ko(dt) {
    alert('res');
}
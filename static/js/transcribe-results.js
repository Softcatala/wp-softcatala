var API_URL='https://api.softcatala.org/transcribe-service/v1';

(function(){

    jQuery('#mostra_opcions_esborrat').change(function(){
        if(jQuery('#mostra_opcions_esborrat').is(':checked')){
            jQuery('#esborrat_controls').show()
        } else {
            jQuery('#esborrat_controls').hide()
        }
    });

    var display_error = function(msg){
        jQuery('#info').hide();
        jQuery('#error').removeClass('hidden');
        jQuery('#errormessage').html(msg);
        jQuery('#error').show('slow');
    }

    var display_ok_message = function(message){
        jQuery('#info_text1').text(message);
        jQuery('#error').hide();
        jQuery('#info').removeClass('hidden');
        jQuery('#info').show('slow');
    }

    function deleteFile()
    {
        var xmlHttp = new XMLHttpRequest();

            xmlHttp.onreadystatechange = function()
            {
                if(xmlHttp.readyState != 4)
                {
                    return;
                }
                alert(xmlHttp.status);
                if (xmlHttp.status == 200)
                {
                    display_ok_message("El fitxer s'ha esborrat");
                }
                else
                {
                    json = JSON.parse(xmlHttp.responseText);
                    display_error(json['error']);
                }
            }

            var url = window.location.href;
            let params = getUrlVars(url);
            let uuid = params['uuid'];
            var formData = new FormData(document.getElementById('form-id'));
            url = API_URL + `/delete_uuid/`;
            formData.append('uuid', uuid);
            xmlHttp.open("post", url);
            xmlHttp.send(formData);
    }

    jQuery( "#i_esborra" ).click(function() {

        if (!validateEmail(document.querySelector('#email').value)) {
            display_error('Reviseu la vostra adreça electrònica.');
            document.querySelector('#email').focus();

        }else{
            deleteFile();
        }
    });

    var validateEmail = function (email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    checkLinks();
    setLinks();

}());


function getUrlVars(url) {
    var vars = {};
    var hashes = url.split("?")[1];

    if (hashes == null) {
        return
    }

    var hash = hashes.split('&');

    for (var i = 0; i < hash.length; i++) {
        params=hash[i].split("=");
        vars[params[0]] = params[1];
    }
    return vars;
}

function getDownloadURL(ext)
{
    var url = window.location.href;
    let params = getUrlVars(url);
    let uuid = params['uuid'];
    
    return API_URL + `/get_file/?uuid=` + uuid + "&ext=" + ext;
}

function setLinks()
{
    var urlTxt = getDownloadURL('txt');
    document.getElementById("txt_down").setAttribute("href", urlTxt);

    var urlSrt = getDownloadURL('srt');
    document.getElementById("srt_down").setAttribute("href", urlSrt);

    var urlSrt = getDownloadURL('json');
    document.getElementById("json_down").setAttribute("href", urlSrt);
}

function hide_ui()
{
    jQuery('#found').hide();
    jQuery('#notfound').removeClass('hidden');
}

function checkLinks()
{
    var url = window.location.href;
    let params = getUrlVars(url);
    if (params == null) {
        hide_ui();
        return;
    }
    let uuid = params['uuid'];

    let aUrl = API_URL + `/uuid_exists/?uuid=` + uuid;
    var anHttpRequest = new XMLHttpRequest();
    anHttpRequest.onreadystatechange = function() {
        if (anHttpRequest.readyState == 4 && anHttpRequest.status != 200) {
            hide_ui();
        }
    }

    anHttpRequest.open( "GET", aUrl, true );
    anHttpRequest.send( null );
}




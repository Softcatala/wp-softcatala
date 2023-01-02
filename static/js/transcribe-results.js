var URL='https://api.softcatala.org/transcribe-service/v1'

function getUrlVars(url) {
    var vars = {};
    var hashes = url.split("?")[1];
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
    
    return URL + `/get_file/?uuid=` + uuid + "&ext=" + ext;
}

function setLinks()
{
    var urlTxt = getDownloadURL('txt');
    document.getElementById("txt_down").setAttribute("href", urlTxt);

    var urlSrt = getDownloadURL('srt');
    document.getElementById("srt_down").setAttribute("href", urlSrt);
}

function checkLinks()
{
    var url = window.location.href;
    let params = getUrlVars(url);
    let uuid = params['uuid'];

    let aUrl = URL + `/uuid_exists/?uuid=` + uuid;
    var anHttpRequest = new XMLHttpRequest();
    anHttpRequest.onreadystatechange = function() {
        if (anHttpRequest.readyState == 4 && anHttpRequest.status != 200) {
            jQuery('#found').hide();
            jQuery('#notfound').removeClass('hidden');

        }
    }

    anHttpRequest.open( "GET", aUrl, true );
    anHttpRequest.send( null );
}


checkLinks();
setLinks();


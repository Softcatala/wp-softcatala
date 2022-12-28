var URL='https://api.softcatala.org/transcribe-service/v1'

var HttpClient = function() {
    this.get = function(aUrl, aCallback) {
        var anHttpRequest = new XMLHttpRequest();
        anHttpRequest.onreadystatechange = function() { 
            if (anHttpRequest.readyState == 4 && anHttpRequest.status == 200)
                aCallback(anHttpRequest.responseText);
        }

        anHttpRequest.open( "GET", aUrl, true );
        anHttpRequest.send( null );
    }
}


function sendFile()
{
    var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function()
        {
            if(xmlHttp.readyState != 4)
            {
                return;
            }

            if (xmlHttp.status == 200)
            {
                alert("D'aquí a una estona rebreu el fitxer transcrit per correu electrònic");
            }
            else
            {
                json = JSON.parse(xmlHttp.responseText);
                alert(json['error']);
            }
        }

        var formData = new FormData(document.getElementById('form-id'));
        url = URL + `/translate_file/`;
        xmlHttp.open("post", url);
        xmlHttp.send(formData); 
}

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

setLinks();

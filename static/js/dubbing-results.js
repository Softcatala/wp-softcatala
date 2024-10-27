var API_URL='https://api.softcatala.org/dubbing-service/v1'


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
    let urlJson = getDownloadURL('json');
    document.getElementById("metadata_down").setAttribute("href", urlJson);

    let urlLog = getDownloadURL('log');
    document.getElementById("log_down").setAttribute("href", urlLog);
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


checkLinks();
setLinks();



(function(){ 

    /* Listerner per demanar el doblatge */
    jQuery( "#i_comentaris" ).click(function() {    
        sendFile();
    });


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
                    alert("Gràcies");
                }
                else
                {
                    json = JSON.parse(xmlHttp.responseText);
                    alert(json['error']);                    
                }
            }

            var _url = window.location.href;
            let params = getUrlVars(_url);
            let uuid = params['uuid'];

            var formData = new FormData(document.getElementById('form-id'));
            formData.append('uuid', uuid);
            url = API_URL + `/feedback_form/`;
            xmlHttp.open("post", url);
            xmlHttp.send(formData);
    }
}());

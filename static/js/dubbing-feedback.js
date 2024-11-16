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


(function(){ 

    /* Listerner per demanar feedback */
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
                    alert("Moltes gràcies pels vostres comentaris!");
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

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

var display_ok_file = function(){

        jQuery('#translate_file').html("Demaneu traducció");
        jQuery('#error').hide();
        jQuery('#info').removeClass('hidden');
        jQuery('#info').show('slow');
        jQuery('#n_email').val('');
        jQuery('#n_file').val('');

}

var display_error = function(msg){

        jQuery('#translate_file').html("Demaneu traducció");
        jQuery('#info').hide();
        jQuery('#error').removeClass('hidden');
        jQuery('#errormessage').html(msg);
        jQuery('#error').show('slow');
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
                display_ok_file();
            }
            else
            {
                json = JSON.parse(xmlHttp.responseText);
                display_error(json['error']);
            }
        }

        var formData = new FormData(document.getElementById('form-id'));
        url = URL + `/transcribe_file/`;
        xmlHttp.open("post", url);
        xmlHttp.send(formData); 
}



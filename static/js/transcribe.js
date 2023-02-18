



(function(){ 

    var URL='https://api.softcatala.org/transcribe-service/v1'

    /* Ajustament boto demanar transcripció */
    jQuery("#i_demana").css("margin-top", "28px");

    /* Listerner per demanar transcripció */
    jQuery( "#i_demana" ).click(function() {

        if (!validateEmail(document.querySelector('#email').value)) {

            display_error('Reviseu la vostra adreça electrònica.');
            document.querySelector('#email').focus();

        }else if (!jQuery('#file').val()){
            display_error("No s'ha especificat fitxer")
            document.querySelector('#file').focus();

        }else{
       
            document.querySelector('#i_demana').innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";
            sendFile();
        }
    });

    document.querySelector('#error' + '> button').addEventListener('click', function (e) {
        jQuery('#error').hide('slow');
    });

    document.querySelector('#info' + '> button').addEventListener('click', function (e) {
        jQuery('#info').hide('slow');
    });

    var display_ok_file = function(waitingTime){
        document.querySelector('#i_demana').innerHTML = "Demana la transcripció";
        
        if (waitingTime == 0)
        {
            jQuery('#info_text1').text("D'aquí a una estona rebreu un correu electrònic quan el fitxer estigui llest.");
        }
        else
        {
            jQuery('#info_text1').text("D'aquí a aproximadament " + waitingTime + " rebreu un correu electrònic quan el fitxer estigui llest.");
        }

        jQuery('#info_text2').text("Gràcies per utilitzar aquest servei.");
        jQuery('#error').hide();
        jQuery('#info').removeClass('hidden');
        jQuery('#info').show('slow');
        jQuery('#n_email').val('');
        jQuery('#n_file').val('');

    }

    var display_error = function(msg){
        document.querySelector('#i_demana').innerHTML = "Demana la transcripció";
        
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
                    json = JSON.parse(xmlHttp.responseText);
                    waitingTime = json['waiting_time'];
                    display_ok_file(waitingTime);
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
        
            jQuery('#file').val('')
    }

    /* Helper functions */
    var validateEmail = function (email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

}());






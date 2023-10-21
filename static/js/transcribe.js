



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

        jQuery('#info_text2').text("Gràcies per usar aquest servei.");
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
    
    updateProgress: function updateProgress(evt)
    {
        if (evt.lengthComputable) {
            var percentVal = Math.ceil((evt.loaded / evt.total) * 100);
            jQuery('#bar').width(percentVal);
            jQuery('#percent').text(percentVal + " %");
        }
    }

    function sendFile()
    {
        var xmlHttp = new XMLHttpRequest();

            xmlHttp.upload.onprogress = updateProgress;
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

            jQuery('#bar').width(0);
            jQuery('#percent').text("0 %");
            xmlHttp.send(formData); 
    }


    // Srt options
    jQuery('#mostra_opcions').change(function(){
        if(jQuery('#mostra_opcions').is(':checked')){
            jQuery('#srt_options').show()
        } else {
            jQuery('#srt_options').hide()
        }
    });


    /* Helper functions */
    var validateEmail = function (email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

}());






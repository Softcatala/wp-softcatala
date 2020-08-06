/*!
 * Client javascript neuronal de Softcatalà 
 */

var neuronal_json_url = "https://www.softcatala.org/sc/v2/api/nmt-engcat";

var neuronalVista = (function () {


    var elementsDOM = {
        btncatsource: '#origin-cat',
        btncattarget: '#target-cat',
        btnengsource: '#origin-en',
        btnengtarget: '#target-en',
        btnswitch: '.direccio',
        btntrad: '#translate',
        firsttext: '.primer-textarea',
        secondtext: '.second-textarea',
        time: '#time',
        btncopy: '#btncopy',
        /* Mobil */
        slsourcemob: '#origin-select-mobil',
        sltargetmob: '#target-select-mobil',

        /*Enviament fitxers*/
        email: '#n_email',
        file: '#n_file',
        model_name: '#n_model_name',
        btntradfile: '#translate_file',
        info: '#info',
        error: '#error',
        errormessage: '#errormessage'
    }

    var direction;


    return {

        elementsDOM: function () {
            return elementsDOM;
        },
        initDOM: function () {

            jQuery(elementsDOM.btntrad).prop('disabled', true);
            jQuery(elementsDOM.btncopy).prop('disabled', true);
            

            document.querySelector(elementsDOM.btntrad).style.width = '130px';
            document.querySelector(elementsDOM.btntradfile).style.width = '250px';
            document.querySelector(elementsDOM.btncopy).style.marginTop = '10px';
            

            jQuery(elementsDOM.slsourcemob).selectpicker();
            jQuery(elementsDOM.sltargetmob).selectpicker();
            jQuery(elementsDOM.info).hide();
            jQuery(elementsDOM.error).hide();

            this.switchEngCat();

        },
        switch: function () {

            firsttext = jQuery(elementsDOM.firsttext).val().replace(/\n/g, "<br />");
            secondtext = jQuery(elementsDOM.secondtext).html().replace(/<br\s*\/?>/gim, "\n");

            jQuery(elementsDOM.firsttext).val(secondtext);
            jQuery(elementsDOM.secondtext).html(firsttext);

            if (direction == 'eng-cat') {

                direction = 'cat-eng';
                this.switchCatEng();

            } else {

                direction = 'eng-cat';
                this.switchEngCat();
            }

        },
        switchCatEng: function () {

            jQuery(elementsDOM.btncatsource).addClass('select');
            jQuery(elementsDOM.btnengsource).removeClass('select');
            jQuery(elementsDOM.btncattarget).removeClass('select');
            jQuery(elementsDOM.btnengtarget).addClass('select');

            jQuery(elementsDOM.slsourcemob).selectpicker('val', 'cat');
            jQuery(elementsDOM.sltargetmob).selectpicker('val', 'eng');

            direction = 'cat-eng';
        },
        switchEngCat: function () {

            jQuery(elementsDOM.btncatsource).removeClass('select');
            jQuery(elementsDOM.btnengsource).addClass('select');
            jQuery(elementsDOM.btncattarget).addClass('select');
            jQuery(elementsDOM.btnengtarget).removeClass('select');

            jQuery(elementsDOM.slsourcemob).selectpicker('val', 'eng');
            jQuery(elementsDOM.sltargetmob).selectpicker('val', 'cat');

            direction = 'eng-cat';


        },
        getDirection: function () {
            return direction;
        },
        getTrad: function (){
            return jQuery(elementsDOM.secondtext).html();
        },
        enableTrad: function () {

            if (jQuery(elementsDOM.firsttext))
                jQuery(elementsDOM.btntrad).prop('disabled', false)
            else
                jQuery(elementsDOM.btntrad).prop('disabled', true)


        },
        updateTrad: function (translation) {

            jQuery(elementsDOM.secondtext).html(translation.translated_text.replace(/\n/g, "<br />"));
            jQuery(elementsDOM.time).html(translation.time);
            jQuery(elementsDOM.btntrad).html("Tradueix");
            jQuery(elementsDOM.btncopy).prop('disabled', false);
        },
        sentFileAlert: function () {
            jQuery(elementsDOM.btntradfile).html("Demaneu traducció");
            jQuery(elementsDOM.error).hide();
            jQuery(elementsDOM.info).removeClass('hidden');
            jQuery(elementsDOM.info).show('slow');
            jQuery(elementsDOM.email).val('');
            jQuery(elementsDOM.file).val('');

        },
        displayError: function (errortxt) {
            jQuery(elementsDOM.btntradfile).html("Demaneu traducció");
            jQuery(elementsDOM.info).hide();
            jQuery(elementsDOM.error).removeClass('hidden');
            jQuery(elementsDOM.errormessage).html(errortxt);
            jQuery(elementsDOM.error).show('slow');
        }

    }

})();

var neuronalApp = (function (vistaCtrl) {

    var initEventsDoom = function () {

        var elementsDOM = vistaCtrl.elementsDOM();

        var $clipBoard = new Clipboard(elementsDOM.btncopy, {
            text: function(trigger) {
                return jQuery(elementsDOM.secondtext).html();
            }
        });

        document.querySelector(elementsDOM.slsourcemob).addEventListener('change', function (e) {

            if (document.querySelector(elementsDOM.slsourcemob).value == 'eng')
                vistaCtrl.switchEngCat()
            else
                vistaCtrl.switchCatEng()

        });

        document.querySelector(elementsDOM.sltargetmob).addEventListener('change', function (e) {

            if (document.querySelector(elementsDOM.sltargetmob).value == 'eng')
                vistaCtrl.switchCatEng();
            else
                vistaCtrl.switchEngCat();

        });

        // Event switch
        document.querySelector(elementsDOM.btnswitch).addEventListener('click', function (e) {
            vistaCtrl.switch();
        });
        document.querySelector(elementsDOM.btncatsource).addEventListener('click', function (e) {
            vistaCtrl.switchCatEng();
        });
        document.querySelector(elementsDOM.btnengsource).addEventListener('click', function (e) {
            vistaCtrl.switchEngCat();
        });
        document.querySelector(elementsDOM.btncattarget).addEventListener('click', function (e) {
            vistaCtrl.switchEngCat();
        });
        document.querySelector(elementsDOM.btnengtarget).addEventListener('click', function (e) {
            vistaCtrl.switchCatEng();
        });

        document.querySelector(elementsDOM.firsttext).addEventListener('input', function (e) {
            vistaCtrl.enableTrad();
        });
  
        document.querySelector(elementsDOM.error + '> button').addEventListener('click', function (e) {
            jQuery(elementsDOM.error).hide('slow');
        });
        
        document.querySelector(elementsDOM.info + '> button').addEventListener('click', function (e) {
            jQuery(elementsDOM.info).hide('slow');
        });

        document.querySelector(elementsDOM.btntrad).addEventListener('click', function (e) {

            document.querySelector(elementsDOM.btntrad).innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";
            var translation = {
                source_text: document.querySelector(elementsDOM.firsttext).value,
                direction: vistaCtrl.getDirection(),
                translated_text: "",
                time: ""
            }
            translate(translation);

        });
        document.querySelector(elementsDOM.btntradfile).addEventListener('click', function (e) {

            document.querySelector(elementsDOM.btntradfile).innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";

            if (!validateEmail(document.querySelector(elementsDOM.email).value)) {

                vistaCtrl.displayError('Reviseu la vostra adreça electrònica.');
                document.querySelector(elementsDOM.email).focus();

            } else if (!document.querySelector(elementsDOM.file).files[0]) {

                vistaCtrl.displayError('Cal que trieu un fitxer del vostre ordinador.');
                document.querySelector(elementsDOM.file).focus();

            } else if (document.querySelector(elementsDOM.file).files[0].size > 256000) {

                vistaCtrl.displayError('La mida màxima és de 256Kb. El vostre fitxer ocupa ' + returnFileSize(document.querySelector(elementsDOM.file).files[0].size) + '.')
                document.querySelector(elementsDOM.file).focus();

            } else {

                var translation = {
                    file: document.querySelector(elementsDOM.file).files[0],
                    email: document.querySelector(elementsDOM.email).value,
                    model_name: document.querySelector(elementsDOM.model_name).value
                }
                
                translate_file(translation);

            }

        });


    }
    /* Helper functions */
    var validateEmail = function (email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }
    var returnFileSize = function (number) {
        if (number < 1024) {
            return number + 'bytes';
        } else if (number >= 1024 && number < 1048576) {
            return (number / 1024).toFixed(1) + 'KB';
        } else if (number >= 1048576) {
            return (number / 1048576).toFixed(1) + 'MB';
        }
    }
    /* Tranlating function */
    var translate = function (translation) {

        var xhr = new XMLHttpRequest();
        url = neuronal_json_url + `/translate/`;
        xhr.open('POST', url);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function () {
            if (xhr.readyState == XMLHttpRequest.DONE) {
                json = JSON.parse(xhr.responseText);
                translation.translated_text = json["translated"];
                translation.time = 'Traducció feta en: ' + json["time"];
                vistaCtrl.updateTrad(translation);
            }
        }
        payload = JSON.stringify({
            "languages": translation.direction,
            "text": translation.source_text,
        });

        xhr.send(payload);
    }

    var translate_file = function (translation) {

        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState != 4) {
                return;
            }

            if (xmlHttp.status == 200) {
                vistaCtrl.sentFileAlert();
            }
            else {
                json = JSON.parse(xmlHttp.responseText);
                vistaCtrl.displayError(json['error']);

            }
        }


        url = neuronal_json_url + `/translate_file/`;

        var formData = new FormData();
        formData.append("email", translation.email);
        formData.append("model_name", translation.model_name);
        formData.append("file", translation.file);
        xmlHttp.open("post", url);
        xmlHttp.send(formData);


    }

    return {

        init: function () {

            vistaCtrl.initDOM();
            initEventsDoom();
            
            

            console.log('app iniciada');
        }
    }


})(neuronalVista);

neuronalApp.init();



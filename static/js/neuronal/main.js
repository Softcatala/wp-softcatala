/*!
 * Client javascript neuronal de Softcatalà 
 */

var neuronal_json_url = "https://www.softcatala.org/sc/v2/api/nmt-engcat";

var neuronalVista = ( function (){

    
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
    }

    var direction;
    
    
    return {

        elementsDOM: function(){
            return elementsDOM;
        },
        initDOM: function(){
            
            document.querySelector(elementsDOM.btntrad).disabled = true;
            //document.querySelector(elementsDOM.btncopy).disabled = true;

            document.querySelector(elementsDOM.btntrad).style.width = '130px';
            //document.querySelector(elementsDOM.btncopy).style.marginTop = '10px';
            
            jQuery(elementsDOM.slsourcemob).selectpicker();
            jQuery(elementsDOM.sltargetmob).selectpicker();

            this.switchEngCat();
                 
            
        },
        switch: function(){
            //document.querySelector(elementsDOM.btncatsource).classList.toggle('select');
            //document.querySelector(elementsDOM.btnengsource).classList.toggle('select');
            //document.querySelector(elementsDOM.btncattarget).classList.toggle('select');
            //document.querySelector(elementsDOM.btnengtarget).classList.toggle('select');

            firsttext = document.querySelector(elementsDOM.firsttext).value;
            secondtext = document.querySelector(elementsDOM.secondtext).innerHTML;

            document.querySelector(elementsDOM.firsttext).value = secondtext;
            document.querySelector(elementsDOM.secondtext).innerHTML = firsttext;
            
            if (direction == 'eng-cat') {

                direction = 'cat-eng';
                this.switchCatEng();


            }else{  
                direction = 'eng-cat';
                this.switchEngCat();   
            }
            
        },
        switchCatEng: function(){
            
            document.querySelector(elementsDOM.btncatsource).classList.add('select');
            document.querySelector(elementsDOM.btnengsource).classList.remove('select');
            document.querySelector(elementsDOM.btncattarget).classList.remove('select');
            document.querySelector(elementsDOM.btnengtarget).classList.add('select');

            //document.querySelector(elementsDOM.slsourcemob).value = 'cat';
            //document.querySelector(elementsDOM.sltargetmob).value = 'eng';
            
            jQuery(elementsDOM.slsourcemob).selectpicker('val', 'cat');
            jQuery(elementsDOM.sltargetmob).selectpicker('val', 'eng');

            direction = 'cat-eng';
            
            
        },
        switchEngCat: function(){
            
            document.querySelector(elementsDOM.btncatsource).classList.remove('select');
            document.querySelector(elementsDOM.btnengsource).classList.add('select');
            document.querySelector(elementsDOM.btncattarget).classList.add('select');
            document.querySelector(elementsDOM.btnengtarget).classList.remove('select');

            //document.querySelector(elementsDOM.slsourcemob).value = 'eng';
            //document.querySelector(elementsDOM.sltargetmob).value = 'cat';

            jQuery(elementsDOM.slsourcemob).selectpicker('val', 'eng');
            jQuery(elementsDOM.sltargetmob).selectpicker('val', 'cat');

            direction = 'eng-cat';
            
            
        },
        getDirection: function(){

            return direction;
        },
        enableTrad: function(){

            if (document.querySelector(elementsDOM.firsttext).value)
                document.querySelector(elementsDOM.btntrad).disabled = false
            else
                document.querySelector(elementsDOM.btntrad).disabled = true
            
        },
        updateTrad: function(translation){
            
            document.querySelector(elementsDOM.secondtext).innerHTML = translation.translated_text;
            document.querySelector(elementsDOM.time).innerHTML = translation.time;
            document.querySelector(elementsDOM.btntrad).innerHTML = "Tradueix";
            //document.querySelector(elementsDOM.btncopy).disabled = false;
        }
            
    }

})();

var neuronalApp = ( function (vistaCtrl){

    var initEventsDoom = function (){
      
        var elementsDOM = vistaCtrl.elementsDOM();
        
        
        document.querySelector(elementsDOM.slsourcemob).addEventListener('change', function(e){
            
            if (document.querySelector(elementsDOM.slsourcemob).value == 'eng')
                vistaCtrl.switchEngCat()
            else
                vistaCtrl.switchCatEng()
             
        });
        
        document.querySelector(elementsDOM.sltargetmob).addEventListener('change', function(e){
            
            if (document.querySelector(elementsDOM.sltargetmob).value == 'eng')
                vistaCtrl.switchCatEng();
            else
                vistaCtrl.switchEngCat();   
             
        });
        
        // Event switch
        document.querySelector(elementsDOM.btnswitch).addEventListener('click', function(e){
            vistaCtrl.switch();
        });
        document.querySelector(elementsDOM.btncatsource).addEventListener('click', function(e){
            vistaCtrl.switchCatEng();
        });
        document.querySelector(elementsDOM.btnengsource).addEventListener('click', function(e){
            vistaCtrl.switchEngCat();
        });
        document.querySelector(elementsDOM.btncattarget).addEventListener('click', function(e){
            vistaCtrl.switchEngCat();
        });
        document.querySelector(elementsDOM.btnengtarget).addEventListener('click', function(e){
            vistaCtrl.switchCatEng();
        });
        
        document.querySelector(elementsDOM.firsttext).addEventListener('input', function(e){
            vistaCtrl.enableTrad();
        });
/*
        document.querySelector(elementsDOM.btncopy).addEventListener('input', function(e){
            document.execCommand('copy');
            var copytext = document.querySelector(elementsDOM.secondtext).textContent;
            copyToClipBoard(copyText);
        });
*/
        document.querySelector(elementsDOM.btntrad).addEventListener('click', function(e){
        
           document.querySelector(elementsDOM.btntrad).innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";
            var translation = {
                source_text: document.querySelector(elementsDOM.firsttext).value,
                direction: vistaCtrl.getDirection(),
                translated_text: "",
                time: ""
            }
            translate(translation);
     
        });
        document.querySelector(elementsDOM.btntradfile).addEventListener('click', function(e){
            
             var translation = {
                 file: document.querySelector(elementsDOM.file).files[0],
                 email: document.querySelector(elementsDOM.email).value,
                 model_name: document.querySelector(elementsDOM.model_name).value
             }
             translate_file(translation);
      
         });
    
    }

    var translate = function(translation){
 
        var xhr = new XMLHttpRequest();
        url = neuronal_json_url + `/translate/`;
        xhr.open('POST', url);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function() {
            if (xhr.readyState == XMLHttpRequest.DONE) {   
                json = JSON.parse(xhr.responseText);
                translation.translated_text = json["translated"];
                translation.time = 'Traducció realitzada en un temps de: ' + json["time"];
                vistaCtrl.updateTrad(translation);
            }
        }
        payload = JSON.stringify({
            "languages": translation.direction,
            "text": translation.source_text,
        });

        xhr.send(payload);
    }

    var translate_file = function (translation)
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
                    alert("En una estona rebeu el fitxer traduÃ¯t per correu electrÃ²nic");
                }
                else
                {
                    json = JSON.parse(xmlHttp.responseText);
                    alert(json['error']);
                }
            }
    
            /*
            var formData = new FormData(document.getElementById('form-id'));
            */
            url = neuronal_json_url + `/translate_file/`;
            
            var formData = new FormData();
            formData.append("email", translation.email);
            formData.append("model_name", translation.model_name);
            formData.append("file", translation.file);
            xmlHttp.open("post", url);
            xmlHttp.send(formData); 
    }
   
    return {

        init: function(){
            
            vistaCtrl.initDOM();
            initEventsDoom();
            console.log('app iniciada');
        }
    }


})(neuronalVista);

neuronalApp.init();

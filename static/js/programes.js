/** JS functions related to pages from the post_type 'Programa' **/

jQuery( document ).ready(function() {
    var OSName="Unknown OS";
    if (navigator.appVersion.indexOf("Win")!=-1) OSName="windows";
    else if (navigator.userAgent.indexOf("Mac")!=-1) OSName="osx";
    else if (navigator.userAgent.indexOf("Linux")!=-1) OSName="linux";

    if(jQuery('#baixada_'+OSName).length) {
        jQuery('#baixada_'+OSName).show();
    } else {
        jQuery('.baixada_boto').first().show();
    }

});
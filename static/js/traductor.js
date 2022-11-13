/*!
 * clipboard.js v1.5.10
 * https://zenorocha.github.io/clipboard.js
 *
 * Licensed MIT © Zeno Rocha
 */
!function(t){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=t();else if("function"==typeof define&&define.amd)define([],t);else{var e;e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,e.Clipboard=t()}}(function(){var t,e,n;return function t(e,n,o){function i(c,a){if(!n[c]){if(!e[c]){var s="function"==typeof require&&require;if(!a&&s)return s(c,!0);if(r)return r(c,!0);var l=new Error("Cannot find module '"+c+"'");throw l.code="MODULE_NOT_FOUND",l}var u=n[c]={exports:{}};e[c][0].call(u.exports,function(t){var n=e[c][1][t];return i(n?n:t)},u,u.exports,t,e,n,o)}return n[c].exports}for(var r="function"==typeof require&&require,c=0;c<o.length;c++)i(o[c]);return i}({1:[function(t,e,n){var o=t("matches-selector");e.exports=function(t,e,n){for(var i=n?t:t.parentNode;i&&i!==document;){if(o(i,e))return i;i=i.parentNode}}},{"matches-selector":5}],2:[function(t,e,n){function o(t,e,n,o,r){var c=i.apply(this,arguments);return t.addEventListener(n,c,r),{destroy:function(){t.removeEventListener(n,c,r)}}}function i(t,e,n,o){return function(n){n.delegateTarget=r(n.target,e,!0),n.delegateTarget&&o.call(t,n)}}var r=t("closest");e.exports=o},{closest:1}],3:[function(t,e,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.fn=function(t){var e=Object.prototype.toString.call(t);return"[object Function]"===e}},{}],4:[function(t,e,n){function o(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!a.string(e))throw new TypeError("Second argument must be a String");if(!a.fn(n))throw new TypeError("Third argument must be a Function");if(a.node(t))return i(t,e,n);if(a.nodeList(t))return r(t,e,n);if(a.string(t))return c(t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function i(t,e,n){return t.addEventListener(e,n),{destroy:function(){t.removeEventListener(e,n)}}}function r(t,e,n){return Array.prototype.forEach.call(t,function(t){t.addEventListener(e,n)}),{destroy:function(){Array.prototype.forEach.call(t,function(t){t.removeEventListener(e,n)})}}}function c(t,e,n){return s(document.body,t,e,n)}var a=t("./is"),s=t("delegate");e.exports=o},{"./is":3,delegate:2}],5:[function(t,e,n){function o(t,e){if(r)return r.call(t,e);for(var n=t.parentNode.querySelectorAll(e),o=0;o<n.length;++o)if(n[o]==t)return!0;return!1}var i=Element.prototype,r=i.matchesSelector||i.webkitMatchesSelector||i.mozMatchesSelector||i.msMatchesSelector||i.oMatchesSelector;e.exports=o},{}],6:[function(t,e,n){function o(t){var e;if("INPUT"===t.nodeName||"TEXTAREA"===t.nodeName)t.focus(),t.setSelectionRange(0,t.value.length),e=t.value;else{t.hasAttribute("contenteditable")&&t.focus();var n=window.getSelection(),o=document.createRange();o.selectNodeContents(t),n.removeAllRanges(),n.addRange(o),e=n.toString()}return e}e.exports=o},{}],7:[function(t,e,n){function o(){}o.prototype={on:function(t,e,n){var o=this.e||(this.e={});return(o[t]||(o[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){function o(){i.off(t,o),e.apply(n,arguments)}var i=this;return o._=e,this.on(t,o,n)},emit:function(t){var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),o=0,i=n.length;for(o;i>o;o++)n[o].fn.apply(n[o].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),o=n[t],i=[];if(o&&e)for(var r=0,c=o.length;c>r;r++)o[r].fn!==e&&o[r].fn._!==e&&i.push(o[r]);return i.length?n[t]=i:delete n[t],this}},e.exports=o},{}],8:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","select"],r);else if("undefined"!=typeof o)r(n,e("select"));else{var c={exports:{}};r(c,i.select),i.clipboardAction=c.exports}}(this,function(t,e){"use strict";function n(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var i=n(e),r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol?"symbol":typeof t},c=function(){function t(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}return function(e,n,o){return n&&t(e.prototype,n),o&&t(e,o),e}}(),a=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=e.action,this.emitter=e.emitter,this.target=e.target,this.text=e.text,this.trigger=e.trigger,this.selectedText=""},t.prototype.initSelection=function t(){this.text?this.selectFake():this.target&&this.selectTarget()},t.prototype.selectFake=function t(){var e=this,n="rtl"==document.documentElement.getAttribute("dir");this.removeFake(),this.fakeHandler=document.body.addEventListener("click",function(){return e.removeFake()}),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.fontSize="12pt",this.fakeElem.style.border="0",this.fakeElem.style.padding="0",this.fakeElem.style.margin="0",this.fakeElem.style.position="fixed",this.fakeElem.style[n?"right":"left"]="-9999px",this.fakeElem.style.top=(window.pageYOffset||document.documentElement.scrollTop)+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,document.body.appendChild(this.fakeElem),this.selectedText=(0,i.default)(this.fakeElem),this.copyText()},t.prototype.removeFake=function t(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function t(){this.selectedText=(0,i.default)(this.target),this.copyText()},t.prototype.copyText=function t(){var e=void 0;try{e=document.execCommand(this.action)}catch(n){e=!1}this.handleResult(e)},t.prototype.handleResult=function t(e){e?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function t(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function t(){this.removeFake()},c(t,[{key:"action",set:function t(){var e=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=e,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function t(){return this._action}},{key:"target",set:function t(e){if(void 0!==e){if(!e||"object"!==("undefined"==typeof e?"undefined":r(e))||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===this.action&&e.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===this.action&&(e.hasAttribute("readonly")||e.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');this._target=e}},get:function t(){return this._target}}]),t}();t.exports=a})},{select:6}],9:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","./clipboard-action","tiny-emitter","good-listener"],r);else if("undefined"!=typeof o)r(n,e("./clipboard-action"),e("tiny-emitter"),e("good-listener"));else{var c={exports:{}};r(c,i.clipboardAction,i.tinyEmitter,i.goodListener),i.clipboard=c.exports}}(this,function(t,e,n,o){"use strict";function i(t){return t&&t.__esModule?t:{"default":t}}function r(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function c(t,e){if(!t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!e||"object"!=typeof e&&"function"!=typeof e?t:e}function a(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}function s(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}var l=i(e),u=i(n),f=i(o),d=function(t){function e(n,o){r(this,e);var i=c(this,t.call(this));return i.resolveOptions(o),i.listenClick(n),i}return a(e,t),e.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText},e.prototype.listenClick=function t(e){var n=this;this.listener=(0,f.default)(e,"click",function(t){return n.onClick(t)})},e.prototype.onClick=function t(e){var n=e.delegateTarget||e.currentTarget;this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new l.default({action:this.action(n),target:this.target(n),text:this.text(n),trigger:n,emitter:this})},e.prototype.defaultAction=function t(e){return s("action",e)},e.prototype.defaultTarget=function t(e){var n=s("target",e);return n?document.querySelector(n):void 0},e.prototype.defaultText=function t(e){return s("text",e)},e.prototype.destroy=function t(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(u.default);t.exports=d})},{"./clipboard-action":8,"good-listener":4,"tiny-emitter":7}]},{},[9])(9)});

/*!
 * Traductor de Softcatalà 
 */

var traductor_json_url = "https://www.softcatala.org/api/traductor/translate";
var neuronal_json_url = "https://api.softcatala.org/v2/nmt";

var SC_TRADUCTOR_COOKIE = 'sc-traductor';

var rawText = '';

jQuery('#translate').data('scroll', false);

function enableDev() {
    traductor_json_url = 'https://www.softcatala.org/api/dev/traductor/translate'
}

(function($) {
//Set the initial default pairs on document ready
jQuery(document).ready(function(){
    jQuery('#origin_language').val('spa');
    jQuery('#target_language').val('cat');

    //Left
    jQuery('#origin-spa').addClass('select');
    jQuery('#origin-cat').removeClass('select');
    jQuery('[data-id="origin-select"]').removeClass('select');

    //Right
    jQuery('#target-spa').attr('disabled', 'disabled');
    jQuery('#target-spa').removeClass('select');
    jQuery('[data-id="target-select"]').removeClass('select');
    jQuery('[data-id="target-select"]').attr('disabled', 'disabled');
    jQuery('#target-cat').removeAttr('disabled');
    jQuery('#target-cat').addClass('select');

    //Mobile (left-right)
    jQuery('#origin-select-mobil').val('spa');
    jQuery('#target-select-mobil').val('cat');
    jQuery('#origin-select-mobil').selectpicker('render');
    jQuery('#target-select-mobil').selectpicker('render');
    jQuery('.selectpicker').selectpicker('refresh');

    /** Workaround to solve the issue when the selected language is the same marked in the dropdown **/
    jQuery('div#div_select_origin  ul.dropdown-menu.inner li').on('click', function() {
        jQuery('#origin-select').trigger('change');
    });

    jQuery('div#div_select_target  ul.dropdown-menu.inner li').on('click', function() {
        jQuery('#target-select').trigger('change');
    });
    /** End workaround **/

    /* Handling options */
    (function() {
        setCheckboxValue('auto-trad', '#auto-trad');
        setCheckboxValue('unknown', '#mark_unknown');
        setCheckboxValue('valencia', '#formes_valencianes');

        jQuery('#auto-trad').change(function() {
            setCookieValue('#auto-trad', 'auto-trad');
        });

        jQuery('#mark_unknown').change(function() {
            setCookieValue('#mark_unknown', 'unknown');
        });

        jQuery('#formes_valencianes').change(function() {
            setCookieValue('#formes_valencianes', 'valencia');
        });

        var sourceLanguage = jQuery.getMetaCookie('source-lang', SC_TRADUCTOR_COOKIE);
        var targetLanguage = jQuery.getMetaCookie('target-lang', SC_TRADUCTOR_COOKIE);

        if ( typeof sourceLanguage === 'string' && sourceLanguage !== '' ) {
            set_origin_language(sourceLanguage);
            set_origin_button(sourceLanguage);
            set_origin_button_mobile(sourceLanguage);

            set_target_language(targetLanguage);
            set_target_button(targetLanguage);
            set_target_button_mobile(targetLanguage);
        }
    })();

    //Timer for instant translation
    var timer,
        lastPunct = false, punct = [46, 33, 58, 63, 47, 45, 190, 171, 49],
        timeoutPunct = 1000, timeoutOther = 3000;
    jQuery('.primer-textarea').on('keyup paste', function (event) {
        if(lastPunct && event.keyCode === 32 || event.keyCode === 13) {
            // Don't override the short timeout for simple space-after-punctuation
            return;
        }

        if(timer) {
            clearTimeout(timer);
        }

        var timeout;
        if(punct.indexOf(event.keyCode) !== -1) {
            timeout = timeoutPunct;
            lastPunct = true;
        }
        else {
            timeout = timeoutOther;
            lastPunct = false;
        }

        timer = setTimeout(function () {
            if((jQuery('#auto-trad').is(':checked')) && (!neuronalApp.isActive())){
                jQuery('#auto-trad').data('translating', true);
                translateText();
            }
        }, timeout);
    });

    jQuery('.primer-textarea').focus();

    jQuery('.primer-textarea').keydown(function (e) {
        if (e.ctrlKey && e.keyCode == 13) {
          translateText();
        }
    });
});

function setCookieValue(htmlId, cookieName) {
        $newValue = jQuery(htmlId).is(':checked');
        jQuery.setMetaCookie(cookieName, SC_TRADUCTOR_COOKIE, $newValue.toString());
}

function setCheckboxValue(cookieName, htmlId) {
    var cookieValue = jQuery.getMetaCookie(cookieName, SC_TRADUCTOR_COOKIE);

    if( typeof cookieValue === 'string' ) {
        jQuery(htmlId).prop('checked', getBoolean(cookieValue) );
    }
}

function getBoolean(value) {
    return value == 'true';
}

/** Set the different language pairs and update menus depending on user clicks **/
//Desktop selectors
jQuery('#origin-cat').click(function() {
    var prev_origin_language = jQuery('#origin_language').val();
    var final_target_language = 'spa';

    set_origin_language('cat');

    set_origin_button('cat');
    set_origin_button_mobile('cat');

    if(prev_origin_language != 'cat') {
        final_target_language = prev_origin_language;
    }
    set_target_language(final_target_language);
    set_target_button(final_target_language);
    set_target_button_mobile(final_target_language);

    toggle_formes_valencianes('off');
});

jQuery('#origin-spa').click(function() {
    set_origin_language('spa');
    set_target_language('cat');

    set_origin_button('spa');
    set_origin_button_mobile('spa');

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('on');
});

jQuery('#origin-select').on('change', function() {
    var origin_language = jQuery('#origin-select').val();
    set_origin_language(origin_language);
    set_target_language('cat');

    set_origin_button(origin_language);
    set_origin_button_mobile(origin_language);

    set_target_button('cat');
    set_target_button_mobile('cat');

    toggle_formes_valencianes('off');
});

jQuery('#target-spa').click(function() {
    set_target_language('spa');
    set_target_button('spa');
    set_target_button_mobile('spa');
});

jQuery('#target-select').on('change', function() {
    var target_language = jQuery('#target-select').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Mobile selectors
jQuery('#origin-select-mobil').on('change', function() {
    var prev_origin_language = jQuery('#origin_language').val();
    var final_target_language = 'cat';
    var origin_language = jQuery('#origin-select-mobil').val();
    set_origin_language(origin_language);

    set_origin_button(origin_language);
    set_origin_button_mobile(origin_language);

    if(origin_language == 'cat') {
        final_target_language = prev_origin_language;
    }
    set_target_language(final_target_language);
    set_target_button(final_target_language);
    set_target_button_mobile(final_target_language);

    if(origin_language == 'spa') {
        toggle_formes_valencianes('on');
    } else {
        toggle_formes_valencianes('off');
    }
});

jQuery('#target-select-mobil').on('change', function() {
    var target_language = jQuery('#target-select-mobil').val();
    set_target_language(target_language);

    set_target_button(target_language);
    set_target_button_mobile(target_language);
});

//Direction change
jQuery('.direccio').on('click', function() {
    var new_target_language = jQuery('#origin_language').val();
    var new_origin_language = jQuery('#target_language').val();

    if(new_origin_language == 'spa') {
        toggle_formes_valencianes('on');
    } else {
        toggle_formes_valencianes('off');
    }

    set_target_language(new_target_language);
    set_origin_language(new_origin_language);

    set_origin_button(new_origin_language);
    set_origin_button_mobile(new_origin_language);

    set_target_button(new_target_language);
    set_target_button_mobile(new_target_language);

    exchange_texts();
});
/** End setting different language pairs **/


/** Translation AJAX action and other related actions **/
jQuery('#translate').click(function() {
    jQuery('#translate').data('scroll', true);
    translateText();
    logSourceText();
});

function logSourceText() {

    if (!sc_settings.log_traductor_source) {
        return;
    }

    if ( jQuery('.primer-textarea').val() == '' ) {
        return;
    }

    if (jQuery('#log_traductor_source:checked').length) {

        var source_lang = jQuery.getMetaCookie('source-lang', SC_TRADUCTOR_COOKIE);
        var target_lang = jQuery.getMetaCookie('target-lang', SC_TRADUCTOR_COOKIE);

        var data = {
            'source_lang' : source_lang,
            'source_txt'  : jQuery('.primer-textarea').val(),
            'target_lang' : target_lang,
            'target_txt'  : '',
        };

        jQuery.ajax({
            url: 'https://www.softcatala.org/api/traductor/feedback/log',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
        });
    }
}

function translateText() {
    if( jQuery('.primer-textarea').val() == '' ) {
        jQuery(".second-textarea").html('');
        return;
    }

    var text = jQuery('.primer-textarea').val();

    if (String.prototype.hasOwnProperty('normalize')) {
         text = text.normalize("NFC");
    }

    if (text.length) {
        var origin_language = jQuery('#origin_language').val();
        var target_language = jQuery('#target_language').val();
        var valencian_forms = (jQuery('#formes_valencianes:checked').length)?'_valencia':'';
        var adapted_target_language = target_language;
        if (origin_language == 'spa') {
            adapted_target_language = target_language.replace("cat","cat"+valencian_forms);
        }

        var langpair = origin_language+"|"+adapted_target_language;
        var muk = (jQuery('#mark_unknown:checked').length)?'yes':'no';

        jQuery.setMetaCookie('source-lang', SC_TRADUCTOR_COOKIE, origin_language);
        jQuery.setMetaCookie('target-lang', SC_TRADUCTOR_COOKIE, target_language);

        document.querySelector('#translate').innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";
  
        if (neuronalApp.isActive()){
              
           var savetext = false;

           if (jQuery('#log_traductor_source:checked').length)
                savetext = true;
            
           $.ajax({
                url:neuronal_json_url + `/translate/`,
                type:"POST",
                data : {'langpair':langpair,'q':text,'savetext':savetext},
                dataType: 'json',
                success : trad_ok,
                failure : trad_ko
            });

        }else{

            $.ajax({
                url:traductor_json_url,
                type:"POST",
                data : {'langpair':langpair,'q':text,'markUnknown':muk,'key':'NmQ3NmMyNThmM2JjNWQxMjkxN2N'},
                dataType: 'json',
                success : trad_ok,
                failure : trad_ko
            });
        }

        return false;
    } else {
        alert('Introduïu algun text');
    }
}

function nl2br(text) {
    text=escape(text);
    return unescape(text.replace(/(%5Cr%5Cn)|(%5Cn%5Cr)|%0A|%5Cr|%5Cn/g,'<br />'));
}

jQuery('#traductor-neteja').click(function() {
    jQuery(".primer-textarea").val('');
    jQuery(".second-textarea").html('');
});

function trad_ok(dt) {

    document.querySelector('#translate').innerHTML = "Tradueix";

    if(dt.responseStatus==200) {
        
        rawText = dt.responseData.translatedText;

        /* Message from neuronal engine */ 
        infoText = dt.message;

        if (infoText){
            jQuery('#message_info').removeClass('hidden');
            jQuery('#message_info').show('slow');
            jQuery('#message').html(infoText);            
        }
        
        encodedText = jQuery('<div/>').text(rawText).html();

        translation = nl2br(encodedText);

        translation_coloured = translation.replace(/\*([^.,;:\t<>& ]+)/gi,"<span style='background-color: #f6f291'>$1</span>");
        jQuery('.second-textarea').html(translation_coloured);

        $scroll = jQuery('#translate').data('scroll');

        jQuery('#translate').data('scroll', false);

        if($scroll && jQuery(".second-textarea").height() < '270') {
            jQuery('html, body').animate({
                scrollTop: jQuery(".second-textarea").offset().top
            }, 2000);
        }

    } else {
        trad_ko();
    }
}

function trad_ko(dt) {
    //Aquesta funció d'error s'ha de moure per a que siga global a tot el web, per cada vegada que es vulga fer un avís
    var error_title = 'Sembla que alguna cosa no ha funcionat com calia';
    var error_txt = 'S\'ha produït un error en executar la traducció. Proveu de nou ara o més tard. Si el problema persisteix, contacteu amb nosaltres mitjançant el formulari d\'ajuda.';
    jQuery('#error_title').html(error_title);
    jQuery('#error_description').html(error_txt);
    jQuery('#error_pagina').trigger('click');
}

/* This function just calls the translation */

jQuery('#mark_unknown').click(function() {
    translateText();
});
/** End translation AJAX action **/


/** Start functions to set the different language pairs and update menus depending on user clicks **/
function toggle_formes_valencianes(status) {
    if ( status == 'on' ) {
        //Enable 'formes valencianes' checkbox
        jQuery('#formes_valencianes').removeAttr('disabled');
        jQuery('#formes_valencianes_label').css( "color", "#333" );
    } else {
        //Disable 'formes valencianes' checkbox
        jQuery('#formes_valencianes').attr('disabled', 'disabled');
        jQuery('#formes_valencianes_label').css( "color", "#AAA" );
    }
}

function set_origin_language( language ) {
    jQuery('#origin_language').val(language);
    neuronalApp.show_neuronal();
    
}

function set_target_language ( language ) {
    jQuery('#target_language').val(language);
    neuronalApp.show_neuronal();
}

function set_origin_button ( language ) {
    if ( language == 'spa' ) {
        jQuery('#origin-spa').addClass('select');
        jQuery('#origin-cat').removeClass('select');
        jQuery('[data-id="origin-select"]').removeClass('select');
    } else if ( language == 'cat' ) {
        jQuery('#origin-cat').addClass('select');
        jQuery('#origin-spa').removeClass('select');
        jQuery('[data-id="origin-select"]').removeClass('select');
    } else {
        jQuery('[data-id="origin-select"]').addClass('select');
        jQuery('#origin-spa').removeClass('select');
        jQuery('#origin-cat').removeClass('select');

        jQuery('#origin-select').val( language );
        jQuery('#origin-select').selectpicker('render');
    }
}

function set_origin_button_mobile ( language ) {
    jQuery('#origin-select-mobil').val(language);
    jQuery('#origin-select-mobil').selectpicker('render');

    if( language == 'cat' ) {
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', '');
        jQuery("#target-select-mobil option[value='cat']").css('display', 'none');
        jQuery('.selectpicker-mobil').selectpicker('refresh');
    } else {
        jQuery("#target-select-mobil option[value='cat']").css('display', '');
        jQuery('.selectpicker-mobil').selectpicker('refresh');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
    }
}

function set_target_button ( language ) {
    if( language == 'cat' ) {
        jQuery('#target-cat').removeAttr('disabled', 'disabled');
        jQuery('#target-cat').addClass('select');
        jQuery('#target-spa').removeClass('select');
        jQuery('#target-spa').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').removeClass('select');
        jQuery('[data-id="target-select"]').attr('disabled', 'disabled');

        jQuery('#div_select_target > div').removeClass('select');

    } else if ( language == 'spa' ) {
        jQuery('#target-spa').removeAttr('disabled', 'disabled');
        jQuery('#target-spa').addClass('select');
        jQuery('#target-cat').removeClass('select');
        jQuery('#target-cat').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').removeClass('select');
        jQuery('[data-id="target-select"]').removeAttr('disabled', 'disabled');
        
        jQuery('#div_select_target > div').removeClass('select');
        

    } else {
        jQuery('#target-select').val( language );
        jQuery('#target-spa').removeAttr('disabled');
        jQuery('#target-spa').removeClass('select');
        jQuery('#target-cat').removeClass('select');
        jQuery('#target-cat').attr('disabled', 'disabled');
        jQuery('[data-id="target-select"]').addClass('select');
        jQuery('[data-id="target-select"]').removeAttr('disabled', 'disabled');
        jQuery('#target-select').selectpicker('render');
    }
}

function set_target_button_mobile ( language ) {
    jQuery('#target-select-mobil').val( language );

    //Don't display other options than 'cat' in case Catalan is the target language
    if( language == 'cat' ) {
        jQuery("#target-select-mobil option[value='cat']").css('display', '');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', 'none');
        jQuery('#div_select_target_mobile .filter-option').next("span").removeClass('caret');
    } else {
        jQuery("#target-select-mobil option[value='cat']").css('display', 'none');
        jQuery('div.btns-llengues-desti .dropdown-menu').css('display', '');
        jQuery('#div_select_target_mobile .filter-option').next("span").addClass('caret');
    }
    jQuery('#target-select-mobil').selectpicker('render');
}

function exchange_texts() {
    var translation_text = jQuery('.second-textarea').html();
    var original_text = jQuery('.primer-textarea').val();
    jQuery('.second-textarea').html(original_text);
    jQuery('.primer-textarea').val(translation_text.replace(/<(?:.|\n)*?>/gm, ''));
}

/** End functions related to language pairs change **/


/* Start with neuronal app functions */

var neuronalApp = (function () {

    var initEventsDoom = function () {

        jQuery('#message_info').hide();

        jQuery('input[type=radio][name=rneuronal]').change(function() {    
            if (jQuery("#rneuronal").is(':checked')) 
                show_neuronal_menu();      
            else    
                hide_neuronal_menu();          
        });

        document.querySelector('#message_info' + '> button').addEventListener('click', function (e) {
            jQuery('#message_info').hide('slow');
        });

        document.querySelector('#error' + '> button').addEventListener('click', function (e) {
            jQuery('#error').hide('slow');
        });

        document.querySelector('#info' + '> button').addEventListener('click', function (e) {
            jQuery('#info').hide('slow');
        });
        document.querySelector('#translate_file').addEventListener('click', function (e) {

           if (!validateEmail(document.querySelector('#n_email').value)) {

                display_error('Reviseu la vostra adreça electrònica.');
                document.querySelector('#n_email').focus();

            } else if (!document.querySelector('#n_file').files[0]) {

                display_error('Cal que trieu un fitxer del vostre ordinador.');
                document.querySelector('#n_file').focus();

            } else if (document.querySelector('#n_file').files[0].size > 8192*1024) {

                display_error('La mida màxima és de 8Mb. El vostre fitxer ocupa ' + returnFileSize(document.querySelector('#n_file').files[0].size) + '.')
                document.querySelector('#n_file').focus();

            } else {
      
                document.querySelector('#translate_file').innerHTML = "<i class=\"fa fa-spinner fa-pulse fa-fw\"></i>";
                
                var translation = {
                    file: document.querySelector('#n_file').files[0],
                    email: document.querySelector('#n_email').value,
                    model_name: document.querySelector('#n_model_name').value
                }
                
                translate_file(translation);
                
            }
        
        });


    }
 
    /* Check is neuronal is currently active */
    var isActive = function (){

        var origin_language = jQuery('#origin_language').val();
        var target_language = jQuery('#target_language').val();
        var rneuronalchecked = jQuery("#rneuronal").is(':checked');
        var neuronal_langs = ["en", "deu", "ita", "nld", "fr", "pt", "jpn", "glg", "oci"]

        if ((jQuery.inArray(origin_language, neuronal_langs) !== -1 || jQuery.inArray(target_language, neuronal_langs) !== -1)
            & rneuronalchecked)
            return true;
        else
            return false;
    
    }
    /* Decide to show or not neuronal app */
    var show_neuronal = function (){
        
        var origin_lang  = jQuery('#origin_language').val();
        var target_lang = jQuery('#target_language').val();
        var rneuronalchecked = jQuery("#rneuronal").is(':checked');
        var langs_with_both_translators = ["en", "fr", "pt", "oc"];
        var langs_only_neuronal = ["deu", "ita", "nld", "jpn", "glg"]
        
        if (jQuery.inArray(origin_lang, langs_with_both_translators) !== -1 ||
            jQuery.inArray(target_lang, langs_with_both_translators) !== -1) {

            jQuery('#rneuronal').prop("checked", true);

            // Show radiobuttons for neuronal vs apertium
            jQuery('#panel-radioneuronal').removeClass('hidden');
            jQuery('#panel-radioneuronal').show();
    
            if (rneuronalchecked){       
                show_neuronal_menu();
            }else{
                hide_neuronal_menu();
            }
        
        } else if (jQuery.inArray(origin_lang, langs_only_neuronal) !== -1 ||
                   jQuery.inArray(target_lang, langs_only_neuronal) !== -1) {
            
            jQuery('#rneuronal').prop("checked", true);
            jQuery('#panel-radioneuronal').addClass('hidden');
            
            
        }else{
            // Hide radiobuttons
            jQuery('#panel-radioneuronal').hide();
            jQuery('#info-neuronal').hide();
    
            // Hide neuronal widgets
            jQuery('.neuronal').hide();
            toggle_mark_unknown('on');
            toggle_formes_valencianes('on');
            toggle_autotrad('on');
        }
        
    
    }
    
    /* Show neuronal options */
    var show_neuronal_menu = function (){

        jQuery('.neuronal').removeClass('hidden');
        jQuery('.neuronal').show();
        toggle_mark_unknown('off');
        toggle_formes_valencianes('off');
        toggle_autotrad('off');
    
    }
    /* Hide neuronal options */
    var hide_neuronal_menu = function (){

        toggle_mark_unknown('on');
        toggle_autotrad('on');
        jQuery('.neuronal').hide();
        jQuery('#message_info').hide();

    
    }

    var process_result = function(translation){
        /* Global function to show translation */
        update_result(translation.translated);

        if (translation.message){
            jQuery('#message_info').removeClass('hidden');
            jQuery('#message_info').show('slow');
            jQuery('#message').html(translation.message);
        }
    }

    var display_error = function(msg){

            jQuery('#translate_file').html("Demaneu traducció");
            jQuery('#info').hide();
            jQuery('#error').removeClass('hidden');
            jQuery('#errormessage').html(msg);
            jQuery('#error').show('slow');
    }

    var display_ok_file = function(){

            jQuery('#translate_file').html("Demaneu traducció");
            jQuery('#error').hide();
            jQuery('#info').removeClass('hidden');
            jQuery('#info').show('slow');
            jQuery('#n_email').val('');
            jQuery('#n_file').val('');

    }
    var translate_file = function(translation){

            var xmlHttp = new XMLHttpRequest();
            xmlHttp.onreadystatechange = function () {
                if (xmlHttp.readyState != 4) {
                    return;
                }
                if (xmlHttp.status == 200) {
                    display_ok_file();
                }
                else {
                    json = JSON.parse(xmlHttp.responseText);
                    displayError(json['error']);
                }
            }
    
    
            url = neuronal_json_url + `/translate_file/`;
    
            var formData = new FormData();
            formData.append("email", translation.email);
            formData.append("model_name", translation.model_name);
            formData.append("file", translation.file);
            formData.append("savetext", translation.savetext);
            xmlHttp.open("post", url);
            xmlHttp.send(formData);
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

    
return {

    init: function () {
        initEventsDoom();
    },
    
    isActive: function(){
        return isActive();
    },
    show_neuronal: function (){
        show_neuronal();
    },
    show_neuronal_menu: function(){
        show_neuronal_menu();
    },
    hide_neuronal_menu: function(){
        hide_neuronal_menu();
    },
    process_result: function(translation){
        process_result(translation)
    }

}

})();

neuronalApp.init();

// Toggle functions for panel

function toggle_mark_unknown(status) {
    if ( status == 'on' ) {
        //Enable 'formes valencianes' checkbox
        jQuery('#mark_unknown').removeAttr('disabled');
        jQuery('#mark_unknown_label').css( "color", "#333" );
    } else {
        //Disable 'formes valencianes' checkbox
        jQuery('#mark_unknown').attr('disabled', 'disabled');
        jQuery('#mark_unknown_label').css( "color", "#AAA" );
    }
}

function toggle_autotrad(status) {
    if ( status == 'on' ) {
        //Enable 'formes valencianes' checkbox
        jQuery('#auto-trad').removeAttr('disabled');
        jQuery('#tradueix_online_label').css( "color", "#333" );
    } else {
        //Disable 'formes valencianes' checkbox
        jQuery('#auto-trad').attr('disabled', 'disabled');
        jQuery('#tradueix_online_label').css( "color", "#AAA" );
    }
}


/* End with neuronal app */


var $clipBoard = new Clipboard('#copy-text', {
    text: function(trigger) {
        return rawText;
    }
});

$clipBoard.on('success', function(e) {
    e.clearSelection();
    showTooltip("El text s'ha copiat!");
});

$clipBoard.on('error', function(e) {
    showTooltip("No s'ha pogut copiar el text :(");
});

function showTooltip(msg) {
    jQuery('#copy-text').data('title', msg);
    jQuery('#copy-text').tooltip('show');
    jQuery('#copy-text').on({
        'mouseleave': function() {
            $(this).tooltip('hide');
        }
    });
}

})(jQuery);

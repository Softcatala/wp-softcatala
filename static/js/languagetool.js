/*!
 * clipboard.js v1.5.10
 * https://zenorocha.github.io/clipboard.js
 *
 * Licensed MIT © Zeno Rocha
 */
!function(t){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=t();else if("function"==typeof define&&define.amd)define([],t);else{var e;e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,e.Clipboard=t()}}(function(){var t,e,n;return function t(e,n,o){function i(c,a){if(!n[c]){if(!e[c]){var s="function"==typeof require&&require;if(!a&&s)return s(c,!0);if(r)return r(c,!0);var l=new Error("Cannot find module '"+c+"'");throw l.code="MODULE_NOT_FOUND",l}var u=n[c]={exports:{}};e[c][0].call(u.exports,function(t){var n=e[c][1][t];return i(n?n:t)},u,u.exports,t,e,n,o)}return n[c].exports}for(var r="function"==typeof require&&require,c=0;c<o.length;c++)i(o[c]);return i}({1:[function(t,e,n){var o=t("matches-selector");e.exports=function(t,e,n){for(var i=n?t:t.parentNode;i&&i!==document;){if(o(i,e))return i;i=i.parentNode}}},{"matches-selector":5}],2:[function(t,e,n){function o(t,e,n,o,r){var c=i.apply(this,arguments);return t.addEventListener(n,c,r),{destroy:function(){t.removeEventListener(n,c,r)}}}function i(t,e,n,o){return function(n){n.delegateTarget=r(n.target,e,!0),n.delegateTarget&&o.call(t,n)}}var r=t("closest");e.exports=o},{closest:1}],3:[function(t,e,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.fn=function(t){var e=Object.prototype.toString.call(t);return"[object Function]"===e}},{}],4:[function(t,e,n){function o(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!a.string(e))throw new TypeError("Second argument must be a String");if(!a.fn(n))throw new TypeError("Third argument must be a Function");if(a.node(t))return i(t,e,n);if(a.nodeList(t))return r(t,e,n);if(a.string(t))return c(t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function i(t,e,n){return t.addEventListener(e,n),{destroy:function(){t.removeEventListener(e,n)}}}function r(t,e,n){return Array.prototype.forEach.call(t,function(t){t.addEventListener(e,n)}),{destroy:function(){Array.prototype.forEach.call(t,function(t){t.removeEventListener(e,n)})}}}function c(t,e,n){return s(document.body,t,e,n)}var a=t("./is"),s=t("delegate");e.exports=o},{"./is":3,delegate:2}],5:[function(t,e,n){function o(t,e){if(r)return r.call(t,e);for(var n=t.parentNode.querySelectorAll(e),o=0;o<n.length;++o)if(n[o]==t)return!0;return!1}var i=Element.prototype,r=i.matchesSelector||i.webkitMatchesSelector||i.mozMatchesSelector||i.msMatchesSelector||i.oMatchesSelector;e.exports=o},{}],6:[function(t,e,n){function o(t){var e;if("INPUT"===t.nodeName||"TEXTAREA"===t.nodeName)t.focus(),t.setSelectionRange(0,t.value.length),e=t.value;else{t.hasAttribute("contenteditable")&&t.focus();var n=window.getSelection(),o=document.createRange();o.selectNodeContents(t),n.removeAllRanges(),n.addRange(o),e=n.toString()}return e}e.exports=o},{}],7:[function(t,e,n){function o(){}o.prototype={on:function(t,e,n){var o=this.e||(this.e={});return(o[t]||(o[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){function o(){i.off(t,o),e.apply(n,arguments)}var i=this;return o._=e,this.on(t,o,n)},emit:function(t){var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),o=0,i=n.length;for(o;i>o;o++)n[o].fn.apply(n[o].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),o=n[t],i=[];if(o&&e)for(var r=0,c=o.length;c>r;r++)o[r].fn!==e&&o[r].fn._!==e&&i.push(o[r]);return i.length?n[t]=i:delete n[t],this}},e.exports=o},{}],8:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","select"],r);else if("undefined"!=typeof o)r(n,e("select"));else{var c={exports:{}};r(c,i.select),i.clipboardAction=c.exports}}(this,function(t,e){"use strict";function n(t){return t&&t.__esModule?t:{"default":t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var i=n(e),r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol?"symbol":typeof t},c=function(){function t(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}return function(e,n,o){return n&&t(e.prototype,n),o&&t(e,o),e}}(),a=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=e.action,this.emitter=e.emitter,this.target=e.target,this.text=e.text,this.trigger=e.trigger,this.selectedText=""},t.prototype.initSelection=function t(){this.text?this.selectFake():this.target&&this.selectTarget()},t.prototype.selectFake=function t(){var e=this,n="rtl"==document.documentElement.getAttribute("dir");this.removeFake(),this.fakeHandler=document.body.addEventListener("click",function(){return e.removeFake()}),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.fontSize="12pt",this.fakeElem.style.border="0",this.fakeElem.style.padding="0",this.fakeElem.style.margin="0",this.fakeElem.style.position="fixed",this.fakeElem.style[n?"right":"left"]="-9999px",this.fakeElem.style.top=(window.pageYOffset||document.documentElement.scrollTop)+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,document.body.appendChild(this.fakeElem),this.selectedText=(0,i.default)(this.fakeElem),this.copyText()},t.prototype.removeFake=function t(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function t(){this.selectedText=(0,i.default)(this.target),this.copyText()},t.prototype.copyText=function t(){var e=void 0;try{e=document.execCommand(this.action)}catch(n){e=!1}this.handleResult(e)},t.prototype.handleResult=function t(e){e?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function t(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function t(){this.removeFake()},c(t,[{key:"action",set:function t(){var e=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=e,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function t(){return this._action}},{key:"target",set:function t(e){if(void 0!==e){if(!e||"object"!==("undefined"==typeof e?"undefined":r(e))||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===this.action&&e.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===this.action&&(e.hasAttribute("readonly")||e.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');this._target=e}},get:function t(){return this._target}}]),t}();t.exports=a})},{select:6}],9:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","./clipboard-action","tiny-emitter","good-listener"],r);else if("undefined"!=typeof o)r(n,e("./clipboard-action"),e("tiny-emitter"),e("good-listener"));else{var c={exports:{}};r(c,i.clipboardAction,i.tinyEmitter,i.goodListener),i.clipboard=c.exports}}(this,function(t,e,n,o){"use strict";function i(t){return t&&t.__esModule?t:{"default":t}}function r(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function c(t,e){if(!t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!e||"object"!=typeof e&&"function"!=typeof e?t:e}function a(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}function s(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}var l=i(e),u=i(n),f=i(o),d=function(t){function e(n,o){r(this,e);var i=c(this,t.call(this));return i.resolveOptions(o),i.listenClick(n),i}return a(e,t),e.prototype.resolveOptions=function t(){var e=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText},e.prototype.listenClick=function t(e){var n=this;this.listener=(0,f.default)(e,"click",function(t){return n.onClick(t)})},e.prototype.onClick=function t(e){var n=e.delegateTarget||e.currentTarget;this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new l.default({action:this.action(n),target:this.target(n),text:this.text(n),trigger:n,emitter:this})},e.prototype.defaultAction=function t(e){return s("action",e)},e.prototype.defaultTarget=function t(e){var n=s("target",e);return n?document.querySelector(n):void 0},e.prototype.defaultText=function t(e){return s("text",e)},e.prototype.destroy=function t(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(u.default);t.exports=d})},{"./clipboard-action":8,"good-listener":4,"tiny-emitter":7}]},{},[9])(9)});


var regles_amb_radio = Array('opcio_general', 'incoatius', 'incoatius2', 'demostratius', 'accentuacio', 'concorda_dues', 
   'municipis', 'variant', 'apostrof', 'guio', 'guiopera', 'interrogant', 'exclamacio', 'percent', 'diacritics', 'pronom_se');
var regles_amb_checkbox = Array('recomana_preferents', 'evita_colloquials', 'espais_blancs', 'prioritza_cometes', 'tres_punts', 'mostra_opcions');
var langCode="ca-ES";
var userOptions="";
var SC_COOKIE = 'sc-languagetool';
var placeholdervisible = true;

(function($) {
  $(document).ready(function() {
    readCookieStatus();
    dooptions();
    $(document).click(function() {
      showoptions();
      dooptions();
    });
    $('#text_prova').click(function() {
        insertDemoText();
    });
    $('#clear_text').click(function() {
        clearText();
    });
    $('.submit').click(function() {
      dochecktext();
      return false;
    });
  });
}(jQuery));

function insertDemoText() {
  var myDemoText = "Aquests frases servixen per a probar algun de les errades que detecta el corrector gramaticals. Proveu les variants de flexió verbal: penso, pense, pens. L'accentuació valenciana o general: café o cafè. Paraules errònies segons el context: Et menjaràs tots els canalons? Li va infringir un càstig sever. Errors de sintaxi: la persona amb la que vaig parlar. I algunes altres opcions: Quan es celebrarà la festa? Soc un os bru que menja mores. Sóc un ós bru que menja móres.";
  tinyMCE.get('checktext').setContent(myDemoText);
  tinyMCE.get('checktext').execCommand('mceInsertContent', false,"");
}

function clearText() {
  tinyMCE.get('checktext').setContent("");
  tinyMCE.get('checktext').execCommand('mceInsertContent', false,"");
}

function getPlainText() {
    var userText = tinyMCE.activeEditor.getContent();
    var tag = document.createElement('div');
    tag.innerHTML = userText;
    return tag.innerText;                                                                                                                     
}


function showoptions() {
  if (!jQuery("input[name=mostra_opcions]:checked").val()) {
    document.getElementById("opcionscorreccio").style.display = "none";
  } else {
      document.getElementById("opcionscorreccio").style.display = "initial";
      if (jQuery("input[name=variant]:checked").val() == "variant_valencia") {
         document.getElementById("opcionscorreccio_valencia").style.display = "initial"; 
      } else {
         document.getElementById("opcionscorreccio_valencia").style.display = "none";
      }
  }
}

tinyMCE.init({
  mode: "specific_textareas",
  editor_selector: "lt",
  plugins: "AtD,paste",
  paste_text_sticky: true,
  auto_focus : "checktext",
  setup:function(ed) {
      ed.onInit.add(function(ed) {
          ed.pasteAsPlainText = true;
      });
      ed.onKeyDown.add(function(ed, e) {
          if (e.ctrlKey && e.keyCode == 13) {  // Ctrl+Return
              dochecktext();
              tinymce.dom.Event.cancel(e);
          } 
      });
      // remove any 'no errors found' message:
      ed.onKeyUp.add(function(ed, e) {
          if (!e.keyCode || e.keyCode != 17) {  // don't hide if user used Ctrl+Return
              jQuery('#feedbackMessage').html('');
          }
      });
      ed.onPaste.add(function(ed, e) {
          jQuery('#feedbackMessage').html('');
      });
  },

  /* translations: */
  languagetool_i18n_no_errors: {
    // "No errors were found.":
    "ca": "No s\'ha trobat cap error",
    'ca-ES-valencia': 'No s\'ha trobat cap error'
  },
  languagetool_i18n_explain: {
    // "Explain..." - shown if there is an URL with a detailed description:
    'ca': 'Més informació…',
    'ca-ES-valencia': 'Més informació…'
  },
  languagetool_i18n_ignore_once: {
    // "Ignore this error":
    'ca': 'Crec que no hi ha error',
    'ca-ES-valencia': 'Crec que no hi ha error'
  },
  languagetool_i18n_edit_manually: {
      'ca': 'Edita manualment',
      'ca-ES-valencia': 'Edita manualment'
    },
  languagetool_i18n_ignore_all: {
    // "Ignore this kind of error":
    'ca': 'Ignora aquesta classe d\'errors',
    'ca-ES-valencia': 'Ignora aquesta classe d\'errors'
  },
  languagetool_i18n_rule_implementation: {
    // "Rule implementation":
    'ca': 'Informació sobre la regla...',
    'ca-ES-valencia': 'Informació sobre la regla...',
  },
  languagetool_i18n_edit_manually: {
    'ca': 'Edita manualment',
    'ca-ES-valencia': 'Edita manualment'
  },
  languagetool_i18n_suggest_word: {
    // "Suggest word for dictionary...": 
    // *** Also set languagetool_i18n_suggest_word_url below if you set this ***
    'ca': 'Suggereix una paraula per al diccionari...',
    'ca-ES-valencia': 'Suggereix una paraula per al diccionari...'
  },
  languagetool_i18n_suggest_word_url: {
    // "Suggest word for dictionary...":
    'ca': 'http://community.languagetool.org/suggestion?word={word}&lang=ca',
    'ca-ES-valencia': 'http://community.languagetool.org/suggestion?word={word}&lang=ca'
  },

  languagetool_i18n_current_lang: function() {
    return document.checkform.lang.value;
  },
  /* The URL of your LanguageTool server.
     If you use your own server here and it's not running on the same domain 
     as the text form, make sure the server gets started with '--allow-origin ...' 
     and use 'https://your-server/v2/check' as URL: */
  languagetool_rpc_url: "/languagetool/api/v2/check",
  //languagetool_rpc_url: "https://www.softcatala.org/languagetool/api/v2/check",
  /* edit this file to customize how LanguageTool shows errors: */
  languagetool_css_url: "/themes/wp-softcatala/inc/languagetool/online-check/tiny_mce/plugins/atd-tinymce/css/content.css",
  /* this stuff is a matter of preference: */
  theme: "advanced",
  theme_advanced_buttons1: "",
  theme_advanced_buttons2: "",
  theme_advanced_buttons3: "",
  theme_advanced_toolbar_location: "none",
  theme_advanced_toolbar_align: "left",
  theme_advanced_statusbar_location: "bottom", //"none",
  theme_advanced_path: false,
  theme_advanced_resizing: true,
  theme_advanced_resize_horizontal : false,
  theme_advanced_resizing_use_cookie: false,
  gecko_spellcheck: false
});

function disableRevisa() {
    if(!checkinprogress) {
        checkinprogress = true;

        jQuery(".submit").prop('disabled', true);
        jQuery(".submit").addClass('bt-basic-gris');
        jQuery(".submit").removeClass('bt-basic-vermell');
    }
}

function enableRevisa() {
    if(checkinprogress) {
        checkinprogress = false;

        jQuery(".submit").prop('disabled', false);
        jQuery(".submit").addClass('bt-basic-vermell');
        jQuery(".submit").removeClass('bt-basic-gris');
    }
}

let checkinprogress = false;
function dochecktext() {
  if(checkinprogress) {
      return;
  }

  disableRevisa();

  var maxTextLength = 30000;
  var userText = tinyMCE.activeEditor.getContent();
  if (userText.length > maxTextLength) {
    var errorText = "Error: el text és massa llarg (" + userText.length + " caràcters). Màxim: " + maxTextLength + " caràcters.";
      jQuery('#feedbackErrorMessage').html("<div id='severeError'>" + errorText + "</div>");
  } else if (userText.length > 0) {
    //normalize text
    if (String.prototype.hasOwnProperty('normalize')) {
      var normalizedText = userText.normalize("NFC");
      tinyMCE.activeEditor.setContent(normalizedText);
    }
    //alert(langCode + " " + userOptions);
    tinyMCE.activeEditor.execCommand("mceWritingImprovementTool", langCode, userOptions);
    // Actualizem mètriques
    mostraMetriques(tinyMCE.activeEditor.getContent({ format: "text" }));


  } else {
    enableRevisa();
  }
}

function dooptions() {
    saveCookieStatus();
    update_enabled_rules();
    showoptions();
    // opcions valencià (ca-ES-valencia)
    var disabledRules = [];
    var enabledRules = [];
    var disabledCategories = [];

    // opcions general (ca-ES)
    var ca_disabledRules = [];
    var ca_enabledRules = []; 
    var ca_disabledCategories = []; 

    //Opcions de tipografia
    var typo_enabledRules = [];
    var typo_disabledRules = [];
    var typo_disabledCategories = [];

    if (jQuery("input[name=opcio_general]:checked").val() == "criteris_gva") {

        disabledRules.push("PERCENT_SENSE_ESPAI","AL_INFINITIU","EVITA_INFINITIUS_INDRE","CA_SIMPLE_REPLACE_DNV");

        enabledRules.push("LEXIC_VAL","VERBS_I_ANTIHIATICA","EVITA_AQUEIX_EIXE","PREFERENCIES_VERBS_VALENCIANS","NUMERALS_VALENCIANS","PARTICIPIS_IT","ORDINALS_E","EXIGEIX_PLURALS_SCOS","EXIGEIX_PLURALS_JOS","EXIGEIX_PLURALS_S","EXIGEIX_INFINITIUS_INDRE","EXIGEIX_INFINITIUS_ALDRE","EXIGEIX_US,HUI");

        disabledCategories.push("DNV_PRIMARY_FORM");       

    } else if (jQuery("input[name=opcio_general]:checked").val() == "criteris_uv") {
        disabledRules.push("EXIGEIX_ACCENTUACIO_VALENCIANA");

        enabledRules.push("VERBS_I_ANTIHIATICA","EVITA_AQUEIX_EIXE","PREFERENCIES_VERBS_VALENCIANS","PARTICIPIS_IT","ORDINALS_E","EXIGEIX_PLURALS_SCOS","EXIGEIX_PLURALS_JOS","EXIGEIX_PLURALS_S","EXIGEIX_INFINITIUS_ALDRE","EXIGEIX_US","EXIGEIX_ACCENTUACIO_GENERAL","EXIGEIX_TENIR_VENIR","LEXIC_VAL","NUMERALS_GENERALS","AVUI","PREFERENCIES_LEXIC_UNIVERSITATS");

        
    } else {
        //disabledRules = disabledRules + ",VULLGA,AHI"; //acceptat per AVL no Generalitat

        /* incoatius -eix/-ix */
        if (jQuery("input[name=incoatius]:checked").val() == "incoatius_ix") {
            enabledRules.push("EXIGEIX_VERBS_IX");
            disabledRules.push("EXIGEIX_VERBS_EIX");
        };
        /* incoatius -esc/-isc */
        if (jQuery("input[name=incoatius2]:checked").val() == "incoatius_esc") {
            enabledRules.push("EXIGEIX_VERBS_ESC");
            disabledRules.push("EXIGEIX_VERBS_ISC");
        };
        /* demostratius aquest/este */
        if (jQuery("input[name=demostratius]:checked").val() == "demostratius_este") {
            enabledRules.push("EVITA_DEMOSTRATIUS_AQUEST");
            disabledRules.push("EVITA_DEMOSTRATIUS_ESTE");
        };
        /* accentuació café /cafè */
        if (jQuery("input[name=accentuacio]:checked").val() == "accentuacio_general") {
            enabledRules.push("EXIGEIX_ACCENTUACIO_GENERAL");
            disabledRules.push("EXIGEIX_ACCENTUACIO_VALENCIANA");
        };
        /* concordança dos/dues */
        if (jQuery("input[name=concorda_dues]:checked").val() == "concorda_dos") {
            enabledRules.push("CONCORDANCES_NUMERALS_DOS");
            disabledRules.push("CONCORDANCES_NUMERALS_DUES");
        };
    }

    /* municipis nom valencià/oficial */
    if (jQuery("input[name=municipis]:checked").val() == "municipi_nom_oficial" && jQuery("input[name=opcio_general]:checked").val() != "criteris_uv") {
        enabledRules.push("MUNICIPIS_OFICIAL");
        disabledRules.push("MUNICIPIS_VALENCIA");
    };

    // paraules preferents
    if (!jQuery("input[name=recomana_preferents]:checked").val()) {
        disabledCategories.push("DNV_SECONDARY_FORM");
        disabledRules.push("CA_SIMPLE_REPLACE_DNV_SECONDARY");
    };
    // terminació -iste
    if (!jQuery("input[name=recomana_preferents]:checked").val() &&
        jQuery("input[name=opcio_general]:checked").val() == "criteris_cap") {
        disabledRules.push("EVITA_ISTE","AHI","VULLGA");
    }
    // paraules col·loquials
    if (!jQuery("input[name=evita_colloquials]:checked").val()) {
        disabledCategories.push("DNV_COLLOQUIAL");
        disabledRules.push("CA_SIMPLE_REPLACE_DNV_COLLOQUIAL");
    };

    if (jQuery("input[name=diacritics]:checked").val() == "diacritics_iec") {
       typo_disabledCategories.push("DIACRITICS_TRADITIONAL"); // unnecessary after update
       typo_disabledRules.push("DIACRITICS_TRADITIONAL_RULES");
       typo_disabledRules.push("CA_SIMPLEREPLACE_DIACRITICS_TRADITIONAL");
       typo_enabledRules.push("CA_SIMPLEREPLACE_DIACRITICS_IEC"); 
    }
    else {
       typo_enabledRules.push("DIACRITICS_TRADITIONAL_RULES");
       typo_enabledRules.push("CA_SIMPLEREPLACE_DIACRITICS_TRADITIONAL");
       typo_disabledRules.push("CA_SIMPLEREPLACE_DIACRITICS_IEC"); 
    }
    if (jQuery("input[name=pronom_se]:checked").val() == "pronom_se_indiferent") {typo_disabledRules.push("SE_DAVANT_SC"); };
    if (jQuery("input[name=apostrof]:checked").val() == "apostrof_tipografic") {typo_enabledRules.push("APOSTROF_TIPOGRAFIC","COMETES_TIPOGRAFIQUES"); };
    if (jQuery("input[name=apostrof]:checked").val() == "apostrof_recte") {typo_enabledRules.push("APOSTROF_RECTE","COMETES_RECTES"); };
    if (jQuery("input[name=guio]:checked").val() == "guio_llarg") {typo_enabledRules.push("GUIO_LLARG"); };
    if (jQuery("input[name=guio]:checked").val() == "guio_mitja") {typo_enabledRules.push("GUIO_MITJA"); };
    if (jQuery("input[name=guiopera]:checked").val() == "guiopera_dialegs") {typo_enabledRules.push("GUIO_SENSE_ESPAI"); };
    if (jQuery("input[name=guiopera]:checked").val() == "guiopera_enumeracions") {typo_enabledRules.push("GUIO_ESPAI"); };
    if (jQuery("input[name=interrogant]:checked").val() == "interrogant_mai") {typo_enabledRules.push("EVITA_INTERROGACIO_INICIAL"); };
    if (jQuery("input[name=interrogant]:checked").val() == "interrogant_sempre") {typo_enabledRules.push("CA_UNPAIRED_QUESTION"); };
    //if (jQuery("input[name=exclamacio]:checked").val() == "exclamacio_mai") {typo_enabledRules = typo_enabledRules + ",EVITA_EXCLAMACIO_INICIAL"; };
    if (jQuery("input[name=exclamacio]:checked").val() == "exclamacio_sempre") {
        typo_enabledRules.push("CA_UNPAIRED_EXCLAMATION"); 
        typo_disabledRules.push("EVITA_EXCLAMACIO_INICIAL"); };
    if (jQuery("input[name=exclamacio]:checked").val() == "exclamacio_indefinit") {typo_disabledRules.push("EVITA_EXCLAMACIO_INICIAL"); };
    //if (jQuery("input[name=percent]:checked").val() == "percent_senseespai") {typo_enabledRules = typo_enabledRules + ",PERCENT_SENSE_ESPAI"; };
    if (jQuery("input[name=percent]:checked").val() == "percent_ambespai") {
        typo_enabledRules.push("PERCENT_AMB_ESPAI"); 
        typo_disabledRules.push("PERCENT_SENSE_ESPAI");};
    if (jQuery("input[name=percent]:checked").val() == "percent_indefinit") {typo_disabledRules.push("PERCENT_SENSE_ESPAI"); };
    if (jQuery("input[name=tres_punts]:checked").val()) { typo_enabledRules.push("PUNTS_SUSPENSIUS"); };
    if (jQuery("input[name=prioritza_cometes]:checked").val()) { typo_enabledRules.push("PRIORITZAR_COMETES"); };
    //if (!jQuery("input[name=espais_blancs]:checked").val()) { typo_disabledRules.push("WHITESPACE_RULE"); };
   typo_disabledRules.push("WHITESPACE_RULE"); //disable always WHITESPACE_RULE 

    //Variant principal: general/valecià/balear
    if (jQuery("input[name=variant]:checked").val() == "variant_general") {
    } else if (jQuery("input[name=variant]:checked").val() == "variant_valencia") {
      ca_enabledRules.push("EXIGEIX_VERBS_VALENCIANS","EXIGEIX_POSSESSIUS_U");
      ca_disabledRules.push("EXIGEIX_VERBS_CENTRAL","EVITA_DEMOSTRATIUS_EIXE","EXIGEIX_POSSESSIUS_V");
      pushArray(ca_enabledRules, enabledRules);
      pushArray(ca_disabledRules, disabledRules);
      pushArray(ca_disabledCategories, disabledCategories);
    } else if (jQuery("input[name=variant]:checked").val() == "variant_balear") {
      ca_enabledRules.push("EXIGEIX_VERBS_BALEARS");
      ca_disabledRules.push("EXIGEIX_VERBS_CENTRAL","CA_SIMPLE_REPLACE_BALEARIC");
    }

    pushArray(ca_enabledRules, typo_enabledRules);
    pushArray(ca_disabledRules, typo_disabledRules);
    pushArray(ca_disabledCategories, typo_disabledCategories);
    pushArray(enabledRules, typo_enabledRules);
    pushArray(disabledRules, typo_disabledRules);
    pushArray(disabledCategories, typo_disabledCategories);

    var today = new Date();
    jQuery('#output_text').html(
        "#LanguageTool Catalan configuration\n" +
        "#" + today + "\n" +
        "disabledRules.ca-ES=" + ca_disabledRules.join() + "\n" +
        "enabledRules.ca-ES=" + ca_enabledRules.join() + "\n" +
        "disabledCategories.ca-ES=" + ca_disabledCategories.join() + "\n" +
        "disabledRules.ca-ES-valencia=" + disabledRules.join() + "\n" +
        "enabledRules.ca-ES-valencia=" + enabledRules.join() + "\n" +
        "disabledCategories.ca-ES-valencia=" + disabledCategories.join() + "\n"
        );

    userOptions="&level=picky"; 
    if (jQuery("input[name=variant]:checked").val() == "variant_valencia") {
  langCode="ca-ES-valencia";
        if (disabledRules.join()) { userOptions += "&disabledRules=" + disabledRules.join(); }
        if (enabledRules.join()) { userOptions += "&enabledRules=" + enabledRules.join(); }
        if (disabledCategories.join()) { userOptions += "&disabledCategories=" + disabledCategories.join(); }
    } else {
  langCode="ca-ES";
        if (ca_disabledRules.join()) { userOptions += "&disabledRules=" + ca_disabledRules.join(); }
        if (ca_enabledRules.join()) { userOptions += "&enabledRules=" + ca_enabledRules.join(); }
        if (ca_disabledCategories.join()) { userOptions += "&disabledCategories=" + ca_disabledCategories.join(); }
    }

    const MIME_TYPE = 'text/plain';
    var container = document.querySelector('#container');
    var output = container.querySelector('output');
    var output_text = document.querySelector('#output_text');
    var bb = new Blob([output_text.textContent], {type: MIME_TYPE});
    var a = document.createElement('a');
    a.download = "Languagetool.cfg"; 
    a.href = window.URL.createObjectURL(bb);
    a.textContent = 'Baixa la configuració com a fitxer';
    a.dataset.downloadurl = [MIME_TYPE, a.download, a.href].join(':');
    a.draggable = true; // Don't really need, but good practice.
    a.classList.add('dragout');
    output.innerHTML = '';
    output.appendChild(a);

}

function pushArray(arr, arr2) {
    arr.push.apply(arr, arr2);
}


// COOKIE
function saveCookieStatus() {
    if (!jQuery.getCookie(SC_COOKIE)) {
      jQuery.setCookie(SC_COOKIE, '');
    }
    jQuery.each(regles_amb_radio, function(index, nom) {
        var valor = jQuery('[type="radio"][name="' + nom + '"]:checked').val();
        jQuery.setMetaCookie(nom, SC_COOKIE, valor);
    });
    jQuery.each(regles_amb_checkbox, function(index, nom) {
        var valor = jQuery('input[name=' + nom + ']:checked').val();
        if (valor) {
            jQuery.setMetaCookie(nom, SC_COOKIE, 1);
        } else {
            jQuery.setMetaCookie(nom, SC_COOKIE, -1);
        }
    });
}

function readCookieStatus() {
    if (jQuery.getCookie(SC_COOKIE)) {
  jQuery.each(regles_amb_radio, function(index, nom) {
            var valor = jQuery.getMetaCookie(nom, SC_COOKIE);
            if (valor !== undefined) {
    jQuery('[type="radio"][name="' + nom + '"][value="' + valor + '"]')
                    .attr('checked', 'checked');
            }
  });
  jQuery.each(regles_amb_checkbox, function(index, nom) {
            var valor = jQuery.getMetaCookie(nom, SC_COOKIE);
            if (valor !== undefined) {
    if (valor > 0) {
                    jQuery('input[name=' + nom + ']').attr('checked', 'checked');
    } else {
                    jQuery('input[name=' + nom + ']').removeAttr('checked');
    }
            }
  });
    }
    update_enabled_rules();
}

function update_enabled_rules() {
    if (jQuery("input[name=opcio_general]:checked").val() != "criteris_cap") {
        document.getElementById("incoatius_eix").disabled = true;
        document.getElementById("incoatius_ix").disabled = true;
        document.getElementById("incoatius_esc").disabled = true;
        document.getElementById("incoatius_isc").disabled = true;
        document.getElementById("demostratius_aquest").disabled = true;
        document.getElementById("demostratius_este").disabled = true;
        document.getElementById("accentuacio_valenciana").disabled = true;
        document.getElementById("accentuacio_general").disabled = true;
        document.getElementById("concorda_dos_dues").disabled = true;
        document.getElementById("concorda_dos").disabled = true;
    } else {
        document.getElementById("incoatius_eix").disabled = false;
        document.getElementById("incoatius_ix").disabled = false;
        document.getElementById("incoatius_esc").disabled = false;
        document.getElementById("incoatius_isc").disabled = false;
        document.getElementById("demostratius_aquest").disabled = false;
        document.getElementById("demostratius_este").disabled = false;
        document.getElementById("accentuacio_valenciana").disabled = false;
        document.getElementById("accentuacio_general").disabled = false;
        document.getElementById("concorda_dos_dues").disabled = false;
        document.getElementById("concorda_dos").disabled = false;
    }
    if (jQuery("input[name=opcio_general]:checked").val() == "criteris_uv") {
        document.getElementById("municipi_nom_valencia").disabled = true;
        document.getElementById("municipi_nom_oficial").disabled = true;
    } else {
        document.getElementById("municipi_nom_valencia").disabled = false;
        document.getElementById("municipi_nom_oficial").disabled = false;
    }
}


function mostraMetriques(text){

  jQuery('#resultatmetriques').html('');
  jQuery('#metriques-col1').html('');
  jQuery('#metriques-col2').html('');

  jQuery.ajax({
    url: "https://api.softcatala.org/style-checker/v1/metrics",
    type:"GET",
    data : {'text':text},
    dataType: 'json',
    success : function(a){
        col1 = true;
        //console.log(a.metrics);
        for (const metrica in a.metrics){
          jQuery('#resultatmetriques').append('<strong>'+a.metrics[metrica].name +'</strong>:'+a.metrics[metrica].score +" ");
          
          str = `${a.metrics[metrica].name}: <span>${a.metrics[metrica].score}</span><br> `;
          
          if (col1 === true){ 
              jQuery('#metriques-col1').append(str);
              col1 = false;
          }else{
              jQuery('#metriques-col2').append(str);
              col1 = true;
          }

          
        }
        jQuery('#metriques').show();
        jQuery('#metriques-lat').show();
      
    },
    failure : function(){
        jQuery('#metriques').hide();
        jQuery('#metriques-lat').hide();
    }
  });


}

var $clipBoard = new Clipboard('#copy-text', {
  text: function(trigger) {
      return getPlainText();
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
          jQuery(this).tooltip('hide');
      }
  });
}

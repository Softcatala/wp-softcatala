 var lang = "ca";
 var lang_val = "ca-valencia";
 var lang_bal ="ca-balear";
 
 var numlang=new Soros(rules_ca, lang);
 var numlang_val=new Soros(rules_ca, lang_val);
 var numlang_bal=new Soros(rules_ca, lang_bal);
 
 var numlang2=new Soros(rules_roman, "Roman");
 
 document.getElementById('nombre').focus();
 
 ConvertNumberToText();
 
 function ConvertNumberToText() {
  var num = document.getElementById("nombre").value.trim();
  var resultat="";
  var warning="";
  var currency = numlang.run(num).replace(/\n/g,"<br>");
  var cardinal_masc = numlang.run("masculine " + num).replace(/\n/g,"<br>");
  var cardinal_masc_val = numlang_val.run("masculine " + num).replace(/\n/g,"<br>");
  var cardinal_masc_bal = numlang_bal.run("masculine " + num).replace(/\n/g,"<br>");
  var cardinal_fem = numlang.run("feminine " + num).replace(/\n/g,"<br>");
  var cardinal_fem_val = numlang_val.run("feminine " + num).replace(/\n/g,"<br>");
  var cardinal_fem_bal = numlang_bal.run("feminine " + num).replace(/\n/g,"<br>");
  var ordinal = numlang.run("ordinal " + num).replace(/\n/g,"<br>");
  var ordinal_val = numlang_val.run("ordinal " + num).replace(/\n/g,"<br>");
  var ordinal_number = numlang.run("ordinal-number " + num).replace(/\n/g,"<br>");
  var ordinal_bal = numlang_bal.run("ordinal " + num).replace(/\n/g,"<br>");
  var ordinal_number_val = numlang_val.run("ordinal-number " + num).replace(/\n/g,"<br>");
  var ordinal_number_bal = numlang_bal.run("ordinal-number " + num).replace(/\n/g,"<br>");
  var ordinal_fem = numlang.run("ordinal-feminine " + num).replace(/\n/g,"<br>");
  var ordinal_fem_val = numlang_val.run("ordinal-feminine " + num).replace(/\n/g,"<br>");
  var ordinal_fem_bal = numlang_bal.run("ordinal-feminine " + num).replace(/\n/g,"<br>");
  var ordinal_number_fem = numlang.run("ordinal-number-feminine " + num).replace(/\n/g,"<br>");
  var ordinal_number_fem_val = numlang_val.run("ordinal-number-feminine " + num).replace(/\n/g,"<br>");
  var ordinal_number_fem_bal = numlang_bal.run("ordinal-number-feminine " + num).replace(/\n/g,"<br>");
  var partitive = numlang.run("partitive " + num).replace(/\n/g,"<br>");
  var partitive_val = numlang_val.run("partitive " + num).replace(/\n/g,"<br>");
  var partitive_bal = numlang_bal.run("partitive " + num).replace(/\n/g,"<br>");
  var partitive_fem = numlang.run("partitive-feminine " + num).replace(/\n/g,"<br>");
  var partitive_fem_val = numlang_val.run("partitive-feminine " + num).replace(/\n/g,"<br>");
  var partitive_fem_bal = numlang_bal.run("partitive-feminine " + num).replace(/\n/g,"<br>");
  var fraction = numlang.run("fraction " + num).replace(/\n/g,"<br>");
  var fraction_val = numlang_val.run("fraction " + num).replace(/\n/g,"<br>");
  var fraction_bal = numlang_bal.run("fraction " + num).replace(/\n/g,"<br>");
  var fraction_fem = numlang.run("fraction-feminine " + num).replace(/\n/g,"<br>");
  var fraction_fem_val = numlang_val.run("fraction-feminine " + num).replace(/\n/g,"<br>");
  var fraction_fem_bal = numlang_bal.run("fraction-feminine " + num).replace(/\n/g,"<br>");
  var collective = numlang.run("collective " + num).replace(/\n/g,"<br>");
  var multiplicative = numlang.run("multiplicative " + num).replace(/\n/g,"<br>");
  var years = numlang.run("years " + num).replace(/\n/g,"<br>");
  
  var roman = numlang2.run(num).replace(/\n/g,"<br>");
  
  var flag_one= false;
  
  if(num.length > 605) {
    resultat = "<b>Atenció</b>: el nombre ha ser inferior a 10<sup>606</sup>.<br/>";
  }
  else {
    if(/\d[ ]?[A-Z]{3}$/.test(num) || /^[A-Z]{3}[ ]?[-−]?\d/.test(num) || /\d[ ]?[€\$£¥₩₽ɱ₿]$/.test(num)){
      resultat = "<b>Divisa</b><br/>";
      if ((currency == "") || (currency == " amb ")) {
        resultat += "El codi de divisa no es reconeix.<br/>" 
      }
    else {
    resultat += currency + "<br/>";
    }
    } 
    else {
      if (cardinal_masc) {
        resultat = "<b>Cardinal</b><br/>";
        if (cardinal_masc === cardinal_fem){
          resultat += "<span id=\"card\">" + cardinal_masc + "</span> <input id=\"card_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card', 'card_parla');return false;\">";
        if (cardinal_masc_val !== cardinal_masc) {
          resultat += ", <span id=\"card_val\">" + cardinal_masc_val + "</span> (val.) <input id=\"card_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_val', 'card_val_parla');return false;\">";
        }
  	    if (cardinal_masc_bal !== cardinal_masc) {
  	      resultat += ", <span id=\"card_bal\">" + cardinal_masc_bal + "</span> (bal.) <input id=\"card_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_bal', 'card_bal_parla');return false;\">";
        }
        resultat += "<br/>";      
      }
      else {
        resultat += "Masculí: <span id=\"card\">" + cardinal_masc + "</span> <input id=\"card_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card', 'card_parla');return false;\">";
        if (cardinal_masc_val !== cardinal_masc) {
          resultat += ", <span id=\"card_val\">" + cardinal_masc_val + "</span> (val.) <input id=\"card_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_val', 'card_val_parla');return false;\">";
        }
        if (cardinal_masc_bal !== cardinal_masc) {
          resultat += ", <span id=\"card_bal\">" + cardinal_masc_bal + "</span> (bal.) <input id=\"card_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_bal', 'card_bal_parla');return false;\">";
        }
        resultat += "<br/>";
        resultat += "Femení: <span id=\"card_f\">" + cardinal_fem + "</span> <input id=\"card_f_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_f', 'card_f_parla');return false;\">"; 
        if (cardinal_fem_val !== cardinal_fem) {
          resultat += ", <span id=\"card_f_val\">" + cardinal_fem_val + "</span> (val.) <input id=\"card_f_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_f_val', 'card_f_val_parla');return false;\">";
        }
        if (cardinal_fem_bal !== cardinal_fem) {
          resultat += ", <span id=\"card_f_bal\">" + cardinal_fem_bal + "</span> (bal.) <input id=\"card_f_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('card_b_val', 'card_f_bal_parla');return false;\">";
        }
        resultat += "<br/>";
      }
        if (/\bun$/.test(cardinal_masc)) {
          flag_one=true;
        }
        if (ordinal) {
          resultat += "<b>Ordinal</b><br/>";
          resultat += "Masculí: <span id=\"ord\">" + ordinal + "</span> " + ordinal_number + "</span> <input id=\"ord_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord', 'ord_parla');return false;\">";
          if (ordinal_val !== ordinal) {
            resultat += ", <span id=\"ord_val\">" + ordinal_val + "</span> " + ordinal_number_val + " (val.) <input id=\"ord_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord_val', 'ord_val_parla');return false;\">";
          }
          if (ordinal_bal !== ordinal) {
            resultat += ", <span id=\"ord_bal\">" + ordinal_bal + "</span> " + ordinal_number_bal + " (bal.) <input id=\"ord_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord_bal', 'ord_bal_parla');return false;\">";
          }
          resultat += "<br/>";
          resultat += "Femení: <span id=\"ord_f\">" + ordinal_fem + "</span> " + ordinal_number_fem + "<input id=\"ord_f_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord_f', 'ord_f_parla');return false;\">";
          if (ordinal_fem_val !== ordinal_fem) {
            resultat += ", <span id=\"ord_f_val\">" + ordinal_fem_val + "</span> " + ordinal_number_fem_val + " (val.) <input id=\"ord_f_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord_f_val', 'ord_f_val_parla');return false;\">";
          }
          if (ordinal_fem_bal !== ordinal_fem) {
            resultat += ", <span id=\"ord_f_bal\">" + ordinal_fem_bal + "</span> " + ordinal_number_fem_bal + " (bal.) <input id=\"ord_f_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('ord_f_bal', 'ord_f_bal_parla');return false;\">";
          }
          resultat += "<br/>";
        }
        if (partitive) {
          resultat += "<b>Fraccionari</b>";
          if (partitive !== "unitat") {
            resultat += "<br/>Masculí: <span id=\"part\">" + partitive + "</span> <input id=\"part_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part', 'part_parla');return false;\">";
          }
          if (partitive_val !== partitive) {
            resultat += ", <span id=\"part_val\">" + partitive_val + "</span> (val.) <input id=\"part_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part_val', 'part_val_parla');return false;\">";
          }
          if (partitive_bal !== partitive) {
            resultat += ", <span id=\"part_bal\">" + partitive_bal + "</span> (bal.) <input id=\"part_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part_bal', 'part_bal_parla');return false;\">";
          }
          resultat += "<br/>";
          resultat += "Femení: <span id=\"part_f\">" + partitive_fem + "</span> <input id=\"part_f_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part_f', 'part_f_parla');return false;\">";
          if (partitive_fem_val !== partitive_fem) {
            resultat += ", <span id=\"part_f_val\">" + partitive_fem_val + "</span> (val.) <input id=\"part_f_val_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part_f_val', 'part_f_val_parla');return false;\">";
          }
          if (partitive_fem_bal !== partitive_fem) {
            resultat += ", <span id=\"part_f_bal\">" + partitive_fem_bal + "</span> (bal.) <input id=\"part_f_bal_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('part_f_bal', 'part_f_bal_parla');return false;\">";
          }
          resultat += "<br/>";
        }
        if ((roman) && (!/^\d/.test(roman))){
          resultat += "<b>Numeració romana</b><br/>";
		  resultat += roman + "</br>";
	    }
        if (collective) {
          resultat += "<b>Col·lectiu</b><br/>";
          resultat += "<span id=\"coll\">" + collective + "</span> <input id=\"coll_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('coll', 'coll_parla');return false;\"><br/>";
        }
        if (multiplicative) {
          resultat += "<b>Multiplicatiu</b><br/>";
          resultat += "<span id=\"mult\">" + multiplicative + "</span> <input id=\"mult_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('mult', 'mult_parla');return false;\"><br/>";
        }
        if (years) {
          resultat += "<b>Període d'anys</b><br/>";
          resultat += "<span id=\"anys\">" + years + "</span> <input id=\"anys_parla\" type=\"submit\" value=\"&#128266;\" onclick=\"speak_text('anys', 'anys_parla');return false;\"><br/>";
        }
      }
      if (fraction && (!cardinal_masc)) {
        resultat += "<b>Fracció</b><br/>";
        resultat += "Masculí: " + fraction;
        if (fraction_val !== fraction) {
          resultat += ", " + fraction_val + " (val.)";
        }
        if (fraction_bal !== fraction) {
          resultat += ", " + fraction_bal + " (bal.)";
        }
        resultat += "<br/>";
        resultat += "Femení: " + fraction_fem;
        if (fraction_fem_val !== fraction_fem) {
          resultat += ", " + fraction_fem_val + " (val.)";
        }
        if (fraction_fem_bal !== fraction_fem) {
          resultat += ", " + fraction_fem_bal + " (bal.)";
        }
        resultat += "<br/>";
      }
    }
  }
  
  if (resultat){
    document.getElementById("resultatp").style.display = "block";
    document.getElementById("resultat").innerHTML=resultat;
  }else{
    document.getElementById("resultatp").style.display = "none";
  }
  
  if(flag_one) {
    warning = "<b>Atenció</b>:";
    warning += " els nombres acabats en \"un\" s'usen acabats en \"u\" si indiquen ordre d'aparició, de col·locació o de successió, com a sinònim de primer. També es pot usar la forma acabada en \"u\" per a referir-se al nom del nombre natural o per a comptar.";
    document.getElementById("alerta").style.display = "block";
  }else{
    document.getElementById("alerta").style.display = "none";
  }
  document.getElementById("warning").innerHTML=warning;
}

function speak_text(id, element) {

    text = document.getElementById(id).innerHTML;
    hash = md5(text).substring(0, 8);
    
    document.getElementById(element).disabled = true;
    url = `https://api.softcatala.org/tts-service/v1/speak/?text=${text}&token=${hash}`;
    aud = new Audio(url)

    aud.onended = function() {
        document.getElementById(element).disabled = false;
    }; 

    aud.play();

}

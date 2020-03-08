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
          resultat += cardinal_masc;
        if (cardinal_masc_val !== cardinal_masc) {
          resultat += ", " + cardinal_masc_val + " (val.)";
        }
  	    if (cardinal_masc_bal !== cardinal_masc) {
  	      resultat += ", " + cardinal_masc_bal + " (bal.)";
        }
        resultat += "<br/>";      
      }
      else {
        resultat += "Masculí: " + cardinal_masc;
        if (cardinal_masc_val !== cardinal_masc) {
          resultat += ", " + cardinal_masc_val + " (val.)";
        }
        if (cardinal_masc_bal !== cardinal_masc) {
          resultat += ", " + cardinal_masc_bal + " (bal.)";
        }
        resultat += "<br/>";
        resultat += "Femení: " + cardinal_fem; 
        if (cardinal_fem_val !== cardinal_fem) {
          resultat += ", " + cardinal_fem_val + " (val.)";
        }
        if (cardinal_fem_bal !== cardinal_fem) {
          resultat += ", " + cardinal_fem_bal + " (bal.)";
        }
        resultat += "<br/>";
      }
        if (/\bun$/.test(cardinal_masc)) {
          flag_one=true;
        }
        if (ordinal) {
          resultat += "<b>Ordinal</b><br/>";
          resultat += "Masculí: " + ordinal + " " + ordinal_number;
          if (ordinal_val !== ordinal) {
            resultat += ", " + ordinal_val + " " + ordinal_number_val + " (val.)";
          }
          if (ordinal_bal !== ordinal) {
            resultat += ", " + ordinal_bal + " " + ordinal_number_bal + " (bal.)";
          }
          resultat += "<br/>";
          resultat += "Femení: " + ordinal_fem + " " + ordinal_number_fem;
          if (ordinal_fem_val !== ordinal_fem) {
            resultat += ", " + ordinal_fem_val + " " + ordinal_number_fem_val + " (val.)";
          }
          if (ordinal_fem_bal !== ordinal_fem) {
            resultat += ", " + ordinal_fem_bal + " " + ordinal_number_fem_bal + " (bal.)";
          }
          resultat += "<br/>";
        }
        if ((roman) && (!/^\d/.test(roman))){
          resultat += "<b>Numeració romana</b><br/>";
		  resultat += roman + "</br>";
	    }
        if (collective) {
          resultat += "<b>Col·lectiu</b><br/>";
          resultat += collective + "<br/>";
        }
        if (multiplicative) {
          resultat += "<b>Multiplicatiu</b><br/>";
          resultat += multiplicative + "<br/>";
        }
        if (years) {
          resultat += "<b>Període d'anys</b><br/>";
          resultat += years + "<br/>";
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

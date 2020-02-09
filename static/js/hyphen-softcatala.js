var hyphenate = createHyphenator(hyphenationPatternsCa, {
    debug: true,
    hyphenChar: '_'
});

String.prototype.lines = function() { return this.split(/\r*\n/); }
String.prototype.lineCount = function() { return this.lines().filter(String).length; }

String.prototype.adjustHyphenatedText = function() {
    return this.replace(/(l·|ŀ)/g, "l")
        .replace(/(L·|Ŀ)/g, "L")
        .replace(/\bPius\b/g, "Pi_us")
        .replace(/à_cid pe_ri_ò_dic/g, "à_cid per_iò_dic");
}

String.prototype.removeApostrophe = function() {
    return this.replace(/^['’]/, '')
        .replace(/['’]$/, '')
        .replace(/([^a-zàáèéìíòóùúäëïöüç·ñŀâêîôûA-ZÀÁÈÉÌÍÒÓÙÚÄËÏÖÜÇ·ÑĿÂÊÎÔÛ])['’]([^a-zàáèéìíòóùúäëïöüç·ñŀâêîôûA-ZÀÁÈÉÌÍÒÓÙÚÄËÏÖÜÇ·ÑĿÂÊÎÔÛ])/, '$1$2')
}


String.prototype.isErraSensible = function() {  
    var erraSensible = [ "a_cer", "ac_nur", "a_e_ro_far", "a_fer", "al_càs_ser", "al_gut_zir", "al_mo_gà_ver", "al_mí_var", "a_ma_teur", "a_mer",
        "a_mor", "an_gu_lar", "an_te_ri_or", "an_ti_so_lar", "a_nu_lar", "a_qüí_fer", "as_tor", "a_tur", "at_zar", "at_zur",
        "au_ri_cu_lar", "au_to_car", "au_xi_li_ar", "a_var", "a_xil_lar", "bai_xa_mar", "ba_le_ar", "bar", "ba_sar", "ber_ber",
        "bi_li_ar", "bi_llar", "bi_no_cu_lar", "bi_po_lar", "bo_lí_var", "bor", "bro_mur", "bí_ter", "bò_xer", "bún_quer",
        "ca_dà_ver", "caix_mir", "ca_la_mar", "ca_lo_rí_fer", "can_gur", "ca_ni_cu_lar", "ca_pil_lar", "car", "car_bur",
        "car_di_o_vas_cu_lar", "ca_ràc_ter", "cat_cher", "ca_tè_ter", "ca_vi_ar", "cel_lu_lar", "ci_a_nur", "ci_clo_mo_tor",
        "ci_gar", "cir_cu_lar", "cir_cum_po_lar", "clar_obs_cur", "clau_dà_tor", "clor", "clo_rur", "clí_per", "con_ci_li_ar",
        "con_jur", "con_su_lar", "con_tra_cor", "con_tra_fur", "cor", "cre_pus_cu_lar", "crà_ter", "cuir", "càn_cer",
        "còn_dor", "cór_ner", "cú_ter", "dac_ti_lar", "da_kar", "de_cor", "des_a_mor", "des_ho_nor", "des_po_der", "dis_par",
        "dò_lar", "e_li_xir", "e_mir", "e_nyor", "e_pis_to_lar", "e_qua_dor", "er_ror", "es_co_lar", "es_càn_ner", "es_cú_ter",
        "es_fín_ter", "es_pec_ta_cu_lar", "es_tel_lar", "es_tor", "es_tu_por", "es_tàn_dard", "es_tàr_ter", "e_xem_plar",
        "ex_te_ri_or", "ex_tra_cel_lu_lar", "fa_mi_li_ar", "fa_quir", "far", "fa_vor", "fer_vor", "flu_o_rur", "ful_gor",
        "fu_ni_cu_lar", "fur", "fu_ror", "fu_tur", "fè_mur", "fòs_for", "gir", "gua_dal_qui_vir", "guèi_ser", "gàngs_ter",
        "han_gar", "he_li_còp_ter", "hi_dro_car_bur", "hi_drur", "ho_nor", "hor_ror", "hu_mor", "hàms_ter", "hú_mer",
        "hús_sar", "im_po_pu_lar", "im_pu_dor", "im_pur", "im_pú_ber", "in_co_lor", "in_fe_ri_or", "in_su_lar", "in_te_ri_or",
        "ir_re_gu_lar", "jac_quard", "ja_guar", "ju_gu_lar", "jò_quer", "jú_ni_or", "jú_pi_ter", "la_bor", "la_mi_nar", "lar",
        "len_ti_cu_lar", "li_cor", "llar", "lum_bar", "lum_pur", "lu_nar", "lu_xor", "là_ser", "lí_der", "ma_da_gas_car",
        "ma_gi_ar", "ma_la_bar", "mar", "ma_xil_lar", "me_dul_lar", "men_hir", "me_nor", "me_te_or", "mi_li_tar", "mir",
        "mo_her", "mo_lar", "mo_le_cu_lar", "mo_no_mo_tor", "mo_no_pa_ren_tal", "mor", "mo_tor", "mu_dè_jar", "mur",
        "mus_cu_lar", "mà_na_ger", "màr_tir", "màs_ter", "mís_ter", "ne_nú_far", "nu_cle_ar", "nèc_tar", "ní_ger", "o_cu_lar",
        "or", "o_vu_lar", "pal_mar", "pan_to_crà_tor", "pa_pir", "par", "pa_ra_mi_li_tar", "par_ti_cu_lar", "pe_cu_li_ar",
        "pen_du_lar", "pe_nin_su_lar", "per", "per_pen_di_cu_lar", "pe_tro_dò_lar", "ple_na_mar", "po_lar", "po_li_ès_ter",
        "po_pu_lar", "pos_te_ri_or", "pre_es_co_lar", "pre_li_mi_nar", "pre_ma_tur", "pre_mo_lar", "prò_cer", "pul_mo_nar",
        "pul_lò_ver", "pur", "pò_quer", "pòr_tic", "pòs_ter", "pú_ber", "qa_tar", "qua_dran_gu_lar", "qua_dri_là_ter",
        "qua_dri_mo_tor", "que_fer", "ra_dar", "rec_tan_gu_lar", "re_gu_lar", "res_pir", "re_ti_cu_lar", "re_tir",
        "re_vòl_ver", "ri_gor", "ru_bor", "ru_mor", "sa_bor", "sa_fir", "se_cu_lar", "se_mà_for", "ser", "si_de_car",
        "si_mi_lar", "sin_ga_pur", "sin_gu_lar", "so_lar", "so_nar", "so_nor", "so_por", "sor", "sos_pir", "sul_fur",
        "su_pe_ri_or", "sà_tir", "sè_ni_or", "sú_per", "te_mor", "te_nor", "ter_mo_nu_cle_ar", "ter_ror", "tir", "ti_tu_lar",
        "tre_sor", "tri_an_gu_lar", "trài_ler", "tsar", "tu_bu_lar", "tu_mor", "tu_te_lar", "tò_ner", "ul_te_ri_or",
        "ul_tra_mar", "u_ni_cel_lu_lar", "u_ni_fa_mi_li_ar", "u_rè_ter", "u_vu_lar", "va_lor", "va_por", "vas_cu_lar",
        "ve_lar", "ve_lo_mo_tor", "ve_si_cu_lar", "vi_gor", "vi_sir", "vul_gar", "và_ter", "xàr_ter", "zan_zí_bar", "àl_ber",
        "è_ter", "és_ser", "òs_car", "ú_ter"];
    var s = this.toString().toLowerCase();
    s = s.replace(/^[lsdtmn]['’](.*)/i, '$1');
    return erraSensible.includes(s);
}


onChangeFunction(); //first time

function onChangeFunction() {
    var original_text = normalizeNFC(document.getElementById("text_to_hyphen").value.trim()).replace(/_/g, " ");
    var lc = original_text.lineCount();
    if (lc < 1) {
        document.getElementById("result").innerHTML = "";
	return;
    } else if ( lc == 1) {
        hyphenated_text = hyphenate(original_text).adjustHyphenatedText();
        document.getElementById("result").innerHTML = getMessageOneLine(hyphenated_text);
    } else if (lc > 1) {
        document.getElementById("result").innerHTML = getMessageMultipleLines(original_text);
    }

    // show warnings
    var hintStr = "";
    var ambiguities = checkAmbiguities(original_text)
    if (ambiguities.count >= 1) {
        hintStr = "<br/>";
        if (ambiguities.count == 1) {
            hintStr += "Partició sil·làbica dubtosa\n<ul>\n"
        } else hintStr += "Particions sil·làbiques dubtoses\n<ul>\n"
        hintStr += ambiguities.hints;
        hintStr += "</ul>\n"
        //hintStr+="<br/><br/>";
        document.getElementById("warning").innerHTML = hintStr;
        document.getElementById("warning").style.display = "inline";
    } else document.getElementById("warning").style.display = "none";
}


function getResultLine(s) {
    var ss = s.removeApostrophe();
    var words = ss.split(/[^a-zàáèéìíòóùúäëïöüç·ñ'’ŀâêîôû_-]/i);
    words = words.filter(String);
    console.log(words);
    wl = words.length;
    if (wl === 0) {
        return {
	        hyphenated_line: "",
	        count_graphical: 0,
	        count_phonetical: 0,
	        count_poetical1: 0,
	        count_poetical2: 0,
	        numwords: 0
	    }
    }
    var new_hyphen_text = "";
    var i;
    // compta amb sinalefes i elisions
    for (i = 0; i < wl - 1; i++) {
        new_hyphen_text += words[i].toString();
        if (
          //primera paraula
          ( 
             // erra final muda
            (words[i].match(/[aeiou]r$/i) && !words[i].isErraSensible())
            ||
            (words[i].match(/[aeiouàèìòùáéíóúïü]h?$/i) && !words[i].match(/([aeio][iu]|^[^q]*ui)$/i)))
          // segona paraula
          && words[i + 1].match(/^h?[aeiouàèìòùáéíóúïü]/i) && !words[i + 1].match(/^h?[iu][aeiouàèìòùáéíóúïü]/i)) {
            new_hyphen_text = new_hyphen_text.replace(/‿(h?[io])$/i, ' $1');
            new_hyphen_text += "‿";
        } else {
            new_hyphen_text += " ";
        }
        new_hyphen_text = new_hyphen_text.replace(/([_-][^aeiou]*[eiïa])(-h[io][ ‿])$/i, '$1‿$2');
        new_hyphen_text = new_hyphen_text.replace(/(‿-h[io])‿$/i, '$1 ');
    }
    new_hyphen_text += words[wl - 1].toString();
    new_hyphen_text = new_hyphen_text.replace(/([_-][^aeiou]*[eiïa])(-h[io])$/i, '$1‿$2');

    //última paraula
    lastword = words[wl - 1].toString();
    numlastword = classifyWord(lastword);

    // sinalefa després de l'última síl·laba tònica
    var sinalefa_final = 0;
    if (lastword.match(/([_-][^aeiou]*[eiïa])(-h[io])$/)) {
        sinalefa_final = 1;
    }

    syllables = ss.split(/[^a-zàáèéìíòóùúäëïöüç·ñ'’ŀâêîôû]/i);
    syllables2 = new_hyphen_text.replace(/‿-/, "‿").split(/[^a-zàáèéìíòóùúäëïöüç·ñ'’ŀâêîôû‿]/i);

    numsyl = syllables.filter(String).length;
    numsyl2 = syllables2.filter(String).length;

    return {
        hyphenated_line: new_hyphen_text.replace(/_/g, '|').replace(/ /g, "&nbsp; "),
        count_graphical: numsyl,
        count_phonetical: numsyl2,
        count_poetical1: numsyl + numlastword,
        count_poetical2: numsyl2 + numlastword + sinalefa_final,
        numwords: wl
    }
}

function getMessageOneLine(s) {
    var r = getResultLine(s);
    if (r.count_graphical === 0) {
        return "";
    }
    var result = "";
    result += r.hyphenated_line;
    result += "<br/><br/>";
    result += "Recompte gràfic: " + r.count_graphical + sillabes(r.count_graphical) + "<br/>";
    if (r.count_graphical != r.count_phonetical) {
        result += "Recompte fonètic: " + r.count_phonetical + sillabes(r.count_phonetical) + " (amb elisions i sinalefes)<br/>";
    }
    if (r.numwords > 1) {
	    result += "Recompte poètic: ";
	    result += (r.count_poetical1);
	    if (r.count_poetical1 != r.count_poetical2) {
	        result += " (" + r.count_poetical2 + ")";
	    }
	    result += sillabes(r.count_poetical1) + " (fins a l'última síl·laba tònica)<br/>";
    }
    return result;
}

function getMessageMultipleLines(s){
    var lines = s.lines().filter(String);
    var i = 0;
    var result = lines.length + " versos";
    result += '<table>\n';
    for (i = 0; i < lines.length; i++) {
        var r = getResultLine(hyphenate(lines[i]).adjustHyphenatedText());
        result += "<tr><td>";
        result += (r.count_poetical1);
        if (r.count_poetical1 != r.count_poetical2) {
            result += " (" + r.count_poetical2 + ")";
        }
        result += "</td><td>";
        result += r.hyphenated_line;
        result += "</td></tr>";
    }
    result += '</table>';
    return result;
}

function sillabes(i) {
    if (i === 1) {
        return " síl·laba";
    } else {
        return " síl·labes";
    }
}

function classifyWord(word) {
    var w = word.trim();
    //    if (!w.matches(/_/)) {
    //    w = hyphenate(w);
    //    }

    //elimina pronoms febles
    var syl_removed = 0;
    while (w.match(/(['’]en|['’]hi|['’]ho|['’]l|['’]ls|['’]m|['’]n|['’]ns|['’]s|['’]t|-el|-els|-em|-en|-ens|-hi|-ho|-l|-la|-les|-li|-lo|-los|-m|-me|-n|-ne|-nos|-s|-se|-t|-te|-us|-vos)$/)) {
        if (w.match(/(['’]en|['’]hi|['’]ho|-el|-els|-em|-en|-ens|-hi|-ho|-la|-les|-li|-lo|-los|-me|-ne|-nos|-se|-te|-us|-vos)$/)) {
            syl_removed++;
        }
        w = w.replace(/(['’]en|['’]hi|['’]ho|['’]l|['’]ls|['’]m|['’]n|['’]ns|['’]s|['’]t|-el|-els|-em|-en|-ens|-hi|-ho|-l|-la|-les|-li|-lo|-los|-m|-me|-n|-ne|-nos|-s|-se|-t|-te|-us|-vos)$/, "");
    }


    syll = w.split(/_/);
    ns = syll.filter(String).length;
    r = 0;
    if (ns === 0) {
        return 0;
    } //empty word
    else if (ns === 1) {
        r = 0;
    } // aguda
    else if (ns > 2 && syll[ns - 3].match(/[àáèéìíòóùú]/i)) {
        r = -2;
    } //esdrúixola
    else if (syll[ns - 2].match(/[àáèéìíòóùú]/i)) {
        r = -1;
    } //plana
    else if (syll[ns - 1].match(/[àáèéìíòóùú]/i)) {
        r = 0;
    } //aguda
    else if (syll[ns - 1].match(/[aeo][iu][^aeiou]*$/i)) {
        r = 0;
    } // aguda (amb diftong)
    else if (syll[ns - 1].match(/([eiï]n|[aeiouï]s?)$/i)) {
        r = -1;
    } //plana
    else r = 0; //aguda
    return r - syl_removed;
}

function normalizeNFC(userText) {
    var normalizedText = userText;
    if (String.prototype.hasOwnProperty('normalize')) {
        normalizedText = userText.normalize("NFC");
    }
    return normalizedText;
}

function checkAmbiguities(s) {
    var checks = [{
            re: "\\baguant\\b",
            cs: "i",
            state: 0,
            hint: "La partició del mot \"aguant\" pot ser \"a_gu_ant\" (del verb \"aguar\") o \"a_guant\" (substantiu o forma verbal del verb \"aguantar\")"
        },
        {
            re: "\\bpair\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"pair\" tant pot ser del verb \"pair\", i la partició seria \"pa_ir\", com del verb \"pairar\", i aleshores seria \"pair\"."
        },
        {
            re: "\\bduumvir[a-z]*\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix una partició diferent per a \"duumvir\" i derivats."
        },
        {
            re: "\\bpairem\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"pairem\" tant pot ser del verb \"pair\", i la partició seria \"pa_i_rem\", com del verb \"pairar\", i aleshores seria \"pai_rem\"."
        },
        {
            re: "\\bpaireu\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"paireu\" tant pot ser del verb \"pair\", i la partició seria \"pa_i_reu\", com del verb \"pairar\", i aleshores seria \"pai_reu\"."
        },
        {
            re: "\\bpairen\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"pairen\" tant pot ser del verb \"pair\", i la partició seria \"pa_i_ren\", com del verb \"pairar\", i aleshores seria \"pai_ren\"."
        },
        {
            re: "\\bpairàs\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"pairàs\" tant pot ser del verb \"pair\", i la partició seria \"pa_i_ràs\", com del verb \"pairar\", i aleshores seria \"pai_ràs\"."
        },
        {
            re: "\\bperiòdi(c|cs|ca|ques)\\b",
            cs: "i",
            state: 0,
            hint: "La partició del mot \"periòdic\" pot ser \"pe_ri_ò_dic\", de període, o \"per_iò_dic\", d'àcid periòdic."
        },
        {
            re: "\\b(Pius|PIUS)\\b",
            cs: "",
            state: 0,
            hint: "El nom propi \"Pius\" té la partició \"Pi_us\", però el substantiu plural \"pius\" és monosil·làbic."
        },
        //{re:"\\bshakespear(e|ià|ians|iana|ianes)\\b", cs:"i", state:0, hint:"L'Enciclopèdia Catalana suggereix una partició diferent per a \"Shakespeare\" i derivats."},
        {
            re: "\\btrair\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"trair\" tant pot ser del verb \"trair\", i la partició seria \"tra_ir\", com del verb \"trairar\", i aleshores seria \"trair\"."
        },
        {
            re: "\\btrairem\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"trairem\" tant pot ser del verb \"trair\", i la partició seria \"tra_i_rem\", com del verb \"trairar\", i aleshores seria \"trai_rem\"."
        },
        {
            re: "\\btraireu\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"traireu\" tant pot ser del verb \"trair\", i la partició seria \"tra_i_reu\", com del verb \"trairar\", i aleshores seria \"trai_reu\"."
        },
        {
            re: "\\btrairen\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"trairen\" tant pot ser del verb \"trair\", i la partició seria \"tra_i_ren\", com del verb \"trairar\", i aleshores seria \"trai_ren\"."
        },
        {
            re: "\\btrairàs\\b",
            cs: "i",
            state: 0,
            hint: "La forma verbal \"trairàs\" tant pot ser del verb \"trair\", i la partició seria \"tra_i_ràs\", com del verb \"trairar\", i aleshores seria \"trai_ràs\"."
        },
        {
            re: "\\bzimbabuès\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix zim_ba_buès."
        },
        {
            re: "\\bzimbabués\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix zim_ba_bués."
        },
        {
            re: "\\bzimbabuesa\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix zim_ba_bue_sa."
        },
        {
            re: "\\bzimbabueses\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix zim_ba_bue_ses."
        },
        {
            re: "\\bzimbabuesos\\b",
            cs: "i",
            state: 0,
            hint: "L'Enciclopèdia Catalana suggereix zim_ba_bue_sos."
        }
    ];

    var output = {
        count: 0,
        hints: ""
    };

    for (i = 0; i < checks.length; i++) {
        var re = new RegExp(checks[i].re, checks[i].cs);

        if (re.test(s)) {
            output.count += 1;
            output.hints += "<li>" + checks[i].hint + "</li>\n";
        }
    }

    return output;
}

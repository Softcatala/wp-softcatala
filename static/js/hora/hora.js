module.exports = {get_time_campanar, get_time_campanar_tradicional, get_time_rellotge, get_text_hora};

// Sistema campanar tradicional
function get_time_campanar_tradicional(hour, minute) {

    if (minute > 6)
        hour = hour + 1;

    if (minute == 0 || minute == 1) return get_article(hour) + " " + get_hour(hour) + " en punt";
    if (minute == 2 || minute == 3 || minute == 4) return get_article(hour) + " " + get_hour(hour) + " " + get_tocades(hour);
    if (minute == 5 || minute == 6) return get_article(hour) + " " + get_hour(hour) + " ben " + get_tocades(hour);
    if (minute == 7 || minute == 8) return "Mig quart " + get_de(hour) + get_hour(hour);
    if (minute == 9 || minute == 10 || minute == 11) return "Mig quart passat " + get_de(hour) + get_hour(hour);
    if (minute == 12 || minute == 13) return "Mig quart ben passat " + get_de(hour) + get_hour(hour);
    if (minute == 14 || minute == 15 || minute == 16) return "Un quart " + get_de(hour) + get_hour(hour);

    if (minute == 17 || minute == 18 || minute == 19) return "Un quart tocat " + get_de(hour) + get_hour(hour);
    if (minute == 20 || minute == 21) return "Un quart ben tocat " + get_de(hour) + get_hour(hour);
    if (minute == 22 || minute == 23) return "Un quart i mig " + get_de(hour) + get_hour(hour);
    if (minute == 24 || minute == 25 || minute == 26) return "Un quart i mig passat " + get_de(hour) + get_hour(hour);
    if (minute == 27 || minute == 28) return "Un quart i mig ben passat " + get_de(hour) + get_hour(hour);
    if (minute == 29 || minute == 30 || minute == 31) return "Dos quarts " + get_de(hour) + get_hour(hour);

    if (minute == 32 || minute == 33 || minute == 34) return "Dos quarts tocats " + get_de(hour) + get_hour(hour);
    if (minute == 35 || minute == 36) return "Dos quarts ben tocats " + get_de(hour) + get_hour(hour);
    if (minute == 37 || minute == 38) return "Dos quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 39 || minute == 40 || minute == 41) return "Dos quarts i mig passats " + get_de(hour) + get_hour(hour);
    if (minute == 42 || minute == 43) return "Dos quarts i mig ben passats " + get_de(hour) + get_hour(hour);
    if (minute == 44 || minute == 45 || minute == 46) return "Tres quarts " + get_de(hour) + get_hour(hour);

    if (minute == 47 || minute == 48 || minute == 49) return "Tres quarts tocats " + get_de(hour) + get_hour(hour);
    if (minute == 50 || minute == 51) return "Tres quarts ben tocats " + get_de(hour) + get_hour(hour);
    if (minute == 52 || minute == 53) return "Tres quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 54 || minute == 55 || minute == 56) return "Tres quarts i mig passats " + get_de(hour) + get_hour(hour);
    if (minute == 57 || minute == 58) return "Tres quarts i mig ben passats " + get_de(hour) + get_hour(hour);
    if (minute == 59) return get_article(hour) + " " + get_hour(hour)

    return "no ho sé";
}



// Sistema campanar tal com està definit a la Gramàtica Essencial de la llengua catalana
// https://geiec.iec.cat/capitol_veure.asp?id_gelc=337&capitol=28
function get_time_campanar(hour, minute) {

    if (minute > 14 || minute == 7 || minute == 8)
        hour = hour + 1;

    if (minute == 0) return get_article(hour) + " " + get_hour(hour) + " en punt";
    if (minute == 1) return get_article(hour) + " " + get_hour(hour) + " i un (minut)";
    if (minute == 2) return get_article(hour) + " " + get_hour(hour) + " i dos (minuts)";
    if (minute == 3) return get_article(hour) + " " + get_hour(hour) + " i tres (minuts)";
    if (minute == 4) return get_article(hour) + " " + get_hour(hour) + " i quatre (minuts)";
    if (minute == 5) return get_article(hour) + " " + get_hour(hour) + " i cinc (minuts)";
    if (minute == 6) return get_article(hour) + " " + get_hour(hour) + " i sis (minuts)";
    if (minute == 7) return "Mig quart " + get_de(hour) + get_hour(hour);
    if (minute == 8) return "Mig quart " + get_de(hour) + get_hour(hour);
    if (minute == 9) return  get_article(hour) + " " + get_hour(hour) + " i nou (minuts)";
    if (minute == 10) return get_article(hour) + " " + get_hour(hour) + " i deu (minuts)";
    if (minute == 11) return get_article(hour) + " " + get_hour(hour) + " i onze (minuts)";
    if (minute == 12) return get_article(hour) + " " + get_hour(hour) + " i dotze (minuts)";
    if (minute == 13) return get_article(hour) + " " + get_hour(hour) + " i tretze (minuts)";
    if (minute == 14) return get_article(hour) + " " + get_hour(hour) + " i catorze (minuts)";
    if (minute == 15) return "Un quart " + get_de(hour) + get_hour(hour);

    if (minute == 16) return "Un quart i un (minut) " + get_de(hour) + get_hour(hour);
    if (minute == 17) return "Un quart i dos (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 18) return "Un quart i tres (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 19) return "Un quart i quatre (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 20) return "Un quart i cinc (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 21) return "Un quart i sis (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 22) return "Un quart i mig " + get_de(hour) + get_hour(hour);
    if (minute == 23) return "Un quart i mig " + get_de(hour) + get_hour(hour);
    if (minute == 24) return "Un quart i nou (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 25) return "Un quart i deu (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 26) return "Un quart i onze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 27) return "Un quart i dotze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 28) return "Un quart i tretze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 29) return "Un quart i catorze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 30) return "Dos quarts " + get_de(hour) + get_hour(hour);

    if (minute == 31) return "Dos quarts i un (minut) " + get_de(hour) + get_hour(hour);
    if (minute == 32) return "Dos quarts i dos (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 33) return "Dos quarts i tres (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 34) return "Dos quarts i quatre (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 35) return "Dos quarts i cinc (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 36) return "Dos quarts i sis (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 37) return "Dos quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 38) return "Dos quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 39) return "Dos quarts i nou (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 40) return "Dos quarts i deu (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 41) return "Dos quarts i onze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 42) return "Dos quarts i dotze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 43) return "Dos quarts i tretze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 44) return "Dos quarts i catorze (minuts) " + get_de(hour) + get_hour(hour);

    if (minute == 45) return "Tres quarts " + get_de(hour) + get_hour(hour);
    if (minute == 46) return "Tres quarts i un (minut) " + get_de(hour) + get_hour(hour);
    if (minute == 47) return "Tres quarts i dos (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 48) return "Tres quarts i tres (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 49) return "Tres quarts i quatre (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 50) return "Tres quarts i cinc (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 51) return "Tres quarts i sis (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 52) return "Tres quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 53) return "Tres quarts i mig " + get_de(hour) + get_hour(hour);
    if (minute == 54) return "Tres quarts i nou (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 55) return "Tres quarts i deu (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 56) return "Tres quarts i onze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 57) return "Tres quarts i dotze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 58) return "Tres quarts i tretze (minuts) " + get_de(hour) + get_hour(hour);
    if (minute == 59) return "Tres quarts i catorze (minuts) " + get_de(hour) + get_hour(hour);

    return "no ho sé";
}

function get_time_rellotge(hour, minute) {

    if (minute > 30)
        hour = hour + 1;


    if (minute == 0) return get_article(hour) + " " + get_hour(hour) + " en punt";
    if (minute == 1) return get_article(hour) + " " + get_hour(hour) + " i un (minut)";
    if (minute == 2) return get_article(hour) + " " + get_hour(hour) + " i dos (minuts)";
    if (minute == 3) return get_article(hour) + " " + get_hour(hour) + " i tres (minuts)";
    if (minute == 4) return get_article(hour) + " " + get_hour(hour) + " i quatre (minuts)";
    if (minute == 5) return get_article(hour) + " " + get_hour(hour) + " i cinc (minuts)";
    if (minute == 6) return get_article(hour) + " " + get_hour(hour) + " i sis (minuts)";
    if (minute == 7) return get_article(hour) + " " + get_hour(hour) + " i set (minuts)";
    if (minute == 8) return get_article(hour) + " " + get_hour(hour) + " i vuit (minuts)";
    if (minute == 9) return  get_article(hour) + " " + get_hour(hour) + " i nou (minuts)";
    if (minute == 10) return get_article(hour) + " " + get_hour(hour) + " i deu (minuts)";
    if (minute == 11) return get_article(hour) + " " + get_hour(hour) + " i onze (minuts)";
    if (minute == 12) return get_article(hour) + " " + get_hour(hour) + " i dotze (minuts)";
    if (minute == 13) return get_article(hour) + " " + get_hour(hour) + " i tretze (minuts)";
    if (minute == 14) return get_article(hour) + " " + get_hour(hour) + " i catorze (minuts)";
    if (minute == 15) return get_article(hour) + " " + get_hour(hour) + " i quart";

    if (minute == 16) return get_article(hour) + " " + get_hour(hour) + " i setze (minuts)";
    if (minute == 17) return get_article(hour) + " " + get_hour(hour) + " i disset (minuts)";
    if (minute == 18) return get_article(hour) + " " + get_hour(hour) + " i divuit (minuts)";
    if (minute == 19) return get_article(hour) + " " + get_hour(hour) + " i dinou (minuts)";
    if (minute == 20) return get_article(hour) + " " + get_hour(hour) + " i vint (minuts)";
    if (minute == 21) return get_article(hour) + " " + get_hour(hour) + " i vint-i-un (minuts)";
    if (minute == 22) return get_article(hour) + " " + get_hour(hour) + " i vint-i-dos (minuts)";
    if (minute == 23) return get_article(hour) + " " + get_hour(hour) + " i vint-i-tres (minuts)";
    if (minute == 24) return  get_article(hour) + " " + get_hour(hour) + " i vint-i-quatre (minuts)";
    if (minute == 25) return get_article(hour) + " " + get_hour(hour) + " i vint-i-cinc (minuts)";
    if (minute == 26) return get_article(hour) + " " + get_hour(hour) + " i vint-i-sis (minuts)";
    if (minute == 27) return get_article(hour) + " " + get_hour(hour) + " i vint-i-set (minuts)";
    if (minute == 28) return get_article(hour) + " " + get_hour(hour) + " i vint-i-vuit (minuts)";
    if (minute == 29) return get_article(hour) + " " + get_hour(hour) + " i vint-i-nou (minuts)";
    if (minute == 30) return get_article(hour) + " " + get_hour(hour) + " i mitja";

    if (minute == 31) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-nou (minuts)";
    if (minute == 32) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-vuit (minuts)";
    if (minute == 33) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-set (minuts)";
    if (minute == 34) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-sis (minuts)";
    if (minute == 35) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-cinc (minuts)";
    if (minute == 36) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-quatre (minuts)";
    if (minute == 37) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-tres (minuts)";
    if (minute == 38) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-dos (minuts)";
    if (minute == 39) return get_article(hour) + " " + get_hour(hour) + " menys vint-i-un (minuts)";
    if (minute == 40) return get_article(hour) + " " + get_hour(hour) + " menys vint (minuts)";
    if (minute == 41) return get_article(hour) + " " + get_hour(hour) + " menys dinou (minuts)";
    if (minute == 42) return get_article(hour) + " " + get_hour(hour) + " menys divuit (minuts)";
    if (minute == 43) return get_article(hour) + " " + get_hour(hour) + " menys disset (minuts)";
    if (minute == 44) return get_article(hour) + " " + get_hour(hour) + " menys setze (minuts)";
    if (minute == 45) return get_article(hour) + " " + get_hour(hour) + " menys quart";

    if (minute == 46) return get_article(hour) + " " + get_hour(hour) + " menys catorze (minuts)";
    if (minute == 47) return get_article(hour) + " " + get_hour(hour) + " menys tretze (minuts)";
    if (minute == 48) return get_article(hour) + " " + get_hour(hour) + " menys dotze (minuts)";
    if (minute == 49) return get_article(hour) + " " + get_hour(hour) + " menys onze (minuts)";
    if (minute == 50) return get_article(hour) + " " + get_hour(hour) + " menys deu (minuts)";
    if (minute == 51) return get_article(hour) + " " + get_hour(hour) + " menys nou (minuts)";
    if (minute == 52) return get_article(hour) + " " + get_hour(hour) + " menys vuit (minuts)";
    if (minute == 53) return get_article(hour) + " " + get_hour(hour) + " menys set (minuts)";
    if (minute == 54) return get_article(hour) + " " + get_hour(hour) + " menys sis (minuts)";
    if (minute == 55) return get_article(hour) + " " + get_hour(hour) + " menys cinc (minuts)";
    if (minute == 56) return get_article(hour) + " " + get_hour(hour) + " menys quatre (minuts)";
    if (minute == 57) return get_article(hour) + " " + get_hour(hour) + " menys tres (minuts)";
    if (minute == 58) return get_article(hour) + " " + get_hour(hour) + " menys dos (minuts)";
    if (minute == 59) return get_article(hour) + " " + get_hour(hour) + " menys un (minut)";


    return "no ho sé";
}


function get_de(hour) {

    if (hour == 1 || hour == 13 ||
        hour == 11 || hour == 23)
        return "d'";

    return "de ";
}


function get_article(hour) {

    if (hour == 1 || hour == 13)
        return "La";

    return "Les";
}


function get_tocades(hour) {

    if (hour == 1 || hour == 13)
        return "tocada";

    return "tocades";
}

function get_article_lower(hour) {

    var s = get_article(hour);
    return s.charAt(0).toLowerCase() + s.slice(1)
}


function get_hour(hour) {

    if (hour == 1 || hour == 13) return "una";
    if (hour == 2 || hour == 14) return "dues";
    if (hour == 3 || hour == 15) return "tres";
    if (hour == 4 || hour == 16) return "quatre";
    if (hour == 5 || hour == 17) return "cinc";
    if (hour == 6 || hour == 18) return "sis";
    if (hour == 7 || hour == 19) return "set";
    if (hour == 8 || hour == 20) return "vuit";
    if (hour == 9 || hour == 21) return "nou";
    if (hour == 10 || hour == 22) return "deu";
    if (hour == 11 || hour == 23) return "onze";
    if (hour == 12 || hour == 24 || hour == 0) return "dotze"

    return "no sé noi"
}

function get_text_hora(hours, minutes, seconds) {
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    return hours + ":" + minutes + ":" + seconds;
}


function get_all_times(hours, min, sistema) {

    var list = "";
    for (min = 0; min <= 59; min++) {
        if (min < 10) {
            var m = "0" + min;
        }
        else {
            var m = min;
        }

        var text;

        if (sistema == 'campanar')
            text = get_time_campanar(hours, min);

        if (sistema == 'campanar_tradicional')
            text = get_time_campanar_tradicional(hours, min);
        else
            text = get_time_rellotge(hours, min);

        list += hours + ":" + m + " > " + text  + "<br>";
    }
    return list;
}

function show_timer() {

    var today = new Date();
    var hours = today.getHours();
    var minutes = today.getMinutes();
    var seconds = today.getSeconds();
    show_hour(hours, minutes, seconds, 'hora');
}

function show_hour(hours, minutes, seconds, div) {

    var text = get_time_campanar(hours, minutes);
    var element = document.getElementById(div);

    if (div == 'hora'){
        element.innerHTML = get_text_hora(hours, minutes, seconds);
        document.getElementById('horaconsulta').style.display = 'none'; 
        document.getElementById('hora').style.display = 'block'; 

    }else{
        element.innerHTML = 'Heu introduit:<b> ' + get_text_hora(hours, minutes, seconds) + '</b>';
        document.getElementById('hora').style.display = 'none'; 
        document.getElementById('horaconsulta').style.display = 'block';
    }

    element = document.getElementById("text_campanar");
    element.innerHTML = text;
    //element.innerHTML += "<p>Depuració</p>" + get_all_times(hours, minutes, 'campanar');

    text = get_time_rellotge(hours, minutes);
    element = document.getElementById("text_rellotge");
    element.innerHTML = text;
    //element.innerHTML += "<p>Depuració</p>" + get_all_times(hours, minutes, 'rellotge');

    text = get_time_campanar_tradicional(hours, minutes);
    element = document.getElementById("text_campanar_tradicional");
    element.innerHTML = text;
    //element.innerHTML += "<p>Depuració</p>" + get_all_times(hours, minutes, 'campanar_tradicional');
}


function show_requested_time(text_hours, text_minutes) {

    var hours = parseInt(text_hours);
    var minutes = parseInt(text_minutes);

    if ((isNaN(hours) || hours < 0 || hours > 23) || (isNaN(minutes) || minutes < 0  || minutes > 59))
    {
        return;
    }
    stop_timer();
    show_hour(hours, minutes, 0, 'horaconsulta');
}

var time_timer_id = null;

function speak_text(id, element) {

    text = document.getElementById(id).innerHTML;
    hash = md5(text).substring(0, 8);
    
    document.getElementById(element).disabled = true;
    url = `https://www.softcatala.org/veu/speak/?text=${text}&token=${hash}`;
    aud = new Audio(url)

    aud.onended = function() {
        document.getElementById(element).disabled = false;
    }; 

    aud.play();

}

function start_timer() {
    show_timer();
    
    time_timer_id = setInterval(function() {
            show_timer();
         }, 1000);
}

function stop_timer() {
    clearInterval(time_timer_id);
    time_timer_id = null;
}


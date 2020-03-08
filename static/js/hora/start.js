




start_timer();
start_clock();
document.getElementById('current_time').disabled = current_time_btn_disabled;

var urlParams = new URLSearchParams(window.location.search);
if (urlParams.has('hora')){
    arr = urlParams.get('hora').split(':');
    hora = parseInt(arr[0]);
    min = parseInt(arr[1]);
    set_clock(hora, min);
    show_requested_time(hora, min); 
}







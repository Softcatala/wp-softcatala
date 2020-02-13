
function drawClock(ctx, radius, hour, minute, second) {
  drawFace(ctx, radius);
  drawNumbers(ctx, radius);
  drawTime(ctx, radius, hour, minute, second
);
}

function drawClockCurrentTime(ctx, radius) {
  var now = new Date();
  var hour = now.getHours();
  var minute = now.getMinutes();
  var second = now.getSeconds();
  drawClock(ctx, radius, hour, minute, second);
}

function drawFace(ctx, radius) {
    var grad;
    ctx.beginPath();
    ctx.arc(0, 0, radius, 0, 2*Math.PI);
    ctx.fillStyle = 'white';
    ctx.fill();
    grad = ctx.createRadialGradient(0,0,radius*0.95, 0,0,radius*1.05);
    grad.addColorStop(0, '#333');
    grad.addColorStop(0.5, 'white');
    grad.addColorStop(1, '#333');
    ctx.strokeStyle = grad;
    ctx.lineWidth = radius*0.1;
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(0, 0, radius*0.1, 0, 2*Math.PI);
    ctx.fillStyle = '#333';
    ctx.fill();
}

function drawNumbers(ctx, radius) {
    var ang;
    var num;
    ctx.font = radius*0.15 + "px arial";
    ctx.textBaseline="middle";
    ctx.textAlign="center";
    for(num = 1; num < 13; num++){
        ang = num * Math.PI / 6;
        ctx.rotate(ang);
        ctx.translate(0, -radius*0.85);
        ctx.rotate(-ang);
        ctx.fillText(num.toString(), 0, 0);
        ctx.rotate(ang);
        ctx.translate(0, radius*0.85);
        ctx.rotate(-ang);
    }
}

function drawTime(ctx, radius, hour, minute, second){
    //hour
    hour=hour%12;
    hour=(hour*Math.PI/6)+
    (minute*Math.PI/(6*60))+
    (second*Math.PI/(360*60));
    drawHand(ctx, hour, radius*0.5, radius*0.07);
    //minute
    minute=(minute*Math.PI/30)+(second*Math.PI/(30*60));
    drawHand(ctx, minute, radius*0.8, radius*0.07);
    // second
    second=(second*Math.PI/30);
    drawHand(ctx, second, radius*0.9, radius*0.02);
}

function drawHand(ctx, pos, length, width) {
    ctx.beginPath();
    ctx.lineWidth = width;
    ctx.lineCap = "round";
    ctx.moveTo(0,0);
    ctx.rotate(pos);
    ctx.lineTo(0, -length);
    ctx.stroke();
    ctx.rotate(-pos);
}

var clock_timer_id = null;
var current_time_btn_disabled = true;
var ctx;
var radius;

function start_clock() {

    if (typeof ctx === 'undefined') {
        var canvas = document.getElementById("canvas");
        ctx = canvas.getContext("2d");
        radius = canvas.height / 2;
        ctx.translate(radius, radius);
        radius = radius * 0.90;
    }

    drawClockCurrentTime(ctx, radius);
    current_time_btn_disabled = true;

    clock_timer_id = setInterval(function() {

        drawClockCurrentTime(ctx, radius);

    }, 1000);
}

function set_clock(text_hours, text_minutes) {

    var hours = parseInt(text_hours);
    var minutes = parseInt(text_minutes);

    if ((isNaN(hours) || hours < 0 || hours > 23) || (isNaN(minutes) || minutes < 0  || minutes > 59))
    {
        return;
    }

    stop_clock();
    drawClock(ctx, radius, hours, minutes, 0);
}

function stop_clock() {
    clearInterval(clock_timer_id);
    clock_timer_id = null;
    current_time_btn_disabled = false;
}




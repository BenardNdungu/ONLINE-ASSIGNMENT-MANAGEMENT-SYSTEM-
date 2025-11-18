// Minimal JS: deadline countdown helper (call startCountdown with selector and ISO date)
function startCountdown(selector, dueDateStr) {
  var el = document.querySelector(selector);
  if (!el) return;
  var due = new Date(dueDateStr).getTime();
  var t = setInterval(function(){
    var now = Date.now();
    var diff = due - now;
    if (diff <= 0) { el.textContent = 'Deadline passed'; clearInterval(t); return; }
    var days = Math.floor(diff / (1000*60*60*24));
    var hrs = Math.floor((diff%(1000*60*60*24))/(1000*60*60));
    var mins = Math.floor((diff%(1000*60*60))/(1000*60));
    el.textContent = days + 'd ' + hrs + 'h ' + mins + 'm';
  }, 60*1000);
}

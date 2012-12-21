

$(document).ready(function() {
    $('.likert input').change(function() {
      $('.nav .next').trigger('click');
    });

    $('.likert.none').click(function() {
      $('.nav .next').trigger('click');
    });

    $('.nav .back').click(function() {
      $('form').stop().animate({
            scrollLeft: $('form').scrollLeft() - 322
        }, 500);
    });

    $('.nav .next').click(function() {
      var count = Math.floor($('form').scrollLeft() / 322) + 1;
      var item = ($('#form>div:nth-child(' + count + ')'));
      if (item.hasClass('none') || item.find('input:checked').length) {
        var dec = 322 - $('form').scrollLeft() % 322;
        dec = dec > 0 ? dec : 322;
        $('form').stop().animate({
              scrollLeft: $('form').scrollLeft() + dec
        }, 500);
      } else {
        item.addClass('needed');
      }
    });

});




// http://paulirish.com/2011/requestanimationframe-for-smart-animating/
// http://my.opera.com/emoller/blog/2011/12/20/requestanimationframe-for-smart-er-animating

// requestAnimationFrame polyfill by Erik Möller
// fixes from Paul Irish and Tino Zijdel

(function() {
    var lastTime = 0;
    var vendors = ['ms', 'moz', 'webkit', 'o'];
    for(var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
        window.requestAnimationFrame = window[vendors[x]+'RequestAnimationFrame'];
        window.cancelAnimationFrame = window[vendors[x]+'CancelAnimationFrame']
                                   || window[vendors[x]+'CancelRequestAnimationFrame'];
    }

    if (!window.requestAnimationFrame)
        window.requestAnimationFrame = function(callback, element) {
            var currTime = new Date().getTime();
            var timeToCall = Math.max(0, 16 - (currTime - lastTime));
            var id = window.setTimeout(function() { callback(currTime + timeToCall); },
              timeToCall);
            lastTime = currTime + timeToCall;
            return id;
        };

    if (!window.cancelAnimationFrame)
        window.cancelAnimationFrame = function(id) {
            clearTimeout(id);
        };
}());

var final;
var rate = 10;
var duration = 10;
var sL = 0;
function scroll() {
    if (sL < (final - rate)) {
      requestAnimationFrame(scroll);
      sL = sL + rate;
    } else {
      sL = final;
    }
    window.document.getElementById("likertForm").scrollLeft = sL;
}

function scrollLeft(speed = 100) {
    var left = window.document.getElementById("likertForm").scrollLeft;
    var count = left - (left % 322);
    final = ((count + 1) * 322);
    rate = (final - left) / duration;
    window.document.getElementById("likertForm").scrollLeft
    scroll();
}
;

window.onload = scrollLeft;

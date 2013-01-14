
$(document).ready(function() {
    $('select').change(function() {
      $('.nav .next').trigger('click');
    });

    $('input').change(function() {
      $('.nav .next').trigger('click');
    });

    $('.likert.none').click(function() {
      $('.nav .next').trigger('click');
    });

    $('.nav .back').click(function() {
      var dec = $('form').scrollLeft() % 302;
      dec = dec > 0 ? dec : 302;
      $('form').stop().animate({
            scrollLeft: $('form').scrollLeft() - dec
        }, 500);
    });

    $('.nav .last').click(function() {
      var count = 0;
      var stop = false;
      var lastWasIntro = false;
      $('#form>div').each(function(){
        var item = ($(this));
        if (!stop && (
          (item.hasClass('none')
            || (!item.find('.required').length)
            || item.find('input:checked').length
            || (item.find('input[type=text]').length && item.find('input').val() != "")
            || (item.find('input[type=number]').length && item.find('input').val() != "")
            || (item.find('select').length && item.find('select').val() != "")
          )
        )) {
          count = count + 1;
          lastWasIntro = item.hasClass('none');
        } else {
          stop = true;
        };
      });
      if (lastWasIntro) count = count - 1;
      $('form').stop().animate({
            scrollLeft: count * 302
      }, 500);
    });

    $('.nav .next').click(function() {
      var count = Math.floor($('form').scrollLeft() / 302) + 1;
      var item = ($('#form>div:nth-child(' + count + ')'));
      // TODO : correct this test for last page !
      if (item.hasClass('none')
        || (!item.find('.required').length)
        || item.find('input:checked').length
        || (item.find('input[type=text]').length && item.find('input').val() != "")
        || (item.find('input[type=number]').length && item.find('input').val() != "")
        || (item.find('select').length && item.find('select').val() != "")
      ) {
        var dec = 302 - $('form').scrollLeft() % 302;
        dec = dec > 0 ? dec : 302;
        $('form').stop().animate({
              scrollLeft: $('form').scrollLeft() + dec
        }, 500);
        item.removeClass('needed');
      } else {
        item.addClass('needed');
      }
    });

    $('.nav .last').trigger('click');

});




// http://paulirish.com/2011/requestanimationframe-for-smart-animating/
// http://my.opera.com/emoller/blog/2011/12/20/requestanimationframe-for-smart-er-animating

// requestAnimationFrame polyfill by Erik Möller
// fixes from Paul Irish and Tino Zijdel

/*
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
*/


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
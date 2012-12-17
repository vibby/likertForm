
$(document).ready(function() {
    $('.likert input').change(function(event) {
      console.log($('form').scrollLeft());
      $('form').stop().animate({
            scrollLeft: $('form').scrollLeft() + 322
        }, 500);
        event.preventDefault();
    });

    $('.nav .back').click(function() {
      $('form').stop().animate({
            scrollLeft: $('form').scrollLeft() - 322
        }, 500);
        event.preventDefault();
    });

    $('.nav .next').click(function() {
      $('form').stop().animate({
            scrollLeft: $('form').scrollLeft() + 322
        }, 500);
        event.preventDefault();
    });
});
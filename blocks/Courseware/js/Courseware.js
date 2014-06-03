define(['assets/js/block_types', './student_view'], function (block_types, StudentView) {

    'use strict';

    return block_types.add({
        name: 'Courseware',
        views: {
            student: StudentView
        }
    });
});

/*
$(window).scroll(function(e) {
    var scroller_anchor = $(".scroller_anchor").offset().top;

    if ($(this).scrollTop() >= (scroller_anchor - 24) )
    {
        $('section#courseware .fixed_navigation').slideDown('fast');
        $('.scroller_anchor').css('height', '39px');
    }
    else if ($(this).scrollTop() < (scroller_anchor - 24))
    {
        $('.scroller_anchor').css('height', '0px');
        $('section#courseware .fixed_navigation').slideUp('fast');
    }
});
*/

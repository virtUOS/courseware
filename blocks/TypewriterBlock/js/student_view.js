import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {},

    initialize() {},

    render() {
        return this;
    },

    postRender() {
        var $view = this;
        var typing = false;

        if (($view.isScrolledIntoView($view.$el))&&(!typing)) {
                $view.type();
                typing = true;
        }

        $(window).on('scroll', function() {
            if (($view.isScrolledIntoView($view.$el))&&(!typing)) {
                $view.type();
                typing = true;
            }
        });

        return this;
    },

    type() {
        var txt  = this.$('.cw-typewriter-stored-content').val();
        var spans = '<span>' + txt.split('').join('</span><span>') + '</span>';
        $(spans).hide().appendTo(this.$('.cw-typewriter')).each(function (i) {
            $(this).delay(100 * i).css({
                display: 'inline',
                opacity: 0
            }).animate({
                opacity: 1
            }, 100);
        });
    },

    isScrolledIntoView($element)
    {
        var docViewTop = $(window).scrollTop();
        var docViewBottom = docViewTop + $(window).height();

        var elemTop = $element.offset().top;
        var elemBottom = elemTop + $element.height();

        return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
    }

});

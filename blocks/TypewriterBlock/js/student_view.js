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
        var typewriter  = this.$('.cw-typewriter-stored-json').val();
        if (typewriter == '') {
            return;
        }
        typewriter = JSON.parse(typewriter);
        this.$('.cw-typewriter').addClass(typewriter.font).addClass(typewriter.size);
        var spans = '<span>' + typewriter.content.split('').join('</span><span>') + '</span>';
        var speed = [200,100,50,25];
        $(spans).hide().appendTo(this.$('.cw-typewriter')).each(function (i) {
            $(this).delay(speed[typewriter.speed] * i).css({
                display: 'inline',
                opacity: 0
            }).animate({
                opacity: 1
            }, speed[typewriter.speed]);
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

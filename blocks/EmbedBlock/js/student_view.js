import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {},

    initialize() { 
        $(window).on('resize.resizeview', this.onResize.bind(this));
    },

    render() {
        return this;
    },

    postRender() {
        
        $(window).trigger('resize');
    },

    onResize() {
        var $view = this,
            width = $view.$el.width(),
            iframe = $view.$('iframe'),
            img = $view.$('img.cw-embedblock-image'),
            new_height = 0;
        if (iframe.length != 0) {
            var iframe_width = iframe.attr('width');
            var iframe_height = iframe.attr('height');
            if (isNaN(iframe_height) || isNaN(iframe_width)) {
                iframe.attr('height', Math.round(width/1.65));
                iframe.css('height', Math.round(width/1.65));
            } else {
                new_height = (iframe_height / iframe_width) * width;
                iframe.attr('height', new_height);
                iframe.css('height', new_height);
            }
            iframe.attr('width', width);
            iframe.css('width', width);
        }

        if (img.length != 0) {
            var img_width = img.attr('data-originalwidth'),
                img_height = img.attr('data-originalheight');
            if (img_width > width) {
                new_height = (img_height / img_width) * width;
                img.attr('width', width+'px');
                img.attr('height', new_height+'px');
            }
        }
    }

});

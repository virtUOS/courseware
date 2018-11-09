import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
    },

    initialize() { },
    
    render() {
    return this;
    },

    postRender() {
        var $view = this;
        if ($('section#courseware').hasClass('scrollyware')) {
            try {
                var block_styles = JSON.parse(this.$('.cw-scrolly-stored-block-style').val());

                $.each(block_styles, function(){
                    switch ((this).style) {
                        case 'full':
                        case 'big':
                        case 'medium':
                            $('#block-'+(this).blockid).addClass('cw-scrolly-'+(this).style);
                            break;
                        case 'left-small':
                        case 'left-big':
                        case 'left-medium':
                        case 'right-small':
                        case 'right-big':
                        case 'right-medium':
                            var element = $('#block-'+(this).blockid);
                            var position = element.prev().position();
                            $('#block-'+(this).blockid).addClass('cw-scrolly-'+(this).style).css('top', position.top);
                            break;
                        case 'default':
                            break;
                    }
                });
            } catch (err) {
                //console.log('no scrolly: ' + err);
            }
            $(window).trigger('resize');
        }
    }
});

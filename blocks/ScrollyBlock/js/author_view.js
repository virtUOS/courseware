import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        var view = this;
        try {
            var block_styles = JSON.parse(this.$('.cw-scrolly-stored-block-style').val());
            $.each(block_styles, function(){
                let select = view.$('.cw-scrolly-block-style[data-blockid="'+(this).blockid+'"] option[value="'+(this).style+'"]');
                select.prop('selected', true);
                if ((this).bigletter) {
                    let input = view.$('.cw-scrolly-big-letter[data-blockid="'+(this).blockid+'"]');
                    input.prop('checked', true);
                }
            });
        } catch(error) {
            
        }
    },

    onNavigate(event) {
        if (!$('section .block-content button[name=save]').length) {
            return;
        }
        if(event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },
    
    onModeSwitch(toView, event) {
        if (toView != 'student') {
            return;
        }
        // the user already switched back (i.e. the is not visible)
        if (!this.$el.is(':visible')) {
            return;
        }
        // another listener already handled the user's feedback
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
    },
    
    onSave(event) {
        var $view = this;
        var $scrolly_block_style = [];
        var $blocks = this.$('.cw-scrolly-blocks');
        $.each($blocks , function(){
            let block_style = {};
            block_style.blockid = $(this).attr('data-blockid');
            block_style.style = $(this).find('.cw-scrolly-block-style').val();
            block_style.bigletter = $(this).find('.cw-scrolly-big-letter').prop('checked');
            $scrolly_block_style.push(block_style);
        });
        $scrolly_block_style = JSON.stringify($scrolly_block_style);

        helper
        .callHandler(this.model.id, 'save', {
            scrolly_block_style: $scrolly_block_style
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                $view.switchBack();
            },
            // error
            function (error) {
                var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
                alert(errorMessage);
                console.log(errorMessage, arguments);
            }
        );
    }
});
    

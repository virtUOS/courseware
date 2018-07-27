import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'click button[name=addcard]': 'addCard'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        return this;
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
        var $cards = $view.$('.cw-dialogcards-card-content');
        var dialogcards_content = [];
        $.each($cards, function(index){
            var card_content = {};
            card_content.front_img = $(this).find(".cw-dialogcards-front-img").val();
            card_content.front_text = $(this).find(".cw-dialogcards-front-text").val();
            card_content.back_img = $(this).find(".cw-dialogcards-back-img").val();
            card_content.back_text = $(this).find(".cw-dialogcards-back-text").val();
            card_content.index = index;
            dialogcards_content.push(card_content);
        });
        dialogcards_content = JSON.stringify(dialogcards_content);
        helper
        .callHandler(this.model.id, 'save', {
              dialogcards_content : dialogcards_content
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                $view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    },

    addCard() {
        var $view = this;
        var $card = $view.$('.cw-dialogcards-card-content-default').clone();
        var index = $view.$('.cw-dialogcards-card-content').length;
        var $last = $view.$('.cw-dialogcards-card-content').last();
        $card.removeClass('cw-dialogcards-card-content-default').addClass('cw-dialogcards-card-content');
        $card.find('.cw-dialogcards-card-content-legend').html('Karte '+index);
        $card.insertAfter($last);
    }
});

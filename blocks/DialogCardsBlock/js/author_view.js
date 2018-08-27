import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'click button[name=addcard]': 'addCard',
        'click button[name=removecard]': 'removeCard',
        'click input.cw-dialogcards-url': 'toggleSource'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        var $cards = this.$('.cw-dialogcards-card-content');
        $.each($cards, function(index) {
            if ($(this).find('.cw-dialogcards-front-url').prop('checked')) {
                $(this).find('.cw-dialogcards-front-img').show();
            } else {
                $(this).find('.cw-dialogcards-front-img-file').show();
                let file_id = $(this).find('.cw-dialogcards-front-img-file-stored').val();
                $(this).find('.cw-dialogcards-front-img-file option[file_id="'+file_id+'"]').prop('selected', true);
            }
            if ($(this).find('.cw-dialogcards-back-url').prop('checked')) {
                $(this).find('.cw-dialogcards-back-img').show();
            } else {
                $(this).find('.cw-dialogcards-back-img-file').show();
                let file_id = $(this).find('.cw-dialogcards-back-img-file-stored').val();
                $(this).find('.cw-dialogcards-back-img-file option[file_id="'+file_id+'"]').prop('selected', true);
            }
        });
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
            if ($(this).find('.cw-dialogcards-front-url').prop('checked')) {
                card_content.front_img = $(this).find('.cw-dialogcards-front-img').val();
                card_content.front_external_file = true;
            } else {
                card_content.front_img_file_id = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_id');
                card_content.front_img_file_name = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_name');
                card_content.front_img = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_url');
                card_content.front_external_file = false;
            }
            card_content.front_text = $(this).find('.cw-dialogcards-front-text').val();
            
            if ($(this).find('.cw-dialogcards-back-url').prop('checked')) {
                card_content.back_img = $(this).find('.cw-dialogcards-back-img').val();
                card_content.back_external_file = true;
            } else {
                card_content.back_img_file_id = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_id');
                card_content.back_img_file_name = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_name');
                card_content.back_img = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_url');
                card_content.back_external_file = false;
            }
            card_content.back_text = $(this).find('.cw-dialogcards-back-text').val();
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
        console.log($last);
        $card.removeClass('cw-dialogcards-card-content-default').addClass('cw-dialogcards-card-content');
        if ($last.length == 0) {
            $card.find('.cw-dialogcards-card-content-legend').html('Karte 0');
            $card.insertAfter($view.$('.cw-dialogcards-card-content-default'));
        } else {
            $card.find('.cw-dialogcards-card-content-legend').html('Karte '+ index);
            $card.insertAfter($last);
        }
        $card.find('.cw-dialogcards-front-img-file').show();
        $card.find('.cw-dialogcards-back-img-file').show();
    },

    removeCard(event) {
        var $view = this;
        var fieldset = this.$(event.target).closest('.cw-dialogcards-card-content');
        fieldset.remove();
        var $title = $view.$('.cw-dialogcards-card-content-default .cw-dialogcards-card-content-legend').text();
        var $datasets = $view.$('.cw-dialogcards-card-content').not('.cw-dialogcards-card-content-default');
        $.each($datasets, function(i){
            $(this).find('.cw-dialogcards-card-content-legend').text($title+' '+i);
        });
    },

    toggleSource(event) {
        var $switch = this.$(event.target).closest('label.cw-dialogcards-switch');
        var $card = $switch.closest('.cw-dialogcards-card-content');
        if ($switch.hasClass('cw-dialogcards-front-switch')) {
            if (this.$(event.target).prop('checked')) {
                $card.find('.cw-dialogcards-front-img').show();
                $card.find('.cw-dialogcards-front-img-file').hide();
            } else {
                $card.find('.cw-dialogcards-front-img').hide();
                $card.find('.cw-dialogcards-front-img-file').show();
            }
        }
        if ($switch.hasClass('cw-dialogcards-back-switch')) {
            if (this.$(event.target).prop('checked')) {
                $card.find('.cw-dialogcards-back-img').show();
                $card.find('.cw-dialogcards-back-img-file').hide();
            } else {
                $card.find('.cw-dialogcards-back-img').hide();
                $card.find('.cw-dialogcards-back-img-file').show();
            }
        }
    }
});
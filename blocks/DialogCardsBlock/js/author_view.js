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
        'change select.cw-dialogcards-source': 'toggleSource'
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
            switch ($(this).find('.cw-dialogcards-source-front').val()) {
                case 'url':
                    $(this).find('.cw-dialogcards-front-img').show();
                    $(this).find('.cw-dialogcards-front-img-info').show();
                    break;
                case 'file':
                    $(this).find('.cw-dialogcards-front-img-file').show();
                    $(this).find('.cw-dialogcards-front-img-file-info').show();
                    let file_id = $(this).find('.cw-dialogcards-front-img-file-stored').val();
                    $(this).find('.cw-dialogcards-front-img-file option[file_id="'+file_id+'"]').prop('selected', true);
                    break;
                case 'none':
                    
                    break;
            }
            switch ($(this).find('.cw-dialogcards-source-back').val()) {
                case 'url':
                    $(this).find('.cw-dialogcards-back-img').show();
                    $(this).find('.cw-dialogcards-back-img-info').show();
                    break;
                case 'file':
                    $(this).find('.cw-dialogcards-back-img-file').show();
                    $(this).find('.cw-dialogcards-back-img-file-info').show();
                    let file_id = $(this).find('.cw-dialogcards-back-img-file-stored').val();
                    $(this).find('.cw-dialogcards-back-img-file option[file_id="'+file_id+'"]').prop('selected', true);
                    break;
                case 'none':
                    
                    break;
            }
        });
        if($cards.length == 0) {
            this.addCard();
        }

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
            switch ($(this).find('.cw-dialogcards-source-front').val()) {
                case 'url': 
                    card_content.front_img = $(this).find('.cw-dialogcards-front-img').val();
                    card_content.front_external_file = true;
                    break;
                case 'file':
                    card_content.front_img_file_id = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_id');
                    card_content.front_img_file_name = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_name');
                    card_content.front_img = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_url');
                    card_content.front_external_file = false;
                    break;
                case 'none':
                    card_content.front_external_file = false;
                    card_content.front_img = false;
                    break;
            }
            card_content.front_text = $(this).find('.cw-dialogcards-front-text').val();

            switch ($(this).find('.cw-dialogcards-source-back').val()) {
                case 'url':
                    card_content.back_img = $(this).find('.cw-dialogcards-back-img').val();
                    card_content.back_external_file = true;
                    break;
                case 'file':
                    card_content.back_img_file_id = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_id');
                    card_content.back_img_file_name = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_name');
                    card_content.back_img = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_url');
                    card_content.back_external_file = false;
                    break;
                case 'none':
                    card_content.back_external_file = false;
                    card_content.back_img = false;
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
        $card.removeClass('cw-dialogcards-card-content-default').addClass('cw-dialogcards-card-content');
        if ($last.length == 0) {
            $card.find('.cw-dialogcards-card-content-legend').html('Karte 0');
            $card.insertAfter($view.$('.cw-dialogcards-card-content-default'));
        } else {
            $card.find('.cw-dialogcards-card-content-legend').html('Karte '+ index);
            $card.insertAfter($last);
        }
        $card.find('.cw-dialogcards-front-img-file').show();
        $card.find('.cw-dialogcards-front-img-file-info').show();
        $card.find('.cw-dialogcards-back-img-file').show();
        $card.find('.cw-dialogcards-back-img-file-info').show();
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
        var select = this.$(event.target);
        var $card = select.closest('.cw-dialogcards-card-content');
        if (select.hasClass('cw-dialogcards-source-front')) {
            switch (select.val()) {
                case 'url':
                    $card.find('.cw-dialogcards-front-img').show();
                    $card.find('.cw-dialogcards-front-img-info').show();
                    $card.find('.cw-dialogcards-front-img-file').hide();
                    $card.find('.cw-dialogcards-front-img-file-info').hide();
                    break;
                case 'file':
                    $card.find('.cw-dialogcards-front-img').hide();
                    $card.find('.cw-dialogcards-front-img-info').hide();
                    $card.find('.cw-dialogcards-front-img-file').show();
                    $card.find('.cw-dialogcards-front-img-file-info').show();
                    break;
                case 'none':
                    $card.find('.cw-dialogcards-front-img-file').hide();
                    $card.find('.cw-dialogcards-front-img-file-info').hide();
                    $card.find('.cw-dialogcards-front-img').hide();
                    $card.find('.cw-dialogcards-front-img-info').hide();
                    break;
            }
        }
        if (select.hasClass('cw-dialogcards-source-back')) {
            switch (select.val()) {
                case 'url':
                    $card.find('.cw-dialogcards-back-img').show();
                    $card.find('.cw-dialogcards-back-img-info').show();
                    $card.find('.cw-dialogcards-back-img-file').hide();
                    $card.find('.cw-dialogcards-back-img-file-info').hide();
                    break;
                case 'file':
                    $card.find('.cw-dialogcards-back-img').hide();
                    $card.find('.cw-dialogcards-back-img-info').hide();
                    $card.find('.cw-dialogcards-back-img-file').show();
                    $card.find('.cw-dialogcards-back-img-file-info').show();
                    break;
                case 'none':
                    $card.find('.cw-dialogcards-back-img').hide();
                    $card.find('.cw-dialogcards-back-img-info').hide();
                    $card.find('.cw-dialogcards-back-img-file').hide();
                    $card.find('.cw-dialogcards-back-img-file-info').hide();
                    break;
            }
        }
    }
});

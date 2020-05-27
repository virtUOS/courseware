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
        'change select.cw-dialogcards-source': 'toggleSource',
        'click button[name=lowercard]': 'lowerCard',
        'click button[name=raisecard]': 'raiseCard'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        let cards = this.$('.cw-dialogcards-card-content');
        $.each(cards, function() {
            let card_index = $(this).attr('data-index');
            if (card_index == 0) {
                $(this).find('.raisecard').hide();
            }
            if (card_index == cards.length - 1) {
                $(this).find('.lowercard').hide();
            }
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
        if(cards.length == 0) {
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
        let view = this;
        let cards = this.$('.cw-dialogcards-card-content');
        let dialogcards_content = [];
        $.each(cards, function(){
            var card_content = {};
            switch ($(this).find('.cw-dialogcards-source-front').val()) {
                case 'url': 
                    let url = $(this).find('.cw-dialogcards-front-img').val();
                    card_content.front_img = (url != '') ? url : false;
                    card_content.front_external_file = true;
                    break;
                case 'file':
                    card_content.front_img_file_id = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_id');
                    card_content.front_img_file_name = $(this).find('.cw-dialogcards-front-img-file option:selected').attr('file_name');
                    card_content.front_img = true;
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
                    let url = $(this).find('.cw-dialogcards-back-img').val();
                    card_content.back_img = (url != '') ? url : false;
                    card_content.back_external_file = true;
                    break;
                case 'file':
                    card_content.back_img_file_id = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_id');
                    card_content.back_img_file_name = $(this).find('.cw-dialogcards-back-img-file option:selected').attr('file_name');
                    card_content.back_img = true;
                    card_content.back_external_file = false;
                    break;
                case 'none':
                    card_content.back_external_file = false;
                    card_content.back_img = false;
            }
            card_content.back_text = $(this).find('.cw-dialogcards-back-text').val();
            card_content.index = $(this).attr('data-index');
            dialogcards_content.push(card_content);
        });

        dialogcards_content.sort((a,b) => {
            if (a.index > b.index) return 1;
            if (a.index < b.index) return -1;

            return 0;
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
                view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    },

    addCard() {
        let card = this.$('.cw-dialogcards-card-content-default').clone();
        let index = this.$('.cw-dialogcards-card-content').length;
        let last = this.$('.cw-dialogcards-card-content').last();
        let title = this.$('.cw-dialogcards-card-content-default .cw-dialogcards-card-content-legend').text();
        card.removeClass('cw-dialogcards-card-content-default').addClass('cw-dialogcards-card-content');
        if (last.length == 0) {
            card.find('.cw-dialogcards-card-content-legend').html(title + ' 0');
            card.insertAfter(this.$('.cw-dialogcards-card-content-default'));
            card.find('.raisecard').hide();
            card.find('.lowercard').hide();
        } else {
            card.find('.cw-dialogcards-card-content-legend').html(title + ' '+ index);
            card.insertAfter(last);
            last.find('.lowercard').show();
        }
        card.attr('data-index', index);
        card.find('.cw-dialogcards-front-img-file').show();
        card.find('.cw-dialogcards-front-img-file-info').show();
        card.find('.cw-dialogcards-back-img-file').show();
        card.find('.cw-dialogcards-back-img-file-info').show();
        card.find('.lowercard').hide();
    },

    removeCard(event) {
        let fieldset = this.$(event.target).closest('.cw-dialogcards-card-content');
        let title = this.$('.cw-dialogcards-card-content-default .cw-dialogcards-card-content-legend').text();
        fieldset.remove();

        let datasets = this.$('.cw-dialogcards-card-content').not('.cw-dialogcards-card-content-default');
        $.each(datasets, function(i){
            $(this).find('.cw-dialogcards-card-content-legend').text(title+' '+i);
            $(this).attr('data-index', i);
            if (i == 0) {
                $(this).find('.raisecard').hide();
            }
            if (i == datasets.length - 1) {
                $(this).find('.lowercard').hide();
            }
        });
    },

    toggleSource(event) {
        let select = this.$(event.target);
        let card = select.closest('.cw-dialogcards-card-content');
        if (select.hasClass('cw-dialogcards-source-front')) {
            switch (select.val()) {
                case 'url':
                    card.find('.cw-dialogcards-front-img').show();
                    card.find('.cw-dialogcards-front-img-info').show();
                    card.find('.cw-dialogcards-front-img-file').hide();
                    card.find('.cw-dialogcards-front-img-file-info').hide();
                    break;
                case 'file':
                    card.find('.cw-dialogcards-front-img').hide();
                    card.find('.cw-dialogcards-front-img-info').hide();
                    card.find('.cw-dialogcards-front-img-file').show();
                    card.find('.cw-dialogcards-front-img-file-info').show();
                    break;
                case 'none':
                    card.find('.cw-dialogcards-front-img-file').hide();
                    card.find('.cw-dialogcards-front-img-file-info').hide();
                    card.find('.cw-dialogcards-front-img').hide();
                    card.find('.cw-dialogcards-front-img-info').hide();
                    break;
            }
        }
        if (select.hasClass('cw-dialogcards-source-back')) {
            switch (select.val()) {
                case 'url':
                    card.find('.cw-dialogcards-back-img').show();
                    card.find('.cw-dialogcards-back-img-info').show();
                    card.find('.cw-dialogcards-back-img-file').hide();
                    card.find('.cw-dialogcards-back-img-file-info').hide();
                    break;
                case 'file':
                    card.find('.cw-dialogcards-back-img').hide();
                    card.find('.cw-dialogcards-back-img-info').hide();
                    card.find('.cw-dialogcards-back-img-file').show();
                    card.find('.cw-dialogcards-back-img-file-info').show();
                    break;
                case 'none':
                    card.find('.cw-dialogcards-back-img').hide();
                    card.find('.cw-dialogcards-back-img-info').hide();
                    card.find('.cw-dialogcards-back-img-file').hide();
                    card.find('.cw-dialogcards-back-img-file-info').hide();
                    break;
            }
        }
    },

    lowerCard(event) {
        let card = this.$(event.target).closest('.cw-dialogcards-card-content');
        let card_index = card.attr('data-index');
        let next_card = card.next();
        let next_card_index = next_card.attr('data-index');
        let datasets = this.$('.cw-dialogcards-card-content').not('.cw-dialogcards-card-content-default');
        let title = this.$('.cw-dialogcards-card-content-default .cw-dialogcards-card-content-legend').text();

        card.attr('data-index', next_card_index);
        card.find('.cw-dialogcards-card-content-legend').text(title + ' ' + next_card_index);
        card.find('.lowercard').show();
        card.find('.raisecard').show();
        if(next_card_index == datasets.length - 1) {
            card.find('.lowercard').hide();
        }

        next_card.attr('data-index', card_index);
        next_card.find('.cw-dialogcards-card-content-legend').text(title + ' ' + card_index);
        next_card.find('.lowercard').show();
        next_card.find('.raisecard').show();
        if(card_index == 0) {
            next_card.find('.raisecard').hide();
        }

        card.insertAfter(next_card);

    },

    raiseCard(event) {
        let card = this.$(event.target).closest('.cw-dialogcards-card-content');
        let card_index = card.attr('data-index');
        let prev_card = card.prev();
        let prev_card_index = prev_card.attr('data-index');
        let datasets = this.$('.cw-dialogcards-card-content').not('.cw-dialogcards-card-content-default');
        let title = this.$('.cw-dialogcards-card-content-default .cw-dialogcards-card-content-legend').text();

        card.attr('data-index', prev_card_index);
        card.find('.cw-dialogcards-card-content-legend').text(title + ' ' + prev_card_index);
        card.find('.lowercard').show();
        card.find('.raisecard').show();
        if(prev_card_index == 0) {
            card.find('.raisecard').hide();
        }

        prev_card.attr('data-index', card_index);
        prev_card.find('.cw-dialogcards-card-content-legend').text(title + ' ' + card_index);
        prev_card.find('.lowercard').show();
        prev_card.find('.raisecard').show();
        if(card_index == datasets.length - 1) {
            prev_card.find('.lowercard').hide();
        }

        card.insertBefore(prev_card);
    }
});

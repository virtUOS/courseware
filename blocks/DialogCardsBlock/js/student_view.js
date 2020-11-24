import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import tooltip from 'js/tooltip'

export default StudentView.extend({
    events: {
        'click .card' : 'flipCard',
        'click .cw-dialogcards-next': 'showNextCard',
        'click .cw-dialogcards-prev': 'showPrevCard'
    },

    initialize() {},

    render() {
        return this;
    },

    postRender() {
        this.$('.scene').first().addClass('is-displayed');
        tooltip(this.$el, '.card');
        document.documentElement.lang = "de"; // we need this for hyphens
        return this;
    },

    flipCard(event) {
        let card = $(event.currentTarget);
        card.toggleClass('is-flipped');
    },

    showNextCard(event) {
        let button = this.$(event.target);
        if (button.data('clicked')) {
            return;
        } else {
            button.data('clicked', true);
            let current_card = this.$('.scene.is-displayed');
            let index = parseInt(current_card.attr('data-index'));
            let next_card = this.$('.scene[data-index="'+(index+1)+'"]');
            if (next_card.length != 0) {
                current_card.removeClass('is-displayed');
                current_card.hide();
                next_card.find('.card').removeClass('is-flipped');
                next_card.show().addClass('is-displayed').effect('shake', {times:1, distance: 10, direction: 'right'}, 500);
            }
            window.setTimeout(function(){
                button.removeData('clicked');
            }, 500)
        }
    }, 

    showPrevCard(event) {
        let button = this.$(event.target);
        if (button.data('clicked')) {
            return;
        } else {
            button.data('clicked', true);
            let current_card = this.$('.scene.is-displayed');
            let index = parseInt(current_card.attr('data-index'));
            let prev_card = this.$('.scene[data-index="'+(index-1)+'"]');
            if (prev_card.length != 0) {
                current_card.hide().removeClass('is-displayed');
                prev_card.find('.card').removeClass('is-flipped');
                prev_card.show().addClass('is-displayed').effect('shake', {times:1, distance: 10, direction: 'left'}, 500);
            }
            window.setTimeout(function(){
                button.removeData('clicked');
            }, 500)
        }
    }
});

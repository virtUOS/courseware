import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
  events: {
      'click .card' : 'flipCard',
      'click .cw-dialogcards-next': 'showNextCard',
      'click .cw-dialogcards-prev': 'showPrevCard'
  },

    initialize() { },

    render() {
        return this;
    },

    postRender() {
        this.$('.scene').first().addClass('is-displayed');
        return this;
    },

    flipCard(event) {
        var $card = $(event.currentTarget);
        $card.toggleClass('is-flipped');
    },

    showNextCard() {
        var $current_card = this.$('.scene.is-displayed');
        var index = parseInt($current_card.attr('data-index'));
        var $next_card = this.$('.scene[data-index="'+(index+1)+'"]');
        if ($next_card.length != 0) {
            $current_card.hide().removeClass('is-displayed');
            $next_card.find('.card').removeClass('is-flipped');
            $next_card.show().addClass('is-displayed').effect( "shake" );
        }
    }, 

    showPrevCard() {
        var $current_card = this.$('.scene.is-displayed');
        var index = parseInt($current_card.attr('data-index'));
        var $prev_card = this.$('.scene[data-index="'+(index-1)+'"]');
        if ($prev_card.length != 0) {
            $current_card.hide().removeClass('is-displayed');
            $prev_card.find('.card').removeClass('is-flipped');
            $prev_card.show().addClass('is-displayed').effect( "shake" );
        }
    }
});

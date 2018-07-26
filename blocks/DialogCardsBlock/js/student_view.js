import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
  events: {
      'click .js-card' : 'flipCard',
      'click .cw-dialogcards-next': 'showNextCard',
      'click .cw-dialogcards-prev': 'showPrevCard'
  },

    initialize() { },

    render() {
        return this;
    },

    postRender() {
        this.$('.card').first().addClass('is-displayed');
        return this;
    },

    flipCard(event) {
        var cardTransitionTime = 1000;
        var $card = $(event.currentTarget);
        var switching = false;
        if (switching) {
          return false
        }
        switching = true

        $card.toggleClass('is-switched')
        window.setTimeout(function () {
          $card.children().children().toggleClass('is-active')
          switching = false
        }, cardTransitionTime / 2)
    },

    showNextCard() {
        var $current_card = this.$('.card.is-displayed');
        var index = parseInt($current_card.attr('data-index'));
        var $next_card = this.$('.card[data-index="'+(index+1)+'"]');
        if ($next_card.length != 0) {
            $current_card.hide().removeClass('is-displayed');
            $next_card.show().addClass('is-displayed');
        }
    }, 

    showPrevCard() {
        var $current_card = this.$('.card.is-displayed');
        var index = parseInt($current_card.attr('data-index'));
        var $prev_card = this.$('.card[data-index="'+(index-1)+'"]');
        if ($prev_card.length != 0) {
            $current_card.hide().removeClass('is-displayed');
            $prev_card.show().addClass('is-displayed');
        }
    }
});

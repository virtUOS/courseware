import jQuery from 'jquery'
import StudentView from 'js/student_view'

export default StudentView.extend({
    events: {
    },

    initialize() {
        var $section = this.$el.closest('section.PortfolioBlockUser');
        var $sortingButtons = jQuery('button.lower', $section);
        $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
        $sortingButtons.removeClass('no-sorting');
    },

    render() {
        return this;
    },

  postRender() {
      let mathjaxP;

      if (window.MathJax && window.MathJax.Hub) {
        mathjaxP = Promise.resolve(window.MathJax);
      } else if (window.STUDIP && window.STUDIP.loadChunk) {
        mathjaxP = window.STUDIP.loadChunk('mathjax');
      }

      mathjaxP && mathjaxP
        .then(({ Hub }) => {
          Hub.Queue(['Typeset', Hub, this.el]);
        })
        .catch(() => {
          console.log('Warning: Could not load MathJax.');
        });
  }
});

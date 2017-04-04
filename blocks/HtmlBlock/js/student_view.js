import jQuery from 'jquery'
import StudentView from 'js/student_view'

export default StudentView.extend({
  events: {},

  initialize() {
    var $section = this.$el.closest('section.HtmlBlock');
    var $sortingButtons = jQuery('button.lower', $section);
    $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
    $sortingButtons.removeClass('no-sorting');
  },

  render() {
    return this;
  },

  postRender() {
    window.MathJax.Hub.Queue([ 'Typeset', window.MathJax.Hub, this.el ]);
  }
});

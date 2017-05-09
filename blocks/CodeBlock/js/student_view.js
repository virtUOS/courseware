import jQuery from 'jquery'
import StudentView from 'js/student_view'
import hljs from 'highlight.js'

export default StudentView.extend({
  events: {},

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
    hljs.highlightBlock(this.$('.code-content > pre > code')[0]);
  }
});

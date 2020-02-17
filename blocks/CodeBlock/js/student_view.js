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
    let code = this.$('.code-content > pre > code')[0];
    if (code) {
      hljs.highlightBlock(code);
    }
  }
});

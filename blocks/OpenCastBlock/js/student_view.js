import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
  events: {
  },

  initialize() { },

  render() {
    return this;
  },

  postRender() {
    try {
      OC.ltiCall(OC_SEARCH_URL, OC_LTI_DATA, function() {
          jQuery('iframe.courseware-oc-video').each(function() {
              this.src = this.dataset.src;
          });
      });
    } catch (e) {
      if (e instanceof ReferenceError) {
        console.log('OpenCast is not available');
      } else {
        console.log(e);
      }
    }

    return this;
  },

});

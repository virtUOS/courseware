import jQuery from 'jquery'
import StudentView from 'js/student_view'
import slick from 'slick-carousel'


export default StudentView.extend({
  events: {},

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
      $('.cw-gallery').slick();
  }
});

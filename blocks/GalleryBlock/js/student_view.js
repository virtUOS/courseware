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
      var $autoplay = this.$el.find('input[name="gallery-autoplay"]').val() == 1;
      var $autoplaytimer = this.$el.find('input[name="gallery-autoplay-timer"]').val()*1000;
      var $hidenav = this.$el.find('input[name="gallery-hidenav"]').val() == 0;
      if (!$hidenav) {
            this.$el.find(".cw-gallery").addClass("cw-gallery-hidenav");
      }
      $autoplaytimer == "" ? $autoplaytimer = 2000 : $autoplaytimer = $autoplaytimer;
      this.$('.cw-gallery').slick({
            arrows: $hidenav,
            autoplay: $autoplay,
            autoplaySpeed: $autoplaytimer,
            infinite: true
      });
  }
});

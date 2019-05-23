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
      let $autoplay = this.$('input[name="gallery-autoplay"]').val() == 1;
      let $autoplaytimer = this.$('input[name="gallery-autoplay-timer"]').val()*1000;
      let $hidenav = this.$('input[name="gallery-hidenav"]').val() == 0;
      let gallery_height = this.$('input[name="gallery-height"]').val();
      let gallery_width = this.$('.cw-gallery-image-wrapper')[0].offsetWidth;
      let $images = this.$('.cw-gallery-image');
      if (!$hidenav) {
            this.$el.find(".cw-gallery").addClass("cw-gallery-hidenav");
      }
      $.each($images, function(){
        let img = $(this)[0];
        let aspact = img.width / img.height;
        img.height = gallery_width / aspact;
        if (img.height > gallery_height) {
          img.height = gallery_height;
        }
      });
      $autoplaytimer == "" ? $autoplaytimer = 2000 : $autoplaytimer = $autoplaytimer;
      
      this.$('.cw-gallery').slick({
            arrows: $hidenav,
            autoplay: $autoplay,
            autoplaySpeed: $autoplaytimer,
            infinite: true
      });
  }
});

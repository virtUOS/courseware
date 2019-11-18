import jQuery from 'jquery';
import StudentView from 'js/student_view';
import slick from 'slick-carousel';


export default StudentView.extend({
  events: {},

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
    if(this.$('input[name="gallery_has_files"]').val() == 0) {
      return;
    }
    let view = this;
    let $autoplay = this.$('input[name="gallery-autoplay"]').val() == 1;
    let $autoplaytimer = this.$('input[name="gallery-autoplay-timer"]').val() * 1000;
    let $hidenav = this.$('input[name="gallery-hidenav"]').val() == 0;
    let gallery_height = this.$('input[name="gallery-height"]').val();
    let gallery_width = this.$('.cw-gallery-image-wrapper')[0].offsetWidth;
    let $images = this.$('.cw-gallery-image');
    if (!$hidenav) {
      this.$el.find('.cw-gallery').addClass('cw-gallery-hidenav');
    }
    $images.ready(function () {
      jQuery.each($images, function () {
        let img = jQuery(this)[0];
        let aspect = img.width / img.height;
        if (img.height == 0) {
          img.height = gallery_height;
        } else {
          img.height = gallery_width / aspect;
          if (img.height > gallery_height) {
            img.height = gallery_height;
          }
        }
      });
      if ($autoplaytimer == '') {
        $autoplaytimer = 2000;
      }
      view.$('.cw-gallery').slick({
        arrows: $hidenav,
        autoplay: $autoplay,
        autoplaySpeed: $autoplaytimer,
        infinite: true
      });
      view.$('.cw-gallery').show();
    });

  }
});

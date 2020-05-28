import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
  events: {
    'click .cw-download-link': 'onDownload'
  },

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
    this.confirmed =  this.$('input[name="download_confirmed"]').val() == true;
    if (this.confirmed && this.$('.cw-download-success-content').val()) {
      this.$('.cw-download-success-box').show();
    }
    if (!this.confirmed && this.$('.cw-download-info-content').val()) {
      this.$('.cw-download-info-box').show();
    }
  },

  onDownload(event) {
    if (!this.confirmed) {
      event.preventDefault();
      let view = this;
      helper
        .callHandler(this.model.id, 'download', {})
        .then (function() {
          window.location = (view.$('.cw-download-link').attr('href'));
          view.confirmed = true;
          view.$('.cw-download-info-box').hide();
          view.$('.cw-download-success-box').show();
        })
        .catch(function (error) {
          if (error.responseText) {
              var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
              alert(errorMessage);
              console.log(errorMessage, arguments);
          }
        });
    }
  }
});

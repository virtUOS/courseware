import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import templates from 'js/templates'

export default StudentView.extend({
  events: {
    'click button[name=download]': 'onDownload'
  },

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
  },

  onDownload() {
    this.model.set('confirmed', true);
    this.model.set('file', this.$('input[name="file"]').val());
    this.model.set('file_name', this.$('input[name="file_name"]').val());
    this.model.set('file_id', this.$('input[name="file_id"]').val());
    this.model.set('download_title', this.$('input[name="download_title"]').val());
    this.model.set('download_info', this.$('input[name="download_info"]').val());
    this.model.set('download_success', this.$('input[name="download_success"]').val());
    this.model.set('download_access', this.$('input[name="download_access"]').val());

    this.$el.html(templates('DownloadBlock', 'student_view', { ...this.model.attributes }));
    helper
      .callHandler(this.model.id, 'download', {})
      .catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  }
});

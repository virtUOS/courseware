import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import templates from 'js/templates'

export default StudentView.extend({
  events: {
    'change input[type=checkbox]': 'onConfirm'
  },

  initialize() {
  },

  initializeFromDOM: function () {
    // complete model by retrieving the attributes from the
    // DOM instead of making a roundtrip to the server
    this.model.set({
      'confirmed': this.$('input[name="confirmed"]').prop('checked'),
      'title':     this.$('.title').html()
    });
  },

  render() {
    this.$el.html(templates('ConfirmBlock', 'student_view', { ...this.model.attributes }));
    return this;
  },

  onConfirm() {
    this.model.set('confirmed', true);
    this.render();

    helper
      .callHandler(this.model.id, 'confirm', {})
      .catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  }
});

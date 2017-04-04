import $ from 'jquery'
import AuthorView from 'js/author_view'

export default AuthorView.extend({
  events: {
    'click button[name="save"]':   'onSave',
    'click button[name="cancel"]': 'switchBack'
  },

  render() {
    return this;
  },

  postRender() {
  },

  onSave(event) {
    var input = this.$('input[name="title"]'),
        button = $(event.target),
        new_title = input.val().trim(),
        view = this;

    if (new_title === '') {
      return;
    }

    // disable button for now
    button.prop('disabled', true);

    this.model.set('title', new_title);

    this.model.save()
        .then(function () {
          view.switchBack();
        }).catch(function (error) {
          button.prop('disabled', false);

          var errorMessage = 'Could not update the title: ' + $.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  }
});

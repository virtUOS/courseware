import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'change select': 'checkUpload'
  },

  initialize() {
    Backbone.on('beforemodeswitch', this.onModeSwitch, this);
    Backbone.on('beforenavigate', this.onNavigate, this);
  },

  render() {
    return this;
  },

  postRender() {
    var $stored_folder = this.$el.find('.cw-folder-stored-folder').val();
    this.$('.cw-folder-select-folder').select2({
      templateResult: state => {
        if (!state.id) { return state.text; }
        if (state.element.dataset.folder_size > 0) { state.element.className = state.element.className + ' full';}
        var $state = $(
          '<span class="new-select2-item ' + state.element.className + '"></span><span>' + state.text + '</span>'
        );
        return $state;
      }
    });
    this.$('.cw-folder-select-folder').val($stored_folder).trigger('change');
    this.$('.cw-folder-allow-upload')[0].checked = this.$('input[name="prev_allow_upload"]').val();
  },

  onNavigate(event) {
    if (!$('section .block-content button[name=save]').length) {
      return;
    }
    if (event.isUserInputHandled) {
      return;
    }
    event.isUserInputHandled = true;
    Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
  },

  onModeSwitch(toView, event) {
    if (toView != 'student') {
      return;
    }
    // the user already switched back (i.e. the is not visible)
    if (!this.$el.is(':visible')) {
      return;
    }
    // another listener already handled the user's feedback
    if (event.isUserInputHandled) {
      return;
    }
    event.isUserInputHandled = true;
    Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
  },

  onSave(event) {
    var view = this;
    var $folder_content = {
      folder_id: this.$('.cw-folder-select-folder').val(),
      folder_title: this.$('input[name="download-title"]').val(),
      allow_upload: this.$('input[name="allow-upload"]')[0].checked
    }
    
    helper
      .callHandler(this.model.id, 'save', {
        folder_content: JSON.stringify($folder_content)
      })
      .then(function () {
        $(event.target).addClass('accept');
        view.switchBack();
      }).catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  },

  checkUpload() {
    switch(this.$('.cw-folder-select-folder :selected').attr('folder_type')) {
    case 'HomeworkFolder':
      this.$('.cw-folder-allow-upload').prop('checked', true);
      this.$('.cw-folder-allow-upload').prop('disabled', true);
      this.$('.cw-upload-label').css('color', 'graytext');
      break;
    case 'MaterialFolder':
      this.$('.cw-folder-allow-upload').prop('checked', false);
      this.$('.cw-folder-allow-upload').prop('disabled', true);
      this.$('.cw-upload-label').css('color', 'graytext');
      break;
    default:
      this.$('.cw-folder-allow-upload').prop('disabled', false);
      this.$('.cw-upload-label').css('color', 'black');
    }
  }

});

import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'change .download-folder': 'selectFolder'
  },

  initialize() {
    Backbone.on('beforemodeswitch', this.onModeSwitch, this);
    Backbone.on('beforenavigate', this.onNavigate, this);
  },

  render() {
    return this;
  },

  postRender() {
    var stored_folder = this.$('.download-stored-folder').val();
    this.$('.download-folder').select2({
      templateResult: state => {
        if (!state.id) { return state.text; }
        var $state = $(
          '<span data-foldername="' + state.element.dataset.foldername +'">' + state.text + '</span>'
        );
        return $state;
      }
    });
    this.$('.download-folder').val(stored_folder).trigger('change');
    this.selectFolder();
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
    var $file = this.$('.download-file');
    var $folder = this.$('.download-folder');
    var $file_val = $file.val();
    var $file_id = $file.find('option:selected').attr('file_id');
    var $file_name = $file.find('option:selected').attr('file_name');
    var $folder_id = $folder.val();
    var $download_title = this.$('input[name="download-title"]').val();
    var $download_info = this.$('input[name="download-info"]').val();
    var $download_success = this.$('input[name="download-success"]').val();
    var $download_grade = this.$('input[name="download-grade"]').is(':checked');

    helper
      .callHandler(this.model.id, 'save', {
        file: $file_val,
        file_id: $file_id,
        file_name: $file_name,
        folder_id: $folder_id,
        download_title: $download_title,
        download_info: $download_info,
        download_success: $download_success,
        download_grade: $download_grade
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

  selectFolder() {
    var view = this;
    var $folder = this.$('.download-folder').find(':selected').data('filename');
    var $folder_id = this.$('.download-folder').val();
    helper
      .callHandler(this.model.id, 'setfolder', { folder: $folder, folder_id: $folder_id })
      .then(function (event) {
        $(event.target).addClass('accept');
        if (event) {
          view.showFiles(event);
        }
      }).catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  },

  showFiles($allfiles) {
    var $files = this.$('.download-file');
    var $stored_file = this.$('.download-stored-file').val();

    $files.find('option').remove();
    if ($allfiles.length == 0) {
        $files.append($('<option>', {
            value: "",
            text: "In diesem Ordner befinden sich keine Dateien."
        }));
        $files.prop('disabled', 'disabled');
        return false;
    }
    $.each($allfiles, function (key, value) {
      $files.append($('<option>', {
        value: value.name,
        file_id: value.id,
        text: value.name,
        selected: value.id == $stored_file
      }));
    });
    $files.prop('disabled', false);
    $files.select2();
    return true;
  }
});

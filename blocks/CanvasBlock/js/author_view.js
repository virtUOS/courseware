import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'change select.cw-canvasblock-source': 'selectSource'
  },

  initialize() {
    Backbone.on('beforemodeswitch', this.onModeSwitch, this);
    Backbone.on('beforenavigate', this.onNavigate, this);
  },

  render() {
    return this;
  },

  postRender() {
    var stored_content = this.$('.cw-canvasblock-content-stored').val();

    this.$('select.cw-canvasblock-file').select2({
      templateResult: state => {
        if (!state.id) { return state.text; }
        var $state = $(
          '<span data-filename="' + state.element.dataset.filename +'">' + state.text + '</span>'
        );
        return $state;
      }
    });

    if (stored_content == '') {
        this.$('input.cw-canvasblock-file').hide();
        this.$('.cw-canvasblock-file-input-info').hide();
        this.$('select.cw-canvasblock-file').show();
        this.$('.cw-canvasblock-file-select-info').show();
        this.$('.cw-canvasblock-source option[value="cw"]').prop('selected', true);

        return;
    }
    var content = JSON.parse(stored_content);

    switch (content.source) {
        case 'cw':
            this.$('input.cw-canvasblock-file').hide();
            this.$('.cw-canvasblock-file-input-info').hide();
            this.$('select.cw-canvasblock-file').val(content.image_id).trigger('change');
            this.$('.cw-canvasblock-source option[value="cw"]').prop('selected', true);
            this.$('select.cw-canvasblock-file').show();
            this.$('.cw-canvasblock-file-select-info').show();
            break;
        case 'web':
            this.$('select.cw-canvasblock-file').hide();
            this.$('.cw-canvasblock-file-select-info').hide();
            this.$('input.cw-canvasblock-file').val(content.image_url);
            this.$('.cw-canvasblock-source option[value="web"]').prop('selected', true);
            this.$('.cw-canvasblock-file-input-info').show();
            break;
        case 'none':
            this.$('input.cw-canvasblock-file').hide();
            this.$('.cw-canvasblock-file-input-info').hide();
            this.$('select.cw-canvasblock-file').hide();
            this.$('.cw-canvasblock-file-select-info').hide();
            this.$('.cw-canvasblock-source option[value="none"]').prop('selected', true);
            break;
    }
    this.$('.cw-canvasblock-upload-folder option[folder-id="' + content.upload_folder_id + '"]').prop('selected', true);
    this.$('.cw-canvasblock-show-userdata option[value="' + content.show_userdata + '"]').prop('selected', true);

    this.$('.cw-canvasblock-description').val(content.description);
  },

  onNavigate(event) {
    if (!$('section .block-content button[name=save]').length) {
      return;
    }
    if(event.isUserInputHandled) {
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
    var content = {};

    content.source = this.$('.cw-canvasblock-source').val();
    switch (content.source){
        case 'web':
            content.image_url = this.$('input.cw-canvasblock-file').val();
            if(content.image_url != '') {
              content.image = true;
            } else {
              content.image = false;
              content.source = 'none';
            }
            break;
        case 'cw':
            content.image_url = '';
            content.image_id = this.$('select.cw-canvasblock-file').val();
            content.image_name = this.$('select.cw-canvasblock-file').find(':selected').data('filename');
            if (content.image_id != '') {
              content.image = true;  
            } else {
              content.image = false;
              content.source = 'none';
            }
            break;
        case 'none':
            content.image_url = '';
            content.image = false;
            break;
    }

    content.description = this.$('.cw-canvasblock-description').val();

    content.upload_folder_name = this.$('.cw-canvasblock-upload-folder').val();
    content.upload_folder_id = this.$('.cw-canvasblock-upload-folder option:selected').attr('folder-id');
    if ((content.upload_folder_name == '') || (content.upload_folder_id == '')) {
      content.upload_enabled = false;
    } else {
      content.upload_enabled = true;
    }

    content.show_userdata = this.$('.cw-canvasblock-show-userdata').val();

    content = JSON.stringify(content);
    helper
      .callHandler(this.model.id, 'save', {
          canvas_content: content
      })
      .then(
        // success
        function () {
          $(event.target).addClass('accept');
          view.switchBack();
        },

        // error
        function (error) {
          var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  },

  selectSource() {
    var $selection = this.$('.cw-canvasblock-source').val();
    this.$('input.cw-canvasblock-file').hide();
    this.$('.cw-canvasblock-file-input-info').hide();
    this.$('select.cw-canvasblock-file').select2().next().hide();
    this.$('.cw-canvasblock-file-select-info').hide();

    switch ($selection) {
        case 'cw':
            this.$('select.cw-canvasblock-file').select2().next().show();
            this.$('.cw-canvasblock-file-select-info').show();
            break;
        case 'web':
            this.$('input.cw-canvasblock-file').show();
            this.$('.cw-canvasblock-file-input-info').show();
            break;
    }

    return;
  }

});

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
    var $view = this;
    var stored_content = this.$('.cw-canvasblock-content-stored').val();
    if (stored_content == '') {
        $view.$('input.cw-canvasblock-file').hide();
        $view.$('.cw-canvasblock-file-input-info').hide();
        $view.$('select.cw-canvasblock-file').show();
        $view.$('.cw-canvasblock-file-select-info').show();
        $view.$('.cw-canvasblock-source option[value="cw"]').prop('selected', true);

        return;
    }
    var content = JSON.parse(stored_content);

    switch (content.source) {
        case 'cw':
            $view.$('input.cw-canvasblock-file').hide();
            $view.$('.cw-canvasblock-file-input-info').hide();
            $view.$('.cw-canvasblock-file option[file-id="'+content.image_id+'"]').prop('selected', true);
            $view.$('.cw-canvasblock-source option[value="cw"]').prop('selected', true);
            $view.$('select.cw-canvasblock-file').show();
            $view.$('.cw-canvasblock-file-select-info').show();
            break;
        case 'web':
            $view.$('select.cw-canvasblock-file').hide();
            $view.$('.cw-canvasblock-file-select-info').hide();
            $view.$('input.cw-canvasblock-file').val(content.image_url);
            $view.$('.cw-canvasblock-source option[value="web"]').prop('selected', true);
            $view.$('.cw-canvasblock-file-input-info').show();
            break;
        case 'none':
            $view.$('input.cw-canvasblock-file').hide();
            $view.$('.cw-canvasblock-file-input-info').hide();
            $view.$('select.cw-canvasblock-file').hide();
            $view.$('.cw-canvasblock-file-select-info').hide();
            $view.$('.cw-canvasblock-source option[value="none"]').prop('selected', true);
            break;
    }
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
    var $view = this;
    var content = {};

    content.source = this.$('.cw-canvasblock-source').val();
    switch (content.source){
        case 'web':
            content.image_url = $view.$('input.cw-canvasblock-file').val();
            content.image = true;
            break;
        case 'cw':
            content.image_url = '';
            content.image_id = $view.$('select.cw-canvasblock-file option:selected').attr('file-id');
            content.image_name = $view.$('select.cw-canvasblock-file option:selected').attr('filename');
            content.image = true;
            break;
        case 'none':
            content.image_url = '';
            content.image = false;
            break;
    }
    content.description = $view.$('.cw-canvasblock-description').val();

    content = JSON.stringify(content);
    helper
      .callHandler(this.model.id, 'save', {
          canvas_content: content
      })
      .then(
        // success
        function () {
          $(event.target).addClass('accept');
          $view.switchBack();
        },

        // error
        function (error) {
          var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  },

  selectSource() {
    var $view = this;
    var $selection = $view.$('.cw-canvasblock-source').val();
    $view.$('input.cw-canvasblock-file').hide();
    $view.$('.cw-canvasblock-file-input-info').hide();
    $view.$('select.cw-canvasblock-file').hide();
    $view.$('.cw-canvasblock-file-select-info').hide();

    switch ($selection) {
        case 'cw':
            $view.$('select.cw-canvasblock-file').show();
            $view.$('.cw-canvasblock-file-select-info').show();
            break;
        case 'web':
            $view.$('input.cw-canvasblock-file').show();
            $view.$('.cw-canvasblock-file-input-info').show();
            break;
    }

    return;
  }

});

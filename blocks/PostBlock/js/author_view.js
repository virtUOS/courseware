import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]'   : 'onSave',
        'click button[name=cancel]' : 'switchBack',
        'click .cw-postblock-reuse' : 'toggleSwitch',
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
        var $stored_id = $view.$('.cw-postblock-id-stored').val();
        if ($stored_id != "") {
            $view.$('select.cw-postblock-id option[value="'+$stored_id+'"]').prop('selected', true);
        }
        this.toggleSwitch();
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
        var $post_title = $view.$(".cw-postblock-title").val();
        var $thread_id = "new";
        var $reuse = $view.$(".cw-postblock-reuse").is(":checked");
        if ($reuse) {
            $thread_id = $view.$(".cw-postblock-id").val();
        }
        helper
            .callHandler(this.model.id, 'save', {
                post_title: $post_title,
                thread_id: $thread_id
            })
            .then(
                // success
                function () {
                  $(event.target).addClass('accept');
                  $view.switchBack();
                  $view.postRender();
                },
            
                // error
                function (error) {
                  var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
                  alert(errorMessage);
                  console.log(errorMessage, arguments);
                }
            );
    },

    toggleSwitch() {
        var state = this.$(".cw-postblock-reuse").is(":checked");
        if (state) {
            this.$('label[for="cw-postblock-id"]').css("display", "inline-block");
            this.$('.cw-postblock-id').show();
        } else {
            this.$('label[for="cw-postblock-id"]').hide();
            this.$('.cw-postblock-id').hide();
        }
    }

});

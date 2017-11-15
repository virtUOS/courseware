import Backbone from 'backbone'
import jQuery from 'jquery'
import AuthorView from 'js/author_view'
import helper from 'js/url'
import hljs from 'highlight.js'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change textarea.code-content-editor': 'updatePreview',
        'keyup textarea.code-content-editor': 'updatePreview',
        'paste textarea.code-content-editor': 'updatePreview',
        'change input.code_lang': 'updateLang',
        'keyup input.code_lang': 'updateLang',
        'paste input.code_lang': 'updateLang'
    },

    initialize() {
        var $section = this.$el.closest('section.HtmlBlock');
        var $sortingButtons = jQuery('button.lower', $section);
        $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
        $sortingButtons.addClass('no-sorting');

        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    onNavigate(event) {
        if (!jQuery('section .block-content button[name=save]').length) {
            return;
        }
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },

    render() {
        return this;
    },

    postRender() {
        hljs.highlightBlock(this.$('.code-content-preview > pre > code')[0]);
    },
    
    updatePreview() {
        var $code_content = this.escapeHtml(this.$('textarea').val());
        var $code_lang = this.$(".code_lang").val();
        var $preview_block = this.$('.code-content-preview > pre > code');
        $preview_block.html($code_content);
        if ($code_lang !== "") {
            $preview_block.removeAttr('class');
            $preview_block.addClass("hljs");
            $preview_block.addClass($code_lang);
        } else {
            var $classes = $preview_block.attr('class').split(/\s+/);
            if ($classes.length > 1) {
                this.$(".code_lang").val($classes[1]);
            }
        }
        hljs.highlightBlock($preview_block[0]);
    },
    
    updateLang() {
        var $code_content = this.escapeHtml(this.$('textarea').val());
        var $code_lang = this.$(".code_lang").val();
        var $preview_block = this.$('.code-content-preview > pre > code');
        $preview_block.html($code_content);
        $preview_block.removeAttr('class');
        $preview_block.addClass("hljs");
        if ($code_lang !== "") {
            $preview_block.addClass($code_lang);
        }
        hljs.highlightBlock($preview_block[0]);
    },

    onSave(event) {
        var view = this;
        var $code_content = this.$('textarea').val();
        var $code_lang = this.$(".code_lang").val();
        helper
          .callHandler(this.model.id, 'save', { code_content: $code_content, code_lang: $code_lang })
          .then(function () {
            jQuery(event.target).addClass('accept');
            view.switchBack();
          }).catch(function (error) {
            var errorMessage = 'Could not update the block: ' + jQuery.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
          });
    },
    
    escapeHtml(text) {
      var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };

      return text.replace(/[&<>"']/g, function(m) { return map[m]; });
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
    }
});

import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-link-type':'onSelectType'
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
        if($view.$(".cw-link-stored-type").val() != "") {
            $view.$('select.cw-link-type option[value="'+$view.$('.cw-link-stored-type').val()+'"]').prop('selected', true);
        }
        this.onSelectType();
        
        if($view.$(".cw-link-stored-target").val() != "") {
            if ($view.$(".cw-link-type option:selected").val() == "internal"){
            $view.$('select.cw-link-target option[value="'+$view.$('.cw-link-stored-target').val()+'"]').prop('selected', true);
            }
            if ($view.$(".cw-link-type option:selected").val() == "external") {
                $view.$('input.cw-link-target').val($view.$('.cw-link-stored-target').val());
            }
        }
        if($view.$(".cw-link-stored-title").val() != "") {
            $view.$('input.cw-link-title').val($view.$('.cw-link-stored-title').val());
        }

        return this;
    },

    onSelectType(){
        var $view = this;
        var $type = $view.$(".cw-link-type option:selected").val();

        if ($type == "internal") {
            $view.$("select.cw-link-target").show();
            $view.$("input.cw-link-target").hide();
        }

        if ($type == "external") {
            $view.$("select.cw-link-target").hide();
            $view.$("input.cw-link-target").show();
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
        var $linktype = $view.$(".cw-link-type option:selected").val();
        if ($linktype == "internal") {
            var $linktarget = $view.$("select.cw-link-target option:selected").val();
        }
        if ($linktype == "external") {
            var $linktarget = $view.$("input.cw-link-target").val();
        }
        var $linktitle = $view.$(".cw-link-title").val();

        if ($linktitle == "") {
            $linktitle = $linktarget;
        }

        helper
        .callHandler(this.model.id, 'save', {
              link_type : $linktype,
              link_target: $linktarget,
              link_title:  $linktitle
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                $view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    }
});
    

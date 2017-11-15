import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]'       : 'onSave',
        'click button[name=cancel]'     : 'switchBack',
        'click .submit-user-id-switch'  : 'toggleSubmitUserId',
        'click .iframe-cc-switch'       : 'toggleCCSwitch',
        'change .iframe-cc-license'     : 'CCSelect'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        this.toggleSubmitUserId();
        this.toggleCCSwitch();
        this.setCCInfos();
        this.CCSelect();
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

    toggleSubmitUserId() {
        var state = this.$('.submit-user-id-switch').is( ':checked');
        if (state) {
            this.$('.iframe-submit-user').show();
        } else {
            this.$('.iframe-submit-user').hide();
        }
    },

    toggleCCSwitch() {
        var state = this.$(".iframe-cc-switch").is( ":checked");
        if (state) {
            this.$(".iframe-cc-wrapper").show();
        } else {
            this.$(".iframe-cc-wrapper").hide();
        }
    },

    CCSelect() {
        var license = this.$(".iframe-cc-license").val();
        var icon = this.$(".iframe-cc-icon");
        icon.attr("class", "");
        icon.addClass("iframe-cc-icon");
        icon.addClass(license);
    },

    setCCInfos() {
        if (this.$('.iframe-cc-infos-stored').val() == "") {return;}
        var cc_infos_stored = $.parseJSON(this.$('.iframe-cc-infos-stored').val())[0];
        this.$(".iframe-cc-license option[value='"+cc_infos_stored.cc_type+"']").prop("selected", true);
        this.$(".iframe-cc-work-name").val(cc_infos_stored.cc_work_name);
        this.$(".iframe-cc-work-url").val(cc_infos_stored.cc_work_url);
        this.$(".iframe-cc-author-name").val(cc_infos_stored.cc_author_name);
        this.$(".iframe-cc-author-url").val(cc_infos_stored.cc_author_url);
        this.$(".iframe-cc-license-name").val(cc_infos_stored.cc_license_name);
        this.$(".iframe-cc-license-url").val(cc_infos_stored.cc_license_url);
    },

    onSave(event) {
    var view    = this;
    var $url    = this.$('input.urlinput').val();
    var show_cc = this.$(".iframe-cc-switch").is( ":checked");
    if (show_cc) {
        var cc_infos = new Array();
        cc_infos.push({
            "cc_type"           : this.$(".iframe-cc-license").val(), 
            "cc_work_name"      : this.$(".iframe-cc-work-name").val(),
            "cc_work_url"       : this.$(".iframe-cc-work-url").val(), 
            "cc_author_name"    : this.$(".iframe-cc-author-name").val(),
            "cc_author_url"     : this.$(".iframe-cc-author-url").val(),
            "cc_license_name"   : this.$(".iframe-cc-license-name").val(),
            "cc_license_url"    : this.$(".iframe-cc-license-url").val()
        });
        cc_infos = JSON.stringify(cc_infos);
    } else {
        var cc_infos = ""; 
    }
    if ($url.indexOf("//") == -1) {
        $url = "http://" + $url;
    }

    helper
        .callHandler(this.model.id, 'save', {
            url: $url,
            height: view.$('input.heightinput').val(),
            width: view.$('input.widthinput').val(),
            salt: view.$('.salt').val(), 
            submit_param: view.$('.submit-param').val(), 
            submit_user_id: view.$('.submit-user-id-switch').is( ':checked'),
            cc_infos: cc_infos 
        })
        .then(function () {
            $(event.target).addClass('accept');
            view.switchBack();
        })
        .catch(function (error) {
            var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
        });
    }
});

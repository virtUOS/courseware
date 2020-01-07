import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change .cw-keypoint-icons' : 'setIcon',
        'keyup input[name=cw-keypoint-content]' : 'setContent',
        'change input[name=cw-keypoint-color]' : 'setColor'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        if(this.$(".cw-keypoint-stored-color").val() != "") {
            this.$('.cw-keypoint-input-color[value="' + this.$('.cw-keypoint-stored-color').val()+'"]').attr('checked', 'checked');
        }

        this.$('.cw-keypoint-icons').select2({
            templateResult: state => {
                if (!state.id) {return state.text;}
                var $state = $(
                    '<span class="cw-keypoint-icon-option cw-keypoint-icon-'+ state.element.value +'"></span><span>'+ state.element.text +'</span>'
                );
                return $state;
            }
        });
        if(this.$(".cw-keypoint-stored-icon").val() != "") {
            this.$('.cw-keypoint-icons').val(this.$('.cw-keypoint-stored-icon').val()).trigger('change');
        }

        return this;
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
        var $keypoint_content = this.$('.cw-keypoint-set-content').val();
        var $keypoint_color = this.$('input[name=cw-keypoint-color]:checked').val();
        var $keypoint_icon = this.$('.cw-keypoint-icons').val();

        helper
            .callHandler(this.model.id, 'save', {
                keypoint_content: $keypoint_content,
                keypoint_color: $keypoint_color,
                keypoint_icon: $keypoint_icon
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
                }
            );
    },

    setIcon(){
        let keypointBox = this.$('.cw-keypoint');
        keypointBox.removeClass (function (index, className) {
            return (className.match (/(^|\s)cw-keypoint-icon-\S+/g) || []).join(' ');
        });
        keypointBox.addClass('cw-keypoint-icon-' + this.$('.cw-keypoint-icons').val());
    },

    setContent(){
        this.$('.cw-keypoint-sentence').text(this.$('.cw-keypoint-set-content').val());
    },

    setColor(){
        let keypointBox = this.$('.cw-keypoint');
        keypointBox.removeClass('cw-keypoint-red cw-keypoint-green cw-keypoint-grey cw-keypoint-blue cw-keypoint-yellow');
        keypointBox.addClass('cw-keypoint-' + this.$('input[name=cw-keypoint-color]:checked').val());
    }

});

import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import templates from 'js/templates'

export default StudentView.extend({
    events: {
        'click button[name=send]':  'onSend',
        'click button[name=shrink]':  'shrinkMessages',
        'click button[name=showall]':  'showAllMessages',
        'keydown textarea.cw-postblock-textbox': 'sendShortcut'
    },

    initialize() { 
    },

    render() {
        this.$el.html(templates('PostBlock', 'student_view', { ...this.model.attributes }));

        return this;
    },

    postRender() {
        var $view = this;
        $view.$('.cw-postblock-posts').scrollTop($view.$('.cw-postblock-posts')[0].scrollHeight);
        if($view.$('.cw-postblock-posts').outerHeight() < 420) {
            $view.$('.cw-postblock-showall-messages').hide();
        }
        var updateLoop = setInterval(function() {
            $view.updateContent(updateLoop); 
        }, 5000);
    },

    showAllMessages() {
        this.$('.cw-postblock-shrink-messages').show();
        this.$('.cw-postblock-showall-messages').hide();
        this.$('.cw-postblock-posts').removeClass("shrink-messages");
    },

    shrinkMessages() {
        this.$('.cw-postblock-showall-messages').show();
        this.$('.cw-postblock-shrink-messages').hide();
        this.$('.cw-postblock-posts').addClass("shrink-messages");
    }, 

    onSend(event) {
        var $view = this;
        var $message = $view.$(".cw-postblock-textbox").val();
        if ($message == "") {
            return false;
        }
        helper
            .callHandler(this.model.id, 'message', {
                message: $message
            })
            .then(
                // success
                function () {
                  $(event.target).addClass('accept');
                  $view.updateContent(null);
                },
    
                // error
                function (error) {
                  var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
                  alert(errorMessage);
                  console.log(errorMessage, arguments);
                }
            );
    },

    updateContent(updateLoop) {
        var $view = this;
        var $timestamp = $view.$(".cw-postblock-timestamp").val();
        helper
            .callHandler(this.model.id, 'update', {
                timestamp: $timestamp
            })
            .then(
                // success
                function (content) {
                    if (content.update) {
                        $view.model.set('post_title',content.post_title);
                        $view.model.set('posts',content.posts);
                        $view.model.set('timestamp',content.timestamp);
                        $view.render();
                        $view.$('.cw-postblock-posts').scrollTop($view.$('.cw-postblock-posts')[0].scrollHeight);
                        if($view.$('.cw-postblock-posts').outerHeight() < 420) {
                            $view.$('.cw-postblock-showall-messages').hide();
                        }
                    }
                },
                // error
                function (error) {
                    console.log("error: could not update content");
                    if(updateLoop != null) { 
                        clearInterval(updateLoop);
                    }
                }
            );

        return;
    },

    sendShortcut(e) {
        var $view =  this;
        if (e.ctrlKey && e.keyCode == 13) {
            // Ctrl-Enter pressed
            $view.onSend($view.$('button[name=send]'));
        }
    }
});

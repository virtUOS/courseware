define(['assets/js/author_view', 'assets/js/url', 'utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            "click button[name=save]": "saveVideo",
            "click button[name=cancel]": "switchBack",
            "change select.videotype": "selection",
            "click button[name=preview]": "preview",
            "click button[name=videotimereset]": "videotimereset"
        },
        initialize: function(options) {
        },
        render: function() {
            return this;
        },
        postRender: function() {
            Utils.showPreview(this, this.$el.find('.videourl').text());
            var url = this.$el.find('.videourl').text(), videotype = Utils.getVideoType(url);
            if(url == '') this.$('iframe').hide();
            this.$el.find(".videotype option[value="+videotype+"]").attr('selected', true);
            this.selection();
        },
        selection: function() {
            var videotype = this.$el.find('.videotype').val();
            if (videotype == 'youtube') {
                this.$el.find('.videosrcname').html('YouTube ID');
            } else {
                this.$el.find('.videosrcname').html('URL');
            }
            Utils.resetVideoData(this);
            Utils.setVideoData(this, this.$el.find('.videourl').text(), videotype);
        },
        preview: function() {
            var videourl = this.$el.find('.videourl'), videotype = this.$el.find('.videotype').val(), url = Utils.getUrl(this, videotype);
            var aspect = this.$('input[name="videoaspect"]:checked').val();
            if(url != '') {
                this.$('iframe').show();
                videourl.html(url);
                Utils.showPreview(this, url);
                this.$('.video-wrapper').attr('class', 'video-wrapper '+aspect);
            }
            else this.selection();
        },
        videotimereset: function() {
            this.$el.find('.videostartmin').val("");
            this.$el.find('.videostartsec').val("");
            this.$el.find('.videoendmin').val("");
            this.$el.find('.videoendsec').val("");
        },
        saveVideo: function() {
            this.preview();
            var url = this.$el.find('.videourl').text(), status = this.$('.status');
            var aspect = this.$('input[name="videoaspect"]:checked').val();
            var videoTitle = this.$el.find(".videotitle").val();
            status.text('Speichere Änderungen...');
            var view = this;
            helper.callHandler(this.model.id, 'save', { url: url, videoTitle: videoTitle, aspect: aspect}).then(
                function() {
                    status.text('Änderungen wurden gespeichert.');
                    view.switchBack();
                },
                function (error) {
                    status.text('Fehler beim Speichern: '+jQuery.parseJSON(error.responseText).reason);
                }
            ).done();
        }
    });
});

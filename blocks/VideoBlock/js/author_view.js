define(['assets/js/author_view', 'assets/js/url', 'utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            "click button[name=save]": "saveVideo",
            "click button[name=cancel]": "switchBack",
	    "click #videotype option": "selection",
	    "click button[name=preview]": "preview"
        },
        initialize: function(options) {
	},
        render: function() {
		return this; 
	},
        postRender: function() {
            Utils.showPreview(this, this.$('#videourl').text());
            var url = this.$('#videourl').text(), videotype = Utils.getVideoType(url);
            if(url == '') this.$('iframe').hide();
            this.$("#videotype option[value="+videotype+"]").attr('selected', true);
            this.selection();
        },
	selection: function() {
		var videotype = this.$('#videotype').val();
		if (videotype == 'youtube') this.$('#videosrcname').html('YouTube ID');
		else this.$('#videosrcname').html('URL');
		Utils.resetVideoData(this);
		Utils.setVideoData(this, this.$('#videourl').text(), videotype);
	},
	preview: function() {
		var videourl = this.$('#videourl'), videotype = this.$('#videotype').val(), url = Utils.getUrl(this, videotype);
		if(url != '') {
     			this.$('iframe').show();
			videourl.html(url);
			Utils.showPreview(this, url);
		}
		else this.selection();
	},
        saveVideo: function() {
            this.preview();
            var url = this.$('#videourl').text(), status = this.$('.status');
            status.text('Speichere Änderungen...');
            var view = this;
            helper.callHandler(this.model.id, 'save', { url: url }).then(
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

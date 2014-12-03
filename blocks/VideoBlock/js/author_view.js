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
        initialize: function (options) {
	},
        render: function() { return this; },
        postRender: function () {
            Utils.showPreview(this, this.$('#videourl').text());
            var
                view = this,
                url = view.$('#videourl').text(),
                videotype = Utils.getVideoType(url);

            if(url=='') {
                view.$('iframe').hide();
            }

            view.$("#videotype option[value="+videotype+"]").attr('selected', true);
            view.selection();
        },

	selection: function(){
		var videotype = this.$('#videotype').val();
		if (videotype == 'youtube'){
			this.$('#videosrcname').html('YouTube ID');
		}else {
			this.$('#videosrcname').html('URL');
		}
		Utils.resetVideoData(this);
		Utils.setVideoData(this, this.$('#videourl').text(), videotype);
	},
	preview: function(){
		var
		view = this, 
		videourl = view.$('#videourl'),
		videotype = view.$('#videotype').val(),
		url = Utils.getUrl(view, videotype);
		if (url != ''){
     			view.$('iframe').show();
			videourl.html(url);
			Utils.showPreview(view, url);
		}else this.selection();
	},

        saveVideo: function () {
            this.preview();
	    var view = this;
            var url = view.$('#videourl').text();
            var status = view.$('.status');
	    
            status.text('Speichere Änderungen...');
            helper
                .callHandler(view.model.id, 'save', { url: url })
                .then(function () {
                    status.text('Änderungen wurden gespeichert.');
                    view.switchBack();
                }, function (error) {
                    status.text('Fehler beim Speichern: '+jQuery.parseJSON(error.responseText).reason);
                });
        }
    });
});

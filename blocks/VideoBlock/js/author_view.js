define(['assets/js/author_view', 'assets/js/url', 'utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            //'keyup input': "preview",
            'click button[name=save]': 'saveVideo',
            "click button[name=cancel]": "switchBack",
	    "click #videotype option": "selection",
	    "click button[name=preview]": 'preview'
        },
        initialize: function (options) {
	},
        render: function() { return this; },
        postRender: function () {
	    Utils.showPreview(this, this.$('#videourl').text());
	    var 
            view = this,
            url = view.$('#videourl').text(),
	    videotype = Utils.getVideoType(view, url);
		if(url=='') view.$('iframe').hide();
            view.$("#videotype option[value="+videotype+"]").attr('selected', true);
	    view.selection();

        },
	selection: function(){
		var videotype = this.$('#videotype').val();
		if (videotype == 'youtube'){
			var html = 'youtube ID<input type="text" id="youtubeid"></input>Start<input type="number" id="videostartmin" min="0"></input>';
			html += '<input id="videostartsec" type="number" min="0" max="59" step="1"></input>Ende<input type="number" id="videoendmin" min="0"></input>';
			html += '<input id="videoendsec" type="number" min="0" max="59"  step="1"></input>'
			this.$('#videodata').html(html);
		}else {
			var html = 'url <input type="text" id="urlinput"></input>';
			this.$('#videodata').html(html);
		}
		var url = this.$('#videourl').text();
		var currentvideotype = Utils.getVideoType(this, url);
		switch(currentvideotype){
		    case 'youtube': {
			var youtubeid = url.slice(29).split("?",1);
			this.$('#youtubeid').val(youtubeid);
			var start = url.slice(url.indexOf("start=")+6, url.length);
			start = start.split("&", 1);
			this.$('#videostartmin').val(parseInt(start/60));
			this.$('#videostartsec').val(start%60);
			var end = url.slice(url.indexOf("end=")+4, url.length);
			console.log(end);
			this.$('#videoendmin').val(parseInt(end/60));
			this.$('#videoendsec').val(end%60);	
			break;
		    } 
		    case 'matterhorn':
		    case 'url': {
			this.$('#urlinput').val(url);
			break;
		    }
		}
	},
	preview: function(){
		var
		view = this, 
		videourl = view.$('#videourl'),
		videotype = view.$('#videotype').val(),
		url = Utils.getUrl(view, videotype);
		view.$('iframe').show();
		videourl.html(url);
		Utils.showPreview(view, url);
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

define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        onSave: function (event) {
            var url_input    = this.$("input.urlinput");
            var new_url      = url_input.val().replace("https", "http");
            var height_input = this.$("input.heightinput");
            var new_height   = height_input.val();
            
            var href1_input    = this.$("input.href1input");
            var new_href1      = href1_input.val();
            var linktitle1_input    = this.$("input.linktitle1input");
            var new_linktitle1      = linktitle1_input.val();
			
            var href2_input    = this.$("input.href2input");
            var new_href2      = href2_input.val();
            var linktitle2_input    = this.$("input.linktitle2input");
            var new_linktitle2      = linktitle2_input.val();
			
            var href3_input    = this.$("input.href3input");
            var new_href3      = href3_input.val();
            var linktitle3_input    = this.$("input.linktitle3input");
            var new_linktitle3      = linktitle3_input.val();
            
            var view         = this;
            var links        = [];

	     //falls url=embedcode (plus optional Quellenangaben): aufschlüsseln
	     

	     //falls embedcode mit iFrame
	     if (new_url.match('<iframe')){
		if (new_url.match('height')){
		    var begin_height = parseInt(new_url.indexOf('height')) + 8; 
		    var height_tmp = new_url.substr(begin_height, 6);
		    var end_height_rel = parseInt(height_tmp.indexOf('"'));
		    var end_height = begin_height + end_height_rel;
		    var height = new_url.slice(begin_height, end_height);
		    new_height = height;
		}
		
		if (new_url.match('<a href=')){
		    var link_part = new_url.split('</iframe>')[1];
		    var link_array = link_part.split('</a>');
			
			for (var i=0; i<link_array.length-1; i++) {	
				links[i] = [];
		    		var begin_link = parseInt(link_array[i].indexOf('<a href')) + 9; 
		    		var link_tmp = link_array[i].split('href="', 2)[1];
		    		var end_link_rel = parseInt(link_tmp.indexOf('"'));
		    		var end_link = begin_link + end_link_rel;
		    		var link = link_array[i].slice(begin_link, end_link);
				var title = link_array[i].split('">' ,2)[1];
		    		links[i]["href"] = link;
				links[i]["title"] = title;
			}

		}
		if (new_url.match('src')){
		    var begin_src = parseInt(new_url.indexOf('src')) + 5; 
		    var src_tmp = new_url.split('src="', 2)[1];
		    var end_src_rel = parseInt(src_tmp.indexOf('"'));
		    var end_src = begin_src + end_src_rel;
		    var src = new_url.slice(begin_src, end_src);

		    new_url = src;
		}
	    }

	     if(!(parseInt(new_height) > 5)){
		   new_height = 400;
	     }

	     if(links.length == 3){
	     	helper
                .callHandler(this.model.id, "save", {url: new_url, height: new_height, 
							  href1: links[0]["href"], linktitle1: links[0]["title"],
							  href2: links[1]["href"], linktitle2: links[1]["title"],
							  href3: links[2]["href"], linktitle3: links[2]["title"]})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        view.switchBack();
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    });
	     } else

            helper
                .callHandler(this.model.id, "save", {url: new_url, height: new_height,
                                                          href1: new_href1, linktitle1: new_linktitle1,
							  href2: new_href2, linktitle2: new_linktitle2,
							  href3: new_href3, linktitle3: new_linktitle3})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        view.switchBack();
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});

define(['assets/js/student_view'], function (StudentView) {
    'use strict';

    return StudentView.extend({
        initialize: function (options) {
        },

        render: function () {
            return this;
        },

        postRender: function () {
            var filterTag = '#block-'+this.model.id;
            var $container = this.$el.find('div.blubber-stream');
            var assetsBaseUrl = $container.attr("data-assets-base-url");

            this.loadStylesheets([assetsBaseUrl+'stylesheets/blubberforum.css']);

            this.loadScripts(
                [
                    assetsBaseUrl+'javascripts/autoresize.jquery.min.js',
                    assetsBaseUrl+'javascripts/blubber.js',
                    assetsBaseUrl+'javascripts/formdata.js'
                ],
                function () {
                    jQuery.get($container.attr('data-stream-url'), function (data) {
                        $container.html(data);
                        $container.removeClass('loading');

                        // ensure that the filter tag is set properly before
                        // creating the new Blubber posting
                        var originalNewPosting = STUDIP.Blubber.newPosting;
                        STUDIP.Blubber.newPosting = function () {
                            var field = jQuery('#new_posting');

                            if (field.val().indexOf(filterTag) == -1) {
                                field.val(field.val()+' '+filterTag);
                            }

                            originalNewPosting();
                        };

                        // hide tags used to filter Blubber threads
                        jQuery('a.hashtag', $container).filter(function () {
                            return jQuery(this).text() == filterTag;
                        }).hide();
                    });
                }
            );

            return this;
        },

        loadStylesheets: function (stylesheets) {
            for (var i = 0; i < stylesheets.length; i++) {
                if (document.createStyleSheet) {
                    document.createStyleSheet(stylesheets[i]);
                } else {
                    var attributes = {
                        'rel': 'stylesheet',
                        'href': stylesheets[i],
                        'type': 'text/css',
                        'media': 'screen'
                    };
                    jQuery('<link>', attributes).appendTo('head');
                }
            }
        },

        loadScripts: function (scripts, callback) {
            var remaining = scripts.length;

            for (var i = 0; i < scripts.length; i++) {
                jQuery.getScript(scripts[i], function () {
                    remaining--;

                    if (remaining == 0) {
                        callback();
                    }
                });
            }
        }
    });
});

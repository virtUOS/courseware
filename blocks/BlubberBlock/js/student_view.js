import jQuery from 'jquery'
import StudentView from 'js/student_view'

export default StudentView.extend({
  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
    var filterTag = '#block-' + this.model.id;
    var $container = this.$el.find('div.blubber-stream');
    var assetsBaseUrl = $container.attr('data-assets-base-url');

    // hide tags used to filter Blubber threads
    var filterHashTags = function () {
      jQuery('a.hashtag', $container).filter(function () {
        return jQuery(this).text() == filterTag;
      }).hide();
    };

    this.loadStylesheets([ assetsBaseUrl + 'stylesheets/blubberforum.css' ]);

    this.loadScripts(
      [
        assetsBaseUrl + 'javascripts/autoresize.jquery.min.js',
        assetsBaseUrl + 'javascripts/blubber.js',
        assetsBaseUrl + 'javascripts/formdata.js'
      ],
      function () {
        // ensure that the filter tag is set properly before
        // creating the new Blubber posting
        jQuery(document).ajaxSend(function (event, xhr, options) {
          if (options.type != 'POST') {
            return;
          }

          if (options.url.substr(-12) != '/new_posting') {
            return;
          }

          if (options.data.indexOf(filterTag.replace(/#/, '%23')) == -1) {
            options.data = options.data + '+' + filterTag.replace(/#/, '%23');
          }
        });

        jQuery.get($container.attr('data-stream-url'), function (data) {
          $container.html(data);
          $container.removeClass('loading');

          // hide filter hash tags of newly created postings
          var originalInsertThread = window.STUDIP.Blubber.insertThread;
          window.STUDIP.Blubber.insertThread = function (postingId, date, contents) {
            originalInsertThread(postingId, date, contents);
            filterHashTags();
          };

          filterHashTags();

          var $newPostingInput = jQuery('#new_posting');
          $newPostingInput.after('<input type="submit" value="Absenden">');
          $newPostingInput.next().click(function () {
            var event = jQuery.Event('keydown');
            event.keyCode = 13;
            event.which = 13;
            jQuery('#new_posting').trigger(event);
          });
        });
      }
    );

    return this;
  },

  loadStylesheets(stylesheets) {
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

  loadScripts(scripts, callback) {
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

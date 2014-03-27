define(['assets/js/author_view', 'assets/js/url', './utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            'keyup input': "onKeyup",
            "click button[name=cancel]": "switchBack"
        },
        initialize: function (options) {
            // timeoutId is needed by the 'keyup input' event
            this.timeoutId = setTimeout(function () {
                // TODO remove setTimeout call
                // calling normalizeIFrame after a timeout is just a workaround
                // since the IFrame is not initialized yet when this function
                // is called                
                Utils.normalizeIFrame(this);
            }, 1000);
        },
        render: function() { return this; },
        onKeyup: function (event) {
            var view = this;

            view.$('p').text('...am ändern.');
            clearTimeout(this.timeoutId);

            this.timeoutId = setTimeout(function () {
                var url = view.$('input').val();
                Utils.normalizeIFrame(this, url);

                // save data
                view.$('p').text('Speichere Änderungen...');
                helper
                    .callHandler(view.model.id, 'save', { url: url })
                    .then(function () { // success
                        view.$('p').text('Änderungen wurden gespeichert.');
                    }, function () {    // error
                        view.$('p').text('Fehler beim speichern.');
                    });
            }, 1000);
        }

    });
});

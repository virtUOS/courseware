define(['assets/js/author_view', 'assets/js/url', './utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            'keyup input': "onKeyup",
            "click button[name=cancel]": "switchBack"
        },
        initialize: function (options) {},
        render: function() { return this; },
        postRender: function () {
            Utils.normalizeIFrame(this);
        },
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

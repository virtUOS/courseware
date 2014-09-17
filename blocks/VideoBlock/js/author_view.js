define(['assets/js/author_view', 'assets/js/url', 'utils'], function (
    AuthorView, helper, Utils
) {
    'use strict';
    return AuthorView.extend({
        events: {
            'keyup input': "onKeyup",
            'click button[name=save]': 'saveVideo',
            "click button[name=cancel]": "switchBack"
        },
        initialize: function (options) {},
        render: function() { return this; },
        postRender: function () {
            Utils.normalizeIFrame(this);
        },
        onKeyup: function () {
            var
            view = this,
            status = view.$('.status');

            status.text('Lade Video...');
            clearTimeout(this.timeoutId);

            this.timeoutId = setTimeout(function () {
                var url = view.$('input').val();
                Utils.normalizeIFrame(view, url);
            }, 1000);
        },

        saveVideo: function () {
            var view = this;
            var url = view.$('input').val();
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

define([], function () {
    'use strict';

    return function ($element, itemSelector, contentCallback) {
        return $element.tooltip({
            items: itemSelector,
            content: function() {
                if (contentCallback) {
                    return _.escape(contentCallback.call(this));
                }

                return _.escape(jQuery(this).attr("data-title"));
            },
            show: false,
            hide: false,
            position: {
                my: "center bottom-10",
                at: "center top",
                using: function (position, feedback) {
                    jQuery(this).css(position);
                    jQuery("<div/>")
                        .addClass(["arrow", feedback.vertical, feedback.horizontal].join(" "))
                        .appendTo(this);
                }
            }
        });
    };
});

import $ from 'jquery'
import _ from 'underscore'

export default function ($element, itemSelector, contentCallback) {
  return $element.tooltip({
    items: itemSelector,
    content() {
      if (contentCallback) {
        return _.escape(contentCallback.call(this));
      }

      return _.escape($(this).attr('data-title'));
    },
    show: false,
    hide: false,
    position: {
      my: 'center bottom-10',
      at: 'center top',
      using: function (position, feedback) {
        $(this).css(position);
        $('<div/>')
          .addClass([ 'arrow', feedback.vertical, feedback.horizontal ].join(' '))
          .appendTo(this);
      }
    }
  });
}

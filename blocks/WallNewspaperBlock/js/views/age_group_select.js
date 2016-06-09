define(['backbone', 'underscore', 'assets/js/templates'],
function (Backbone, _, templates) {

    var AgeGroupSelectView = Backbone.View.extend({
        tagName: 'div',

        className: 'age-group-select',

        events: {
            'click > .title': 'onSelect'
        },

        initialize: function (options) {
            this.dispatcher = options.dispatcher;
            this.listenTo(this.model, 'change', this.render);
        },

        render: function () {
            var data = _.extend({}, this.model.attributes, { $thoroughlyComplete: this.model.isThoroughlyComplete() });
            this.$el.html(templates('WallNewspaperBlock', 'age_group_select_view', data));
            return this;
        },

        remove: function() {
            return Backbone.View.prototype.remove.call(this);
        },

        onSelect: function () {
            this.dispatcher.selectAgeGroup(this.model);
        }
    });

    return AgeGroupSelectView;
});

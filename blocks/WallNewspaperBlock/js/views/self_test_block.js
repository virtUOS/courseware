define(['backbone', 'assets/js/block_model', 'assets/js/block_types'], function (Backbone, BlockModel, block_types) {

    var SelfTestBlockView = Backbone.View.extend({
        tagName: 'article',

        className: 'self-test-block-view',

        attributes: {
        },

        initialize: function (options) {
            this.dispatcher = options.dispatcher;
            this.topics = options.topics;
            this.listenTo(this.topics, 'change:selectedSelfTest', this.render);
        },

        remove: function () {
            this.removeBlockView();
            return Backbone.View.prototype.remove.call(this);
        },

        _blockView: null,

        removeBlockView: function () {
            if (this._blockView) {
                this._blockView.off('TestBlock:graded');
                this._blockView.remove();
                this._blockView = null;
            }
        },

        render: function () {
            this.removeBlockView();

            if (this.topics.selectedSelfTest) {
                var selectedAgeGroup = this.topics.get(this.topics.selectedSelfTest),
                    block_id = selectedAgeGroup.get('selfTest').id,
                    model  = new BlockModel({ id: block_id, type: 'TestBlock' });

                var block_container = Backbone.$('<section class="TestBlock"></section>').appendTo(this.$el);
                this._blockView = block_types
                    .findByName(model.get('type'))
                    .createView('student', { el: block_container, model: model });

                this._blockView.on('TestBlock:graded', function (grade) {
                    if (grade === 1) {
                        this.dispatcher.passSelfTest();
                    }
                }, this);

                this._blockView.renderServerSide();
            }

            return this;
        }
    });

    return SelfTestBlockView;
});

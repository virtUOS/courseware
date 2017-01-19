define(['backbone', 'underscore', 'assets/js/templates', 'utils'],
function (Backbone, _, templates, Utils) {

    var VIDEO_TYPES = [
        {
            type: 'url',
            placeholder: 'URL'
        },
        {
            type: 'matterhorn',
            placeholder: 'URL'
        },
        {
            type: 'youtube',
            placeholder: 'YouTube ID'
        },
        {
            type: 'dfb',
            placeholder: 'DFB-TV-ID (z.B. 11019)'
        }
    ];


    var ContentView = Backbone.View.extend({
        tagName: 'article',

        className: 'content',

        events: {
            'click  nav  .edit': 'onClickEdit',
            'click  form [name=cancel]': 'onClickCancel',
            'change select': 'onChangeVideoType',
            'submit form': 'onSaveContent'
        },

        initialize: function (options) {
            this.dispatcher = options.dispatcher;

            this.editing = false;
            this.listenTo(this.model, 'change', this.render);
            this.listenTo(this.dispatcher, 'START_EDITING', this.toggleEditing.bind(this, true));
            this.listenTo(this.dispatcher, 'STOP_EDITING',  this.toggleEditing.bind(this, false));
        },

        remove: function() {
            return Backbone.View.prototype.remove.call(this);
        },

        render: function () {
            var video_type = Utils.getVideoType(this.model.get('video') || ''),
                types = _.map(VIDEO_TYPES, function (type) { return _.extend({}, type, { selected: video_type === type.type }); }),
                placeholder = _.findWhere(VIDEO_TYPES, { type: video_type }).placeholder,
                data = _.extend({}, this.model.attributes, { editing: this.editing, types: types, placeholder: placeholder } );

            this.$el.html(templates('WallNewspaperBlock', 'content_view', data));

            return this;
        },

        toggleEditing: function (editing) {
            this.editing = editing;
            this.render();
        },

        onChangeVideoType: function () {
            this.$('input[name=video_url]').attr('placeholder', _.findWhere(VIDEO_TYPES, { type: this.$('select').val() }).placeholder);
        },

        onSaveContent: function (event) {
            event.preventDefault();

            var $form = this.$('form'),
                video_type = $form.find('select[name=video_type]').val(),
                video_url = $form.find('input[name=video_url]').val(),
                text = $form.find('textarea[name=text]').val();

            this.dispatcher.editTopicRequest(this.model, { video_type: video_type, video_url: video_url, text: text });
            this.dispatcher.stopEditing();

            return false;
        },

        onClickEdit: function () {
            this.dispatcher.startEditing();
        },

        onClickCancel: function () {
            this.dispatcher.stopEditing();
        }
    });

    return ContentView;
});

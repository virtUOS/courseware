import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'click button[name=additem]': 'addItem',
        'click button[name=removeitem]': 'removeitem'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        var $view = this;
        var $type = $view.$(".cw-chart-stored-type").val();
        var $content = $view.$(".cw-chart-stored-content").val();
        if ($type != '') {
            $view.$('.cw-chart-type option[value="'+$type+'"]').prop('selected', true);
        }
        if ($content != '') {
            var content_json = JSON.parse($content);
            var $wrapper = $view.$('.cw-chart-item-datasets-wrapper');
            for (var i in content_json) {
                var $item = $view.$('.cw-chart-item-dataset-default').clone();
                var $title = $item.find('.cw-chart-item-title');
                $title.text($title.text()+' '+(parseInt(i)+1));
                $item.find('input[name="cw-chart-item-value"]').val(content_json[i].value);
                $item.find('input[name="cw-chart-item-label"]').val(content_json[i].label);
                $item.find('.cw-chart-item-color option[value="'+content_json[i].color+'"]').prop('selected', true);
                $item.removeClass('cw-chart-item-dataset-default');
                $($item).appendTo($wrapper);
            }
        } else {
            $view.addItem();
        }
        $view.$('.cw-chart-item-dataset-default').hide();

        return this;
    },

    onNavigate(event) {
        if (!$('section .block-content button[name=save]').length) {
            return;
        }
        if(event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },

    onModeSwitch(toView, event) {
        if (toView != 'student') {
            return;
        }
        // the user already switched back (i.e. the is not visible)
        if (!this.$el.is(':visible')) {
            return;
        }
        // another listener already handled the user's feedback
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
    },

    onSave(event) {
        var $view = this;
        var $chart_type = $view.$(".cw-chart-type").val();
        var $chart_label = $view.$(".cw-chart-label").val();

        var $datasets = $view.$('.cw-chart-item-dataset');
        var $chart_content = new Array();
        $datasets.each(function( index ) {
            if ($(this).hasClass('cw-chart-item-dataset-default')){
                return true;
            }
            var $chart_value = $(this).find(".cw-chart-item-value").val();
            var $chart_label = $(this).find(".cw-chart-item-label").val();
            var $chart_color = $(this).find(".cw-chart-item-color").val();
            $chart_content.push({'value': $chart_value, 'label': $chart_label, 'color': $chart_color});
        });
        $chart_content = JSON.stringify($chart_content);

        helper
        .callHandler(this.model.id, 'save', {
              chart_type : $chart_type,
              chart_label : $chart_label,
              chart_content : $chart_content
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                $view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    },

    addItem() {
        var $view = this;
        var $wrapper = $view.$('.cw-chart-item-datasets-wrapper');
        var $item = $view.$('.cw-chart-item-dataset-default').clone();
        var $content = $view.$(".cw-chart-stored-content").val();
        var i = $view.$('.cw-chart-item-dataset').not('.cw-chart-item-dataset-default').length;
        var $title = $item.find('.cw-chart-item-title');
        $title.text($title.text()+' '+(parseInt(i)+1));
        $item.removeClass('cw-chart-item-dataset-default');
        $($item).appendTo($wrapper).show();
    },

    removeitem(event) {
        var $view = this;
        var fieldset = this.$(event.target).closest('.cw-chart-item-dataset');
        fieldset.remove();
        var $title = $view.$('.cw-chart-item-dataset-default .cw-chart-item-title').text();
        var $datasets = $view.$('.cw-chart-item-dataset').not('.cw-chart-item-dataset-default');
        $.each($datasets, function(i){
            $(this).find('.cw-chart-item-title').text($title+' '+(parseInt(i)+1));
        });
    }
});

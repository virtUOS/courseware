import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
        'click button[name=reset-exercise]': function (event) {
            var $form = this.$(event.target).closest('form'),
            view = this,
            $exercise_index = $form.find('input[name="exercise_index"]').val(),
            $block = this.$el.parent();

            if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                helper.callHandler(this.model.id, 'exercise_reset', $form.serialize())
                .then(function () {
                    return view.renderServerSide();
                }).catch(function () {
                    console.log('failed to reset the exercise');
                }).then(function () {
                    $block.find('.exercise').hide();
                    $block.find('#exercise' + $exercise_index).show();
                });
            }

          return false;
        },

        'click button[name=submit-exercise]': function (event) {
            var $form = this.$(event.target).closest('form'),
            view = this,
            $exercise_index = $form.find('input[name="exercise_index"]').val(),
            $block = this.$el.parent();

            helper.callHandler(this.model.id, 'exercise_submit', $form.serialize())
            .then(function (resp) {
                if(resp.is_nobody) {
                    var $ex =view.$("#exercise"+resp.exercise_index);
                    $ex.find(".cw-test-content").first().html('<form class="studip_form"><fieldset><legend>'+resp.title+'</legend>'+resp.solution+'</fieldset></form>');
                } else {
                    return view.renderServerSide();
                }
            }).then(function () {
                $block.find('.exercise').hide();
                $block.find('#exercise' + $exercise_index).show();
                $block.find('.submitinfo').slideDown(250).delay(1500).slideUp(250);
            })
            .catch(function () {
                console.log('failed to store the solution');
            });

            return false;
        },

        'click button[name=exercisenav]': function (event) {
            var options = $.parseJSON(this.$(event.target).attr('button-data')),
            $num = parseInt(options.id),
            $block = this.$el.parent();

            if (options.direction == 'next') {
                $num++;
            } else {
                $num--;
            }
            if ($num > parseInt(options.numexes, 10)) {
                $num = 1;
            }
            if ($num < 1) {
                $num = parseInt(options.numexes, 10);
            }
            $block.find('.exercise').hide();
            var $ex = $block.find('#exercise' + $num).show();
            if ($ex.find("input[name=exercise_type]").val() == 'tb_exercise') {
                $ex.find('table.default').hide();
            }
            $(window).trigger('resize');
        },

        'click button[name=exercise-hint-button]': function (event) {
            $('#exercise-hint-' + this.$(event.target).attr('exercise-data')).toggle('slow');
            if (this.$(event.target).hasClass("showing")) {
                this.$(event.target).removeClass("showing");
                this.$(event.target).html("Hinweis anzeigen");
            } else {
                this.$(event.target).addClass("showing");
                this.$(event.target).html("Hinweis ausblenden");
            }
        }
    },

    initialize() {
    },

    render() {
        return this;
    },

    postRender() {
    }

});

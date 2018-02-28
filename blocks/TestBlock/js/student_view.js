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
            var $this_block = this; // We need 'this' in the handler for postRender functions

            if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                helper.callHandler(this.model.id, 'exercise_reset', $form.serialize())
                .then(function () {
                    return view.renderServerSide();
                }).catch(function () {
                    console.log('failed to reset the exercise');
                }).then(function () {
                    $block.find('.exercise').hide();
                    $this_block.postRenderExercise($block.find('#exercise' + $exercise_index).show());
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
                view.postRenderExercise($block.find('#exercise' + $exercise_index).show());
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
            this.postRenderExercise($block.find('#exercise' + $num).show());
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
        var $form = this.$('.cw-test-content form');

        $form.each(function () {
            var $exercise_type = $(this).find('input[name="exercise_type"]').val();
            var $user_answers = $(this).find('input[name="user_answers_string"]').val();
            var $thisform = $(this);
            if (!($user_answers)) {
                return; //break the loop
            } else {
                switch ($exercise_type) {
                    case 'sc_exercise':
                    case 'yn_exercise':
                        var $radioid = $thisform.find('label:contains(' + $user_answers + ')').attr('for');
                        var $radio = $('#' + $radioid);
                        $radio.attr('checked', 'checked');
                        break;
                    case 'mc_exercise':
                        var $mc_answers = $user_answers.split(',');
                        $.each($mc_answers, function (index, value) {
                            var $checkboxid = $thisform.find('label:contains(' + value + ')').attr('for');
                            var $checkbox = $('#' + $checkboxid);
                            $checkbox.attr('checked', 'checked');
                        });
                        break;
                    case 'tb_exercise':
                        var $textbox = $thisform.find('textarea');
                        $textbox.val($user_answers);
                        break;
                    case 'lt_exercise':
                        var $textfield = $thisform.find('input[type="text"]');
                        $textfield.val($user_answers);
                        break;
                    default:
                        return false;
                }
            }
        });
        // search for rh_lists
        var $firstExercise = this.$('ul.exercise').eq(0);
        this.postRenderExercise($firstExercise);
        // re-format LaTeX stuff
        window.MathJax.Hub.Queue([ 'Typeset', window.MathJax.Hub, this.el ]);
    },

    postRenderExercise($exerciseElement) {
        // für Zuordnungsaufgaben
        $exerciseElement.find('.rh_list').each(function (index, rhListEl) {
            createSortable($(rhListEl));
        });
        // helper functions
        function createSortable($element) {
            $element.sortable({
                axis: 'y',
                containment: 'parent',
                item: '> .rh_item',
                tolerance: 'pointer',
                update: rh_move_choice
            });
        }
        function rh_move_choice(event, ui) {
            jQuery(this).children().each(function(i) {
                jQuery(this).find('input').val(i);
            });
        }
    }
});

define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';

    var counter = 0;

    function updateSizes(tableElement, onResizeEvent) {
        var columns = tableElement.find('#rh_labels, #rh_list');
        var items = tableElement.find('.rh_label, .rh_item');
        var maxHeight = 0;

        if (onResizeEvent) {
            // reset to default sizes
            items.css('height', 'auto');
            columns.css('width', 'auto');
        }

        items.each(function(i, item) {
            maxHeight = Math.max(maxHeight, jQuery(item).height());
        });

        // set to fixes sizes again
        items.height(maxHeight);
        columns.width(function(index, width) {
            return width;
        });
    }

    return StudentView.extend({
        events: {
            'click button[name=reset-exercise]': function (event) {
                var $form = this.$(event.target).closest('form'),
                    view = this,
                    $exercise_index = $form.find("input[name='exercise_index']").val(),
                    $block = this.$el.parent();

                var $this_block = this; // We need 'this' in the handler for postRender functions

                if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                    helper.callHandler(this.model.id, 'exercise_reset', $form.serialize())
                        .then(
                            function () {
                                return view.renderServerSide();
                            },
                            function () {
                                console.log('failed to reset the exercise');
                            }
                        )
                        .then(function () {
                            $block.find('.exercise').hide();
                            $this_block.postRenderExercise($block.find('#exercise' + $exercise_index).show());
                        })
                        .done();
                }
                return false;
            },

            'click button[name=submit-exercise]': function (event) {
                var $form = this.$(event.target).closest('form'),
                    view = this,
                    $exercise_index = $form.find("input[name='exercise_index']").val(),
                    $block = this.$el.parent();



                helper.callHandler(this.model.id, 'exercise_submit', $form.serialize())
                    .then(
                        function () {
                            return view.renderServerSide();
                        },
                        function () {
                            console.log('failed to store the solution');
                        }
                    )
                    .then(function () {
                        $block.find('.exercise').hide();
                        view.postRenderExercise($block.find('#exercise' + $exercise_index).show());
                        $block.find(".submitinfo").slideDown(250).delay(1500).slideUp(250);
                    })
                    .done();

                return false;
            },

            'click button[name=exercisenav]': function (event){
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
                this.postRenderExercise($block.find('#exercise'+$num).show());
            },

            'click button[name=exercise-hint-button]': function (event) {
                    $("#exercise-hint-"+this.$(event.target).attr("exercise-data")).toggle("slow");
            }
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var view = this;
            var $form = this.$('.exercise-content form');

            $form.each(function () {
                    var $exercise_type = $(this).find('input[name="exercise_type"]').val();
                    var $user_answers = $(this).find('input[name="user_answers_string"]').val();
                    var $thisform = $(this);
                    if (!($user_answers)){
                         return; //break the loop
                    }
                    else {
                        switch ($exercise_type) {
                            case "sc_exercise":
                            case "yn_exercise":
                                var $radioid = $thisform.find('label:contains('+$user_answers+')').attr('for');
                                var $radio = $('#'+$radioid);
                                $radio.attr("checked","checked");
                                break;

                            case "mc_exercise":
                                var $mc_answers = $user_answers.split(",");
                                $.each($mc_answers, function(index, value) {
                                    var $checkboxid = $thisform.find('label:contains('+value+')').attr('for');
                                    var $checkbox = $('#'+$checkboxid);
                                    $checkbox.attr("checked","checked");
                                });
                                break;
                            case "tb_exercise":
                                var $textbox = $thisform.find('textarea');
                                $textbox.val($user_answers);
                                break;

                            case "lt_exercise":
                                var $textfield = $thisform.find('input[type="text"]');
                                $textfield.val($user_answers);
                                break;

                            default:
                                return false;
                        }
                    }
            });

            /*
            var fixAnswersHeight = function (labels, answers) {
                for (var i = 0; i < labels.length && i < answers.length; i++) {
                    var answer = answers.eq(i);
                    answer.css({height: 'auto'});
                    var label = labels.eq(i);
                    label.css({height: 'auto'});
                    var labelHeight = label.height();
                    var answerHeight = answer.height();

                    if (labelHeight > answerHeight) {
                        answer.css({height: labelHeight});
                    } else if (labelHeight < answerHeight) {
                        label.css({height: answerHeight});
                    }
                }
            };

            this.$('ul.exercise_answers').each(function () {
                var $sortableAnswers = $(this),
                    $sortableLabels = $sortableAnswers.parent().find('ul.matching_exercise.labels');

                fixAnswersHeight($sortableAnswers.find('li'), $sortableLabels.find('li'));

                $sortableAnswers.sortable({
                    axis: 'y',
                    cursor: 'move',
                    forcePlaceholderSize: true,
                    change: function () {
                        fixAnswersHeight($sortableAnswers.find('li'), $sortableLabels.find('li'));
                    },
                    update: function () {
                        view.moveChoice($sortableAnswers);
                        fixAnswersHeight($sortableAnswers.find('li'), $sortableLabels.find('li'));
                    }
                });
            });
            */

            // search for rh_lists
            var $firstExercise = this.$('ul.exercise').eq(0);
            this.postRenderExercise($firstExercise);

            // re-format LaTeX stuff
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
        },


        postRenderExercise: function ($exerciseElement) {

            // für Zuordnungsaufgaben
            $exerciseElement.find('#rh_list').each(function (index, rhListEl) {
                createSortable(jQuery(rhListEl));
            });




            // helper functions

            function createSortable($element)
            {
                updateSizes($element.closest('table'));

                $element.sortable({
                    axis: 'y',
                    containment: 'parent',
                    item: '> .rh_item',
                    tolerance: 'pointer',
                    update: moveChoice($element)
                });
            }

            function moveChoice($element)
            {
                return function (event) {
                    var items = $element.sortable('toArray');

                    for (var i = 0; i < items.length; ++i) {
                        $element.find('#' + items[i] + ' > input').attr('value', i);
                    }
                }
            }

        },

        moveChoice: function ($sortableAnswers) {
            var items = $sortableAnswers.sortable('toArray');
            var $inputs = $sortableAnswers.find('input');

            for (var i = 0; i < items.length; i++) {
                $inputs.eq(i).val(i);
            }
        }
    });
});

define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';

    return StudentView.extend({
        events: {
            'click button[name=reset-exercise]': function (event) {
                var $form = this.$(event.target).closest('form');
                var view = this;
                var $exercise_id = $form.find("input[name='exercise_id']").val();
                if (confirm('Soll die Antwort zurückgesetzt werden?')) {
                    helper
                        .callHandler(this.model.id, 'exercise_reset', $form.serialize())
                        .then(
                            function () {
                                $.when(view.renderServerSide()).done(function(){
                                   $('.exercise').hide();
                                   $('#exercise'+$exercise_id).show();
                                });                                
                            },
                            function () {
                                console.log('failed to reset the exercise');
                            }
                        ).done();
                    
                }

                return false;
            },
            'click button[name=submit-exercise]': function (event) {
                var $form = this.$(event.target).closest('form');
                var view = this;
                var $exercise_id = $form.find("input[name='exercise_id']").val();
                helper
                    .callHandler(this.model.id, 'exercise_submit', $form.serialize())
                    .then(
                        function () {
                            $.when(view.renderServerSide()).done(function(){
                                   $('.exercise').hide();
                                   $('#exercise'+$exercise_id).show();
                            });  
                        },
                        function () {
                            console.log('failed to store the solution');
                        }
                    ).done();
                
                return false;
            },
            'click button[name=exercisenav]': function (event){
                    var options = $.parseJSON(this.$(event.target).attr('button-data'));
                    var $num =parseInt(options.id);
                    if(options.direction == "next") $num++;
                    else $num--;
                    if ($num > parseInt(options.numexes)) $num = 1;
                    if ($num < 1) $num = parseInt(options.numexes); 
                    $('.exercise').hide();
                    $('#exercise'+$num).show();
            }
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var view = this;
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
            jQuery('ul.exercise_answers', this.$el).each(function () {
                var $sortableAnswers = $(this);
                var $sortableLabels = $('ul.matching_exercise.labels', $(this).parent());
                fixAnswersHeight($('li', $sortableAnswers), $('li', $sortableLabels));
                $sortableAnswers.sortable({
                    axis: 'y',
                    containment: $sortableAnswers,
                    tolerance: 'pointer',
                    update: function () {
                        view.moveChoice($sortableAnswers);
                        fixAnswersHeight($('li', $sortableAnswers), $('li', $sortableLabels));
                    },
                    sort: function (event, ui) {
                        // this workaround is needed, otherwise, sortable items
                        // would jump when the user scrolled down before sorting
                        ui.helper.css({
                            top : ui.position.top + $(window).scrollTop() + 'px'
                        });
                    }
                });
            });
            
        },

        moveChoice: function ($sortableAnswers) {
            var items = $sortableAnswers.sortable('toArray');
            var $inputs = jQuery('input', $sortableAnswers);

            for (var i = 0; i < items.length; i++) {
                $inputs.eq(i).val(i);
            }
        }
    });
});

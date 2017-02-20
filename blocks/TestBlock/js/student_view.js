define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';
   

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
                        function (response) {
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

            'click button[name=print-exercise]': function (event) {
                var $cid = window.location.href.slice(window.location.href.indexOf('cid') + 4).split('&')[0];
                var $assignment_id = $("input[name='assignment_id']").val();
                var $url = window.location.href.split('courseware')[0];
                $url = $url+"vipsplugin/show?cid="+$cid+"&action=export&subaction=pdf_assignment&assignment="+$assignment_id;
                window.open($url, '_blank');
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
            
            this.$(".exercise").each( function() {
                //id's der aufgaben ändern und nur bestimmte anzahl anzeigen ... random verwenden!
            });
            
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

            // search for rh_lists
            var $firstExercise = this.$('ul.exercise').eq(0);
            this.postRenderExercise($firstExercise);

            // re-format LaTeX stuff
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
        },


        postRenderExercise: function ($exerciseElement) {
        
            function randomiseDraggables() {
                var $parent = $(".rh-catalog");
                var $divs = $parent.children();
                $divs.each(function() {
                    var $postop = 0;
                    var $posleft = 0;
                    $(this).css({ 'position': 'relative', 'top': $postop+"px", 'left': $posleft+"px"});
                    $(this).attr('postop' , $postop);
                    $(this).attr('posleft' , $posleft);
                    });

                while ($divs.length) {
                    $parent.append($divs.splice(Math.floor(Math.random() * $divs.length), 1)[0]);
                }
                
            }

            randomiseDraggables();
            
            var $height = 0; 
        
            $(".rh-catalog").children().each(function(){

                if ($(this).outerHeight() > $height) {
                    $height = $(this).outerHeight();
                }
            });
            
            $height += 10; 
            
            $(".rh-list-item").each( function(index) {
                $(this).height($height)
            });
            
            $(".rh-catalog").height($(".rh-cart").height()-22);
            
            $exerciseElement.find(".rh-catalog-item").draggable({
                start: function( event, ui ) {
                    $(this).css("z-index", 10);
                },
                stop: function( event, ui ) {
                    $(this).css("z-index", 0);
                },
                revert: "invalid"
            }).find("input").attr("value", -1);

            $(".rh-cart-item").each( function(index) {
                $(this).height($height)
                $(this).droppable({
                    drop: function(event, ui) {
                        var pastDraggable = $(this).attr('pastdraggable');
                        var currentDraggable =  $(ui.draggable).attr('id');

                        if (pastDraggable != "" && pastDraggable != currentDraggable) {
                            $("#" + pastDraggable).animate({left: $("#" + pastDraggable).attr('posleft'), top: $("#" + pastDraggable).attr('postop')},"slow");
                            $("#" + pastDraggable).find("input").attr("value", -1);
                        }

                        $(this).attr('pastdraggable', currentDraggable);
                        $(ui.draggable).find("input").attr("value", $(this).index());
                        
                        $(this).animate({backgroundColor: "#007f4b"}, 550);
                        $(this).animate({backgroundColor: "#fff", borderColor: "#eee"}, 250);
                        
                        $(this).find(".rh-cart-item-answer").hide();
                        $(".rh-cart-item").each( function(index) { 
                            if ($(this).attr('pastdraggable') === "") $(this).find(".rh-cart-item-answer").show();
                        });

                    },
                    out: function(event, ui) {
                        if($(ui.draggable).attr("id") == $(this).attr('pastdraggable')) {
                            $(this).attr('pastdraggable', '');
                            $(ui.draggable).find("input").attr("value", -1);
                        }
                        $(this).css("border-color", "#eee");
                    },
                    over : function(event, ui) {
                        $(this).css("border-color", "#007f4b");
                    }
                });
            });
             $(".rh-catalog").droppable({
                drop: function(event, ui) {
                        if($(ui.draggable).attr("id") == $(this).attr('pastdraggable')) {
                            $(this).attr('pastdraggable', '');
                            $(ui.draggable).find("input").attr("value", -1);
                        }
                        $(ui.draggable).animate({left: $(ui.draggable).attr('posleft'), top: $(ui.draggable).attr('postop')},"slow");
                        $(this).animate({backgroundColor: "#007f4b"}, 550);
                        $(this).animate({backgroundColor: "#fff", borderColor: "#eee"}, 250);
                        
                        $(".rh-cart-item").each( function(index) { 
                            if ($(this).attr('pastdraggable') === "") $(this).find(".rh-cart-item-answer").show();
                        });
                        
                },
                out: function(event, ui) {
                    $(this).css("border-color", "#eee");
                },
                over : function(event, ui) {
                        $(this).css("border-color", "#007f4b");
                }
            });
        }

    });
});

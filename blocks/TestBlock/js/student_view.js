import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'
import ajax from 'js/url'
import Config from 'js/courseware-config'

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
                    $(window).trigger('resize');
                });
            }

          return false;
        },

        'click button[name=submit-exercise]': function (event) {
            var $form = this.$(event.target).closest('form'),
                view = this,
                $exercise_index = $form.find('input[name="exercise_index"]').val(),
                $block = this.$el.parent();

            var file = this.$('input[name="upload"]')[0].files[0]; //Files[0] = 1st file

            if (file != undefined) {
                var reader = new FileReader();
                reader.readAsDataURL(file);
                  reader.onloadend = function() {
                     let file_str = "&file="+reader.result+"&filename="+file.name+"&filesize="+file.size+"&filetype="+file.type;
                     helper.callHandler(view.model.id, 'exercise_submit', $form.serialize()+file_str)
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
                        $(window).trigger('resize');
                    })
                    .catch(function () {
                        console.log('failed to store the solution');
                    });
                 }
            } else {
                //helper.callHandler(this.model.id, 'exercise_submit', $form.serialize())
                helper.callHandler(this.model.id, 'exercise_submit', fd)
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
                    $(window).trigger('resize');
                })
                .catch(function () {
                    console.log('failed to store the solution');
                });
            }

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
        // TODO this code from vips.js should be called by vips.js
        if (this.$('.exercise').hasClass('vips14')) {
            this.$('.rh_list').sortable({
                item: '> .rh_item',
                tolerance: 'pointer',
                connectWith: '.rh_list',
                update: function(event, ui) {
                    if (ui.sender) {
                        ui.item.find('input').val(jQuery(this).data('group'));
                    }
                },
                over: function(event, ui) {
                    jQuery(this).addClass('hover');
                },
                out: function(event, ui) {
                    jQuery(this).removeClass('hover');
                },
                receive: function(event, ui) {
                    var sortable = jQuery(this);
                    var container = sortable.closest('tbody').find('.answer_container');
        
                    // default answer container can have more items
                    if (sortable.children().length > 1 && !sortable.is(container)) {
                        sortable.find('.rh_item').each(function(i) {
                            if (!ui.item.is(this)) {
                                jQuery(this).find('input').val(-1);
                                jQuery(this).detach().appendTo(container)
                                            .css('opacity', 0).animate({opacity: 1});
                            }
                        });
                    }
                }
            });
        } else {
            this.$('.rh_list').sortable({
                axis: 'y',
                containment: 'parent',
                item: '> .rh_item',
                tolerance: 'pointer',
                update: this.rh_move_choice
            });
        }

    },

    // TODO this code from vips.js should be called by vips.js
    rh_move_choice(event, ui)
    {
        jQuery(this).children().each(function(i) {
            jQuery(this).find('input').val(i);
        });
    }

});

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
            let $form = this.$(event.target).closest('form'),
                view = this,
                $exercise_index = $form.find('input[name="exercise_index"]').val(),
                $block = this.$el.parent();

            let files = [];
            if (this.$('input[name="upload"]').length > 0) {
                files.push(this.$('input[name="upload"]')[0].files[0]); //Files[0] = 1st file
            }
            if (this.$('input[name="upload[]"]').length > 0) {
                files = this.$('input[name="upload[]"]')[0].files; 
            }
            let files_array = [];
            let promises = [];
            if (files.length > 0) {
                $.each(files, function(index, file){
                    let filePromise = new Promise( resolve => {
                        let reader = new FileReader();
                        let file_data = {};
                        reader.readAsDataURL(file);
                        reader.onloadend = function(){
                            file_data.file = reader.result;
                            file_data.name = file.name;
                            file_data.size = file.size;
                            file_data.type = file.type;
                            files_array.push(file_data);
                            resolve(reader.result);
                        };
                    });
                    promises.push(filePromise);
                });
            }

            let indexed_array = {};
            $.each($form.serializeArray(), function () {
                if (this.name.indexOf('answer[') > -1) {
                    if (!('answer' in indexed_array)) {
                        indexed_array['answer'] = {};
                    }
                    let split = this.name.split('[');
                    let key = split[1].split(']')[0];
                    indexed_array['answer'][key] =  this.value;
                } else {
                    indexed_array[this.name] = this.value;
                }
            });

            Promise.all(promises).then(function() {
                indexed_array.files = files_array;
                let file_upload_failed = false;
                // console.log(indexed_array); return;
                helper.callHandler(view.model.id, 'exercise_submit', indexed_array)
                .then(function (resp) {
                    file_upload_failed = resp.file_upload_failed
                    if(resp.is_nobody) {
                        var $ex =view.$("#exercise"+resp.exercise_index);
                        $ex.find(".cw-test-content").first().html('<form class="studip_form"><fieldset><legend>'+resp.title+'</legend>'+resp.solution+'</fieldset></form>');
                    } else {
                        return view.renderServerSide();
                    }
                }).then(function () {
                    $block.find('.exercise').hide();
                    $block.find('#exercise' + $exercise_index).show();
                    $block.find('.submitinfo').slideDown(250).delay(2500).slideUp(250);
                    if(file_upload_failed) {
                        $block.find('.file-upload-failed').slideDown(250).delay(2500).slideUp(250);
                    }
                    $(window).trigger('resize');
                    view.resetUnsave();
                })
                .catch(function () {
                    console.log('failed to store the solution');
                });
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
            $block.find('#exercise' + $num).show();

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
        },

        'click input[type=checkbox]': 'setUnsave',
        'click input[type=radio]': 'setUnsave',
        'mouseup .rh_item': 'setUnsave',
        'change input[type=text]': 'setUnsave',
        'change textarea': 'setUnsave',
        'change select': 'setUnsave',
    },

    initialize() {
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    onNavigate(event) {
        if (this.unsavedInput.length > 0) {
            let view = this;
            let confirmText = '';
            if (this.unsavedInput.length > 2) {
                confirmText = 'Ihre Änderungen an mehr als 2 Frage wurden noch nicht abgeschickt.';
            } else {
                confirmText = 'Ihre Änderungen an ';
                $.each(this.unsavedInput, function(index, question){
                    confirmText += 'Frage ' + question.index + ' (' + question.title + ')';
                    if ((index == 0) && (view.unsavedInput.length == 2)) {confirmText += ' und '}
                });
                confirmText += ' wurden noch nicht abgeschickt.';
            }
            Backbone.trigger('preventnavigateto', !confirm(confirmText + ' Möchten Sie die Seite trotzdem verlassen?'));
        }
    },

    render() {
        return this;
    },

    postRender() {
        if (this.$('.numexes').val() == 1) {
            this.$('.exercisenavbutton').hide();
        }
        this.unsavedInput = [];
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
        $( ".rh_list" ).on( "sortchange", function( event, ui ) {} );

    },

    // TODO this code from vips.js should be called by vips.js
    rh_move_choice(event, ui) {
        jQuery(this).children().each(function(i) {
            jQuery(this).find('input').val(i);
        });
    },

    setUnsave(event) {
        let target = $(event.currentTarget);
        let question = {};
        question.index = target.parents('.cw-test-content').find('input[name=exercise_index]').val();
        question.title = target.parents('.cw-test-content').find('.question_title').text();
        this.unsavedInput.push(question);
    },

    resetUnsave() {
        this.unsavedInput = [];
    }

});

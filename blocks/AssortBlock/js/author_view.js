import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        "click button[name=save]":   "onSave",
        "click button[name=cancel]": "switchBack"
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
        $("section.block").show();

        var $assorttype = $view.$(".assorttype-selection-assort").val();
        $view.$("input[name='assorttype'][value='"+$assorttype+"']").attr("checked", "checked");

        $view.$("input[name='assortblocks']").removeAttr("checked");
        var $assortblocksselection = $view.$(".assortblocks-selection-assort").val();

        if ($assortblocksselection != ""){
            var $assortblocks = JSON.parse($assortblocksselection);

            $.each($assortblocks , function(){
                $view.$("input[name='assortblocks'][value='"+this["id"]+"']").prop("checked", true);
                $view.$("#blockname-"+this["id"]).val(this["name"]);
            });
        }
    },

    onSave(event) {
        var view = this;
        var $assorttype = this.$('input[name="assorttype"]:checked').val();
        var $assortblocksarray = new Array();

        this.$('input[name="assortblocks"]:checked').each(function(){
            var $id = $(this).val();
            var $name = $("#blockname-"+$id)
                        .val()
                        .replace(/[\u00A0-\u9999<>\&]/gim, function(i){
                            return '&#'+i.charCodeAt(0)+';';
                        });
            $assortblocksarray.push({id : $id , name : $name, hash: ''});
        });
        helper
            .callHandler(this.model.id, "save", {assortblocks: $assortblocksarray, assorttype: $assorttype})
            .then(
                // success
                function () {
                    jQuery(event.target).addClass("accept");
                    view.switchBack();
                },
                // error
                function (error) {
                    var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                    alert(errorMessage);
                    console.log(errorMessage, arguments);
                })
            .done();
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
    }

});


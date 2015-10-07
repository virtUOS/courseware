define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function() {
            var $view = this;
            $("section.block").show();
            
            var $assorttype = $view.$(".assorttype-selection-assort").val();
            $view.$("input[name='assorttype']").removeAttr("checked");
            $view.$("input[name='assorttype'][value='"+$assorttype+"']").attr("checked", "checked");
           
            $view.$("input[name='assortblocks']").removeAttr("checked");
            var $assortblocksselection = $view.$(".assortblocks-selection-assort").val();
            if ($assortblocksselection != ""){
                var $assortblocks = JSON.parse($assortblocksselection);
                $.each($assortblocks , function(){
                    $view.$("input[name='assortblocks'][value='"+this["id"]+"']").attr("checked", "checked");
                    $view.$("#blockname-"+this["id"]).val(this["name"]);
                });
            }
            
        },

        onSave: function (event) {
            var view = this;
            var $assorttype = this.$('input[name="assorttype"]:checked').val();
            var $assortblocksarray = new Array();
            
            this.$('input[name="assortblocks"]:checked').each(function(){
                var $id = $(this).val();
                var $name = $("#blockname-"+$id).val();
                $assortblocksarray.push({id : $id , name : $name});
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
        }
        
    });
});

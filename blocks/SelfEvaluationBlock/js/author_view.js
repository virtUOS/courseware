define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "click button[name=add-element]": "addElement"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function() {
            var $view = this;
            if (this.$(".cw-selfevaluation-value-stored").val() != "") {
                var $values = $.parseJSON(this.$(".cw-selfevaluation-value-stored").val())[0];
                this.$("input[name=cw-selfevaluation-content-value-good]").val($values.good);
                this.$("input[name=cw-selfevaluation-content-value-bad]").val($values.bad);

                var $values = $.parseJSON(this.$(".cw-selfevaluation-content-stored").val());
                
                $.each($values, function(){
                    $view.addElement(($(this)[0]).element);
                });
                console.log($values);
                if ($values.length > 0) {
                    this.$(".cw-selfevaluation-content-item").first().remove();
                }
            }
            

        },

        addElement: function($value){
            if(typeof $value !== "string"){ $value = "";}
            var $element = this.$(".cw-selfevaluation-content-item").first().clone();
            var $button =  this.$("button[name=add-element]");
            $element.find("input").attr("value", $value);
            $element.insertBefore($button);
        },

        onSave: function(event) {
            var $view = this;
            var $title = this.$("input[name=cw-selfevaluation-title]").val();
            var $description = this.$("textarea[name=cw-selfevaluation-description]").val();
            var $value = '[{"good":"'+this.$("input[name=cw-selfevaluation-content-value-good]").val()+'", "bad":"'+this.$("input[name=cw-selfevaluation-content-value-bad]").val()+'"}]';
            var $elements = new Array();
            this.$(".cw-selfevaluation-content-item").each(function(index){
                var $val = $(this).find("input").val();
                if ($val != "") {
                    $elements.push({"element": $val});
                }
            });
            $elements = JSON.stringify($elements);
            helper
                .callHandler(this.model.id, "save", {selfevaluation_title: $title, selfevaluation_description: $description, selfevaluation_value: $value,  selfevaluation_content: $elements})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        $view.switchBack();
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

define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
            "click input[name=download]":             "onDownload",
            "click .cw-selfevaluation-button-left":   "switchLeft",
            "click .cw-selfevaluation-button-right":  "switchRight",
            "change .cw-selfevaluation-radio":        "onSelect"
        },
        
        initialize: function(options) {
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
            var $view =  this;
            this.buildElements();
            return;
        },
        
        switchLeft: function(event){
            var $siblings = $(event.target).siblings("input");
            $.each($siblings , function($index, $item){
                if (($item.checked ==  true) && ($index != 0)){
                    $siblings[$index-1].checked = true;
                    return false;
                }
            })
        },
        
        switchRight: function(event){
            var $siblings = $(event.target).siblings("input");
            $.each($siblings , function($index, $item){
                if (($item.checked ==  true) && ($index != 3)){
                    $siblings[$index+1].checked = true;
                    return false;
                }
            })
        },
        
        buildElements: function() {
            if (this.$(".cw-selfevaluation-content-stored").val() != "") {
                if (this.$(".cw-selfevaluation-value-stored").val() != "") {
                    var $values = $.parseJSON(this.$(".cw-selfevaluation-value-stored").val())[0];
                } else {
                    var $values = $.parseJSON('[{"good":"", "bad":""}]')[0];
                }
                var $container = this.$(".cw-selfevaluation-container");
                var $contents = $.parseJSON(this.$(".cw-selfevaluation-content-stored").val());
                var $html = "";

                $.each($contents, function($index){
                    var $element = ($(this)[0]).element;
                    
                    $html += 
                        '<div class="cw-selfevaluation-item">'+
                        '<p class="cw-selfevaluation-item-element">'+$element+'</p>';
                    $element = $element.replace(/ /g, '');
                    $html += 
                        '<span class="cw-selfevaluation-item-value-good">'+$values.good+'</span>'+
                        '<button class="cw-selfevaluation-button cw-selfevaluation-button-left"></button> '+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="++">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="+">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="-">'+
                        '<input class="cw-selfevaluation-radio" type="radio" name="selected-'+$element+'" value="--">'+
                        '<button  class="cw-selfevaluation-button cw-selfevaluation-button-right"></button> '+
                        '<span class="cw-selfevaluation-item-value-bad">'+$values.bad+'</span>'+
                    '</div>';
                    if ($contents.length > $index+1) {
                        $html += "<hr>";
                    } else {
                      $html += "<br>";  
                    }
                });
                
                $container.html($html);
            }
        },
        
        onSelect: function() {
            var $view = this;
            var $data = $view.$("input[name=selfevaluation-data]");
            var $contents = $.parseJSON(this.$(".cw-selfevaluation-content-stored").val());
            
            var $selection = new Array();
            $.each($contents, function(){
                var $element = ($(this)[0]).element;
                $element = $element.replace(/ /g, '');
                var $value = $view.$("input[name=selected-"+$element+"]:checked").val();
                if (typeof $value !== "undefined") {
                    $selection.push({"element":$element, "value": $value});
                }
            });
            $data.val(JSON.stringify($selection));
        },
        
        onDownload: function(event) {
            
            helper
                .callHandler(this.model.id, "download", {})
                .then(
                    // success
                    function () {
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not store download action: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
        
    });
});



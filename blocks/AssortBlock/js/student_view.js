define(['assets/js/student_view'], function (StudentView) {
    'use strict';
    return StudentView.extend({
        events: {
        },
        
        initialize: function(options) {
        },
        
        postRender: function() {
            var $assorttype = this.$(".assorttype-selection-assort").val();  
            var $div = $("<div>", {class: "assortblock-content"});
            this.$el.append($div);
            if($assorttype == "tabs" || $assorttype == "vtabs") {
                var $ul = $("<ul>");
                $div.append($ul);
            }
            var $assortblocks = JSON.parse(this.$(".assortblocks-selection-assort").val());
            console.log($assortblocks.length);
            var $maxheight = 0;
            $.each($assortblocks , function(){
                var $id = this["id"];
                var $name = this["name"];
                var $thisblock = $("#block-"+$id);
                if ($thisblock.length == 0) {console.log("block "+ $id +" nicht vorhanden"); return; }
                $thisblock.hide();
                if ($maxheight < $thisblock.height()) $maxheight = $thisblock.height();
                if ($name == "") {$name = "Block "+$id;}
                switch($assorttype){
                    case "accordion":
                        $div.append("<h3>"+$name+"</h3><div>"+$thisblock.html()+"</div>");
                        break;
                    case "tabs":
                    case "vtabs":
                        $ul.append("<li><a href='#tabs-"+$id+"'>"+$name+"</a></li>");
                        $div.append("<div id ='tabs-"+$id+"'>"+$thisblock.html()+"</div>");
                        break;
                }
                console.log($maxheight+$assortblocks.length*20);
            });
            switch($assorttype){
                case "accordion":
                    $div.accordion({
                        heightStyle: "content" 
                    }).css({"min-height": 34+$maxheight+$assortblocks.length*45});
                    break;
                case "tabs":
                    $div.tabs({
                          heightStyle: "auto"
                    });
                    break;
                case "vtabs":
                    $div.tabs({
                          heightStyle: "fill"
                    }).addClass( "ui-tabs-vertical ui-helper-clearfix" );
                    $div.find('li').removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
                    break;
            }
            $div.find(".controls").hide();
        },
        
        render: function() { 
            return this; 
        }
    });
});

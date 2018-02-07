import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
    },

    initialize() {
    },

    postRender() {
        var $assorttype = this.$(".assorttype-selection-assort").val();  
        if (!$assorttype) {return;}
        var $div = $("<div>", {class: "assortblock-content"});
        this.$el.append($div);
        if($assorttype == "tabs") {
            var $ul = $("<ul>");
            $div.append($ul);
        }
        try {
            var $assortblocks = JSON.parse(this.$(".assortblocks-selection-assort").val());
        } catch (e) {
                console.log("json parse crashed!");
                console.log(e);
                return;
        }
        var $maxheight = 0;
        if(!$assortblocks) {return ;}
        $.each($assortblocks , function(){
            var $id = this["id"];
            var $name = this["name"];
            if(!$id) {return;}
            if(!$name) {$name = $id; }
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
                    $ul.append("<li><a href='#tabs-"+$id+"'>"+$name+"</a></li>");
                    $div.append("<div id ='tabs-"+$id+"'>"+$thisblock.html()+"</div>");
                    break;
            }
        });
        switch($assorttype){
            case "accordion":
                $div.accordion({
                    heightStyle: "content" 
                });
                break;
            case "tabs":
                $div.tabs({
                      heightStyle: "auto"
                });
                break;
        }
        $div.find(".controls").hide();
    },

    render() { 
        return this; 
    }
});


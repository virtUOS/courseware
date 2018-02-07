import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
        "click button[name=search]":   "onSearch",
        "click .cw-search-input-search": "keyListener"
    },

    initialize() {
    },

    render() {
        return this;
    },

    onSearch(event) {
        var view = this;
        var input_request = $.trim(view.$(".cw-search-input-search").val());

        if (input_request == "") {
            view.$(".cw-search-result-empty").show();
            return;
        }
        view.$(".cw-search-result-empty").hide();

        helper
            .callHandler(view.model.id, "search", {request: input_request}) 
            .then(function (success) {
                    view.showResults(success);
            })
            .catch(function (error) {
                console.log("error");
                console.log(error, arguments);
            });
    }, 

    keyListener(){
        var view = this;
        this.$(".cw-search-input-search").keypress(function(event){
            if ( event.which == 13 ) { view.onSearch();}
        });
    }, 
    
    showResults(response) {
        var $result = jQuery.parseJSON(response);
        var $html ="";
        if ($result) {
        $.each($result, function(index, value){
            if(value.chap){
                $html += "<li><a href='"+ value.link + "'>"+value.title+"</a></li>";
                
            } else {
                $html += "<li>"+value.chapter+" &rarr; "+value.subchapter+" &rarr; "+value.title+" &rarr; <a href='"+ value.link + "'>"+value.type+"</a></li>";
            }
        });}

        this.$(".cw-search-result ul").html($html);
        if ($html == "") {
            this.$(".cw-search-result-empty").show();
        }
    }
});


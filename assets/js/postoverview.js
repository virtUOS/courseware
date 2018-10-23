$(document).ready(function(){
    var thread_id_from_url = location.search.split('thread_id=')[1];
    $('.postoverview-form').hide();
    if(thread_id_from_url && (document.getElementById('container_'+thread_id_from_url) != null)){
        $('#thread_'+thread_id_from_url).show();
        $('#container_'+thread_id_from_url).addClass("post-overview-thread-selected");
        $('.post-overview-postings').scrollTop($('.post-overview-postings')[0].scrollHeight);
        $('.post-overview').scrollTop(
            $('#container_'+thread_id_from_url).offset().top - $('.post-overview').offset().top + $('.post-overview').scrollTop()
        );
        $('.postoverview-form').show();
    }

    $('.show-thread-button').click(function(event){
        $('.postoverview-form').show();
        var $thread_id = $(event.target).data('showthread');
        $('.thread').hide();
        $('#thread_'+$thread_id).show();
        $('.post-overview-postings').scrollTop($('.post-overview-postings')[0].scrollHeight);
        $('#input_thread_id').val($thread_id);
        $('.post-overview-thread').removeClass("post-overview-thread-selected");
        $('#container_'+$thread_id).addClass("post-overview-thread-selected");
    });
    $('.edit-thread-title-button').click(function(event){
        $(this).parent().hide();
        $(this).parent().siblings("form").show();
    });
    $('.edit-reset').click(function(event){
        $(this).parent().hide();
        $(this).parent().siblings(".thread-title-content").show();
    });
    $('.show-new-posts-threats').click(function(event){
        $('.thread').hide();
        $(this).css('background', '#28497c').css('color', '#fff');
        $.each($('.post-overview-thread'), function(){
            if (!$(this).hasClass('post-overview-highlight')) {
                $(this).hide();
            }
        });
    });
    $('.hide-unused-threats').click(function(event){
        $('.thread').hide();
        $(this).css('background', '#28497c').css('color', '#fff');
        $.each($('.post-overview-thread'), function(){
            if (!$(this).children('.post-overview-block-link').length) {
                $(this).hide();
            }
        });
    });
    $('.show-all-threats').click(function(event){
        $('.post-overview-settings button').removeAttr('style');
        $('.post-overview-thread').show();
    });
});

$(document).ready(function(){
    var isDragging = false;

    var chapterList = [],
        subchapterList = {},
        sectionList = {},
        blockList = {};

    $('p').siblings('ul').hide();

    $('p')
    .mousedown(function(){
        isDragging = false;
    })
    .mousemove(function(){
        isDragging = true;
    })
    .mouseup(function(){
        var wasDragging = isDragging;
        isDragging = false;
        if (!wasDragging) {
            $(this).siblings('ul').toggle();
            if(!$(this).hasClass('unfolded')) {
                $(this).addClass('unfolded');
            } else {
                $(this).removeClass('unfolded');
            }
        }
    });

    $('.chapter-list').sortable({
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            var chapterList = [];
            $('.chapter-item').each(function(key, value){
                chapterList.push($(value).data('id'));
            });
            $('#chapterList').val(JSON.stringify(chapterList));
        }
    }).disableSelection();
    $('.subchapter-list').sortable({
        connectWith:'.subchapter-list', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            $parent = $(ui.item).parents('.chapter-item').first();
            subchapterList[$parent.data('id')] = [];
            $.each(subchapterList, function(chapter_id){
                var entry = [];
                $('.chapter-item[data-id="'+chapter_id+'"]').find('.subchapter-item').each(function(key, value){
                    entry.push($(value).data('id'));
                });
                if(entry.length > 0) {
                    subchapterList[chapter_id] = entry;
                } else {
                    delete subchapterList[chapter_id];
                }
            });
            $('#subchapterList').val(JSON.stringify(subchapterList));
        }
    }).disableSelection();
    $('.section-list').sortable({
        connectWith:'.section-list', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            $parent = $(ui.item).parents('.subchapter-item').first();
            sectionList[$parent.data('id')] = [];
            $.each(sectionList, function(subchapter_id){
                var entry = [];
                $('.subchapter-item[data-id="'+subchapter_id+'"]').find('.section-item').each(function(key, value){
                    entry.push($(value).data('id'));
                });
                if(entry.length > 0) {
                    sectionList[subchapter_id] = entry;
                } else {
                    delete sectionList[subchapter_id];
                }
            });
            $('#sectionList').val(JSON.stringify(sectionList));
        }
    }).disableSelection();
    $('.block-list').sortable({
            connectWith:'.block-list', 
            placeholder: "highlight",
            start: function( event, ui ) {
                ui.placeholder.height(ui.item.height()+20);
            },
            stop: function(event, ui) {

            },
            update: function(event, ui) {
                $parent = $(ui.item).parents('.section-item').first();
                blockList[$parent.data('id')] = [];
                $.each(blockList, function(section_id){
                    var entry = [];
                    $('.section-item[data-id="'+section_id+'"]').find('.block-item').each(function(key, value){
                          entry.push( $(value).data('id'));
                    });
                    if(entry.length > 0) {
                        blockList[section_id] = entry;
                    } else {
                        delete blockList[section_id];
                    }
                });
                $('#blockList').val(JSON.stringify(blockList));
            }
    }).disableSelection();

    $('#sendchanges').click(function(){
        
        $.ajax({
            url : 'http://voicebunny.comeze.com/index.php',
            type : 'GET',
            data : {
                'numberOfWords' : 10
            },
            dataType:'json',
            success : function(data) {              
                alert('Data: '+data);
            },
            error : function(request,error)
            {
                alert("Request: "+JSON.stringify(request));
            }
        });
        
    });

});

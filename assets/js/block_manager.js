$(document).ready(function(){
    var isDragging = false,
        chapterList = [],
        subchapterList = {},
        sectionList = {},
        blockList = {};

    $('.chapter-description, .subchapter-description, .section-description, .block-description').siblings('ul').hide();
    startMouseListeners();
    createSortables();
    setImport();

    if($('#cw-blockmanager-info').children().length > 0) {
        $('#cw-blockmanager-info').show();
    }

    $('#cw-import-from-course').click(function(){
        $('#cw-import-selection').hide();
        $('#user-course-list').show();
        $('.semester-description').siblings('ul').hide();
    });

    $('.semester-description').click(function(){
        $(this).siblings('ul').toggle();
        if(!$(this).hasClass('unfolded')) {
            $(this).addClass('unfolded');
        } else {
            $(this).removeClass('unfolded');
        }
    });

    if ($('#cw-import-wrapper').hasClass('cw-blockmanager-remote-courseware')) {
        createSortablesForImport();
        stopMouseListeners();
        startMouseListeners();
        $('#cw-import-selection').hide();
        $('#user-course-list').hide();
    }
});

function startMouseListeners() {
    $('.chapter-description, .subchapter-description, .section-description, .block-description')
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
}

function stopMouseListeners() {
    $('.chapter-description, .subchapter-description, .section-description, .block-description').unbind();
}

function createSortables() {
    $('.chapter-list').sortable({
        connectWith:'.chapter-list:not(.chapter-list-import)', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {
            chapterList = [];
            $('.chapter-list:not(.chapter-list-import) .chapter-item').each(function(key, value){
                chapterList.push($(value).data('id'));
            });
            if($(ui.item).hasClass('chapter-item-import')) {
                removeImportClasses($(ui.item));
                importSubchapters($(ui.item));
                $('#import').val(true);
            }
            if($(ui.item).hasClass('chapter-item-remote')) {
                $('#remote').val(true);
            }
            $('#chapterList').val(JSON.stringify(chapterList));
        }
    }).disableSelection();

    $('.subchapter-list').sortable({
        connectWith:'.subchapter-list:not(.subchapter-list-import)', 
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
            if($(ui.item).hasClass('subchapter-item-import')) {
                removeImportClasses($(ui.item));
                importSections($(ui.item));
                $('#import').val(true);
            }
            if($(ui.item).hasClass('subchapter-item-remote')) {
                $('#remote').val(true);
            }
            $('#subchapterList').val(JSON.stringify(subchapterList));
        }
    }).disableSelection();

    $('.section-list').sortable({
        connectWith:'.section-list:not(.section-list-import)', 
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
            if($(ui.item).hasClass('section-item-import')) {
                removeImportClasses($(ui.item));
                importBlocks($(ui.item));
                $('#import').val(true);
            }
            if($(ui.item).hasClass('section-item-remote')) {
                $('#remote').val(true);
            }
            $('#sectionList').val(JSON.stringify(sectionList));
        }
    }).disableSelection();

    $('.block-list').sortable({
            connectWith:'.block-list:not(.block-list-import)', 
            placeholder: "highlight",
            start: function( event, ui ) {
                ui.placeholder.height(ui.item.height()+20);
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
                if($(ui.item).hasClass('block-item-import')) {
                    removeImportClasses($(ui.item));
                    $('#import').val(true);
                }
                if($(ui.item).hasClass('block-item-remote')) {
                $('#remote').val(true);
            }
                $('#blockList').val(JSON.stringify(blockList));
            }
    }).disableSelection();
}

function removeImportClasses($item) {
    var classes = 'chapter-item-import chapter-list-import subchapter-item-import subchapter-list-import section-item-import section-list-import block-item-import block-list-import';
    $item.removeClass(classes);
    $item.find('.chapter-item-import, .chapter-list-import, .subchapter-item-import, .subchapter-list-import, .section-item-import, .section-list-import, .block-item-import, .block-list-import').removeClass(classes);
}

function importBlocks($item) {
    var parent_id = $item.attr('data-id');
    var $blocks = $item.find('.block-item');
    var entry = [];
    $.each($blocks, function(index){
        entry.push($(this).attr('data-id'));
    });
    if (entry.length > 0){
        blockList[parent_id] = entry;
        $('#blockList').val(JSON.stringify(blockList));
    }
}

function importSections($item) {
    var parent_id = $item.attr('data-id');
    var $sections = $item.find('.section-item');
    var entry = [];
    $.each($sections, function(index){
        entry.push($(this).attr('data-id'));
        importBlocks($(this));
    });
    if (entry.length > 0){
        sectionList[parent_id] = entry;
        $('#sectionList').val(JSON.stringify(sectionList));
    }
}

function importSubchapters($item) {
    var parent_id = $item.attr('data-id');
    var $subchapters = $item.find('.subchapter-item');
    var entry = [];
    $.each($subchapters, function(index){
        entry.push($(this).attr('data-id'));
        importSections($(this));
    });
    if (entry.length > 0){
        subchapterList[parent_id] = entry;
        $('#subchapterList').val(JSON.stringify(subchapterList));
    }
}

function createSortablesForImport() {
    $('.chapter-list-import').sortable({
        connectWith:'.chapter-list', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        beforeStop: function(event, ui) {
            if(ui.item.parent().hasClass('chapter-list-import')){
                $(this).sortable("cancel");
            }
        }
    }).disableSelection();

    $('.subchapter-list-import').sortable({
        connectWith:'.subchapter-list', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        beforeStop: function(event, ui) {
            if(ui.item.parent().hasClass('subchapter-list-import')){
                $(this).sortable("cancel");
            }
        }
    }).disableSelection();

    $('.section-list-import').sortable({
        connectWith:'.section-list', 
        placeholder: 'highlight',
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height());
        },
        beforeStop: function(event, ui) {
            if(ui.item.parent().hasClass('section-list-import')){
                $(this).sortable("cancel");
            }
        }
    }).disableSelection();

    $('.block-list-import').sortable({
        connectWith:'.block-list', 
        placeholder: "highlight",
        start: function( event, ui ) {
            ui.placeholder.height(ui.item.height()+20);
        },
        beforeStop: function(event, ui) {
            if(ui.item.parent().hasClass('block-list-import')){
                $(this).sortable("cancel");
            }
        }
    }).disableSelection();
}

function setImport() {
    $('#cw-file-upload-import').on('change', function (event) {

        const file0 = event.target.files[0];
        var $file_input = $(this), $file_input_clone = $file_input.clone();
        $file_input_clone.attr('id', 'cw-file-upload-full-import');

        var block_map = JSON.parse($('#block_map').val());
        $('#cw-blockmanager-form-full-import').css('display', 'inline-block');

        ZipLoader.unzip(file0).then( function ( unziped ) {
            var text, parser, xmlDoc;

            text = unziped.extractAsText( 'data.xml' );
            parser = new DOMParser();
            xmlDoc = parser.parseFromString(text,"text/xml");

            var $this_chapter_list = $('<ul class="chapter-list chapter-list-import"></ul>').appendTo('#cw-import-lists');
            var chapter_counter = 0, subchapter_counter = 0, section_counter = 0;
            $.each(xmlDoc.documentElement.children, function(key, node) {
                if(node.nodeName == 'chapter') {
                    chapter_counter++;
                    node.setAttribute('temp-id' , chapter_counter);
                    var $this_chapter = $('<li class="chapter-item chapter-item-import" data-id="import-'+chapter_counter+'"></li>').appendTo($this_chapter_list);
                    $('<p class="chapter-description">'+node.getAttribute('title')+'<span>'+block_map[node.nodeName]+'</span></p>').appendTo($this_chapter);
                    var $this_subchapter_list = $('<ul class="subchapter-list subchapter-list-import"></ul>').appendTo($this_chapter);

                    $.each(node.children, function(key, node) {
                        if (node.nodeName == 'subchapter'){
                            subchapter_counter++;
                            node.setAttribute('temp-id' , subchapter_counter);
                            var $this_subchapter = $('<li class="subchapter-item subchapter-item-import"  data-id="import-'+subchapter_counter+'"></li>').appendTo($this_subchapter_list);
                            $('<p class="subchapter-description">'+node.getAttribute('title')+'<span>'+block_map[node.nodeName]+'</span></p>').appendTo($this_subchapter);
                            var $this_section_list = $('<ul class="section-list section-list-import"></ul>').appendTo($this_subchapter);

                            $.each(node.children, function(key, node) {
                                if (node.nodeName == 'section') {
                                    section_counter++;
                                    node.setAttribute('temp-id' , section_counter);
                                    var $this_section = $('<li class="section-item section-item-import" data-id="import-'+section_counter+'"></li>').appendTo($this_section_list);
                                    if (node.getAttribute('title').length > 24) {
                                        $('<p class="section-description" title="'+node.getAttribute('title')+'">'+node.getAttribute('title').slice(0,20)+'… <span>'+block_map[node.nodeName]+'</span></p>').appendTo($this_section);
                                    } else {
                                        $('<p class="section-description">'+node.getAttribute('title')+'<span>'+block_map[node.nodeName]+'</span></p>').appendTo($this_section);
                                    }
                                    var $this_block_list = $('<ul class="block-list block-list-import"></ul>').appendTo($this_section);

                                    $.each(node.children, function(key, node) {
                                        if (node.nodeName == 'block') {
                                            //build block
                                            var $this_block = $('<li class="block-item block-item-import" data-id="import-'+node.getAttribute('uuid')+'"></li>').appendTo($this_block_list);
                                            $('<p class="block-description"><span class="block-icon cw-block-icon-'+node.getAttribute('type')+'"></span>'+block_map[node.getAttribute('type')]+'</p>').appendTo($this_block);
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            } );
            let oSerializer = new XMLSerializer();
            $('#importXML').val(oSerializer.serializeToString(xmlDoc));
        }).then(function() {
            createSortablesForImport();
            stopMouseListeners();
            startMouseListeners();
            $('.subchapter-list-import, .section-list-import, .block-list-import').hide();
            $('#cw-import-selection').hide();
            $('#cw-import-lists').show();
            $('#cw-import-title p').html($('#cw-import-title p').html()+' - '+file0.name+ ' ('+(file0.size/1048576).toFixed(2)+'MB)');

            $file_input_clone.appendTo('#cw-blockmanager-form-full-import');
        });

    });
}

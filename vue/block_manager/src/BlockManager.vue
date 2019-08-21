<template>
    <div id="cw-blockmanager-content">
        <div class="cw-blockmanager-wrapper">
            <div class="cw-blockmanager-title">
                <p>{{ this.courseware.title }}</p>
                <span class="cw-blockmanager-store-icon" title="speichern"></span>
                <span class="cw-blockmanager-store-icon-error">Fehler beim Speichern</span>
            </div>
            <ul class="chapter-list">
                <ChapterItem
                    v-for="chapter in this.courseware.children"
                    :key="chapter.id"
                    :chapter="chapter"
                    :importContent="false"
                />
            </ul>
        </div>
        <div id="cw-action-wrapper" class="cw-blockmanager-wrapper">
            <div id="cw-import-title" class="cw-blockmanager-title">
                <p>{{ actionTitle }}</p>
            </div>
            <div class="messagebox messagebox_error" v-if="fileError">Das Archiv enthält keine Coursewaredaten</div>
            <div id="user-course-list">
                <ul class="semester-list">
                    <SemesterItem
                        v-for="(courses, semester_name) in this.remoteCourses"
                        :key="semester_name"
                        :courses="courses"
                        :semester_name="semester_name"
                        @course-selected="getRemoteCourse"
                    />
                </ul>
            </div>
            <div v-if="remoteCourseware" class="cw-remote-courseware">
                <ul class="chapter-list chapter-list-import">
                    <ChapterItem
                        v-for="remote_chapter in this.remoteCourseware.children"
                        :key="remote_chapter.id"
                        :chapter="remote_chapter"
                        :importContent="true"
                        :remoteContent="true"
                    />
                </ul>
            </div>
            <div v-if="importCourseware" id="cw-import-lists">
                <ul class="chapter-list chapter-list-import">
                    <ChapterItem
                        v-for="import_chapter in this.importCourseware.chapters"
                        :key="import_chapter.id"
                        :chapter="import_chapter"
                        :importContent="true"
                        :remoteContent="false"
                    />
                </ul>
            </div>
            <ul id="cw-action-selection">
                <li>
                    <label
                        for="cw-file-upload-import"
                        id="cw-file-upload-import-label"
                        class="cw-action-menu-button"
                        title="Laden Sie eine Datei hoch, die Sie zuvor aus einer Courseware exportiert haben"
                    >
                        <input
                            type="file"
                            name="cw-file-upload-import"
                            class="cw-file-upload-import"
                            id="cw-file-upload-import"
                            accept=".zip"
                            @change="setImport"
                        />
                        <p>Import-Archiv hochladen</p>
                    </label>
                </li>
                <li>
                    <div
                        id="cw-import-from-course"
                        class="cw-action-menu-button"
                        title="Importieren Sie Inhalte aus einer anderen Veranstaltung in der Sie Dozent sind"
                        @click="importFromCourse"
                    >
                        <p>{{ courseImportText }}</p>
                    </div>
                </li>
                <li>
                    <div id="cw-set-decontrol" class="cw-action-menu-button" title="" @click="setDecontrol">
                        <p>{{ setDecontrolText }}</p>
                    </div>
                </li>
            </ul>
            <button class="button" id="cw-reset-action-menu" @click="resetActionMenu">zurück zur Auswahl</button>
            <div style="clear: both;"></div>
        </div>
        <EditDialog />
        <RemoveDialog />
    </div>
</template>

<script>
import ChapterItem from './components/ChapterItem.vue';
import SemesterItem from './components/SemesterItem.vue';
import EditDialog from './components/EditDialog.vue';
import RemoveDialog from './components/RemoveDialog.vue';
import NodeContentHelper from './assets/NodeContentHelper.js';
import BlockManagerDialogs from './assets/BlockManagerDialogs.js';
import axios from 'axios';
export default {
    name: 'BlockManager',
    data() {
        return {
            courseware: {},
            remoteCourses: {},
            remoteCourseware: null,
            importCourseware: null,
            isDragging: false,
            chapterList: [],
            subchapterList: {},
            sectionList: {},
            blockList: {},
            actionTitle: 'Aktionen',
            courseImportText: 'Aus Veranstaltung importieren',
            setDecontrolText: 'Freigaben setzen',
            remoteData: false,
            importData: false,
            importMap: [],
            importXML: '',
            blockMap: null,
            fileError: false
        };
    },
    components: {
        ChapterItem,
        SemesterItem,
        EditDialog,
        RemoveDialog
    },
    created() {
        this.courseware = JSON.parse(COURSEWARE.data.courseware);
        this.remoteCourses = JSON.parse(COURSEWARE.data.remote_courses);
        this.blockMap = JSON.parse(COURSEWARE.data.block_map);
    },
    mounted() {
        this.startMouseListeners();
        this.createSortables();

        BlockManagerDialogs.createEditDialog($('#editDialog'));
        BlockManagerDialogs.createRemoveDialog($('#removeDialog'));
    },
    methods: {
        setDecontrol() {
            $('#cw-reset-action-menu').show();
            $('#cw-action-selection').hide();
            this.actionTitle = this.setDecontrolText;
        },
        importFromCourse() {
            this.actionTitle = this.courseImportText;
            $('#cw-reset-action-menu').show();
            $('#cw-action-selection').hide();
            $('#user-course-list').show();
            $('.semester-description')
                .siblings('ul')
                .hide();
        },
        resetActionMenu(event) {
            $('#user-course-list').hide();
            $('.cw-remote-courseware').hide();
            $('#cw-import-lists').hide();
            $('#cw-action-selection').show();
            $('#cw-action-wrapper')
                .find('.unfolded')
                .removeClass('unfolded');
            $(event.target).hide();
            this.actionTitle = 'Aktionen';
            this.fileError = false;
        },
        startMouseListeners() {
            $('.chapter-description, .subchapter-description, .section-description, .block-description')
                .mousedown(function() {
                    this.isDragging = false;
                })
                .mousemove(function() {
                    this.isDragging = true;
                })
                .mouseup(function() {
                    let wasDragging = this.isDragging;
                    this.isDragging = false;
                    if (!wasDragging) {
                        $(this)
                            .siblings('ul')
                            .toggle();
                        if (!$(this).hasClass('unfolded')) {
                            $(this).addClass('unfolded');
                            $(this)
                                .siblings('.element-toolbar')
                                .addClass('unfolded');
                        } else {
                            $(this).removeClass('unfolded');
                            $(this)
                                .siblings('.element-toolbar')
                                .removeClass('unfolded');
                        }
                    }
                });
        },
        stopMouseListeners() {
            $('.chapter-description, .subchapter-description, .section-description, .block-description').unbind();
        },
        createSortables() {
            let view = this;
            $('.chapter-list')
                .sortable({
                    connectWith: '.chapter-list:not(.chapter-list-import)',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    update: function(event, ui) {
                        view.chapterList = [];
                        $('.chapter-list:not(.chapter-list-import) .chapter-item').each(function(key, value) {
                            view.chapterList.push($(value).data('id'));
                        });
                        if ($(ui.item).hasClass('chapter-item-import')) {
                            view.removeImportClasses($(ui.item));
                            view.importSubchapters($(ui.item));
                            view.importData = true;
                        }
                        if ($(ui.item).hasClass('chapter-item-remote')) {
                            view.remoteData = true;
                        }

                        view.storeChanges();
                    }
                })
                .disableSelection();

            $('.subchapter-list')
                .sortable({
                    connectWith: '.subchapter-list:not(.subchapter-list-import)',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    update: function(event, ui) {
                        let $parent = $(ui.item)
                            .parents('.chapter-item')
                            .first();
                        view.subchapterList[$parent.data('id')] = [];
                        $.each(view.subchapterList, function(chapter_id) {
                            var entry = [];
                            $('.chapter-item[data-id="' + chapter_id + '"]')
                                .find('.subchapter-item')
                                .each(function(key, value) {
                                    entry.push($(value).data('id'));
                                });
                            if (entry.length > 0) {
                                view.subchapterList[chapter_id] = entry;
                            } else {
                                delete view.subchapterList[chapter_id];
                            }
                        });
                        if ($(ui.item).hasClass('subchapter-item-import')) {
                            view.removeImportClasses($(ui.item));
                            view.importSections($(ui.item));
                            view.importData = true;
                        }
                        if ($(ui.item).hasClass('subchapter-item-remote')) {
                            view.remoteData = true;
                        }
                        if (
                            $(ui.item)
                                .parents('.chapter-item')
                                .hasClass('element_hidden')
                        ) {
                            $(ui.item).addClass('element_hidden');
                            $(ui.item)
                                .find('p.subchapter-description')
                                .addClass('element_hidden');
                        }

                        view.storeChanges();
                    }
                })
                .disableSelection();

            $('.section-list')
                .sortable({
                    connectWith: '.section-list:not(.section-list-import)',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    update: function(event, ui) {
                        let $parent = $(ui.item)
                            .parents('.subchapter-item')
                            .first();
                        view.sectionList[$parent.data('id')] = [];
                        $.each(view.sectionList, function(subchapter_id) {
                            var entry = [];
                            $('.subchapter-item[data-id="' + subchapter_id + '"]')
                                .find('.section-item')
                                .each(function(key, value) {
                                    entry.push($(value).data('id'));
                                });
                            if (entry.length > 0) {
                                view.sectionList[subchapter_id] = entry;
                            } else {
                                delete view.sectionList[subchapter_id];
                            }
                        });
                        if ($(ui.item).hasClass('section-item-import')) {
                            view.removeImportClasses($(ui.item));
                            view.importBlocks($(ui.item));
                            view.importData = true;
                        }
                        if ($(ui.item).hasClass('section-item-remote')) {
                            view.remoteData = true;
                        }
                        if (
                            $(ui.item)
                                .parents('.subchapter-item')
                                .hasClass('element_hidden')
                        ) {
                            $(ui.item).addClass('element_hidden');
                            $(ui.item)
                                .find('p.section-description')
                                .addClass('element_hidden');
                        }

                        view.storeChanges();
                    }
                })
                .disableSelection();

            $('.block-list')
                .sortable({
                    connectWith: '.block-list:not(.block-list-import)',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height() + 20);
                    },
                    update: function(event, ui) {
                        let $parent = $(ui.item)
                            .parents('.section-item')
                            .first();
                        view.blockList[$parent.data('id')] = [];
                        $.each(view.blockList, function(section_id) {
                            var entry = [];
                            $('.section-item[data-id="' + section_id + '"]')
                                .find('.block-item')
                                .each(function(key, value) {
                                    entry.push($(value).attr('data-id'));
                                });

                            if (entry.length > 0) {
                                view.blockList[section_id] = entry;
                            } else {
                                delete view.blockList[section_id];
                            }
                        });
                        if ($(ui.item).hasClass('block-item-import')) {
                            view.removeImportClasses($(ui.item));
                            view.importData = true;
                        }
                        if ($(ui.item).hasClass('block-item-remote')) {
                            view.remoteData = true;
                        }
                        if (
                            $(ui.item)
                                .parents('.section-item')
                                .hasClass('element_hidden')
                        ) {
                            $(ui.item).addClass('element_hidden');
                            $(ui.item)
                                .find('p.block-description')
                                .addClass('element_hidden');
                        }

                        view.storeChanges();
                    }
                })
                .disableSelection();
        },
        storeChanges() {
            let view = this;
            let promises = [];
            let fileData = {};
            if (view.importData && !view.remoteData) {
                let file = $('#cw-file-upload-import')[0].files[0];
                let filePromise = new Promise(resolve => {
                    let reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onloadend = function() {
                        fileData.file = reader.result;
                        fileData.name = file.name;
                        fileData.size = file.size;
                        fileData.type = file.type;
                        resolve(reader.result);
                    };
                });
                promises.push(filePromise);
            }
            Promise.all(promises).then(function() {
                axios
                    .post('store_changes_vue', {
                        cid: COURSEWARE.config.cid,
                        import: view.importData,
                        remote: view.remoteData,
                        importXML: view.importXML,
                        chapterList: JSON.stringify(view.chapterList),
                        subchapterList: JSON.stringify(view.subchapterList),
                        sectionList: JSON.stringify(view.sectionList),
                        blockList: JSON.stringify(view.blockList),
                        fileData: fileData
                    })
                    .then(response => {
                        $('.cw-blockmanager-store-icon').fadeIn(500, function() {
                            $(this)
                                .delay(1000)
                                .fadeOut(500);
                        });
                        view.importData = false;
                        view.remoteData = false;

                        if (response.data.remote_map != '') {
                            let remoteMap = JSON.parse(response.data.remote_map);
                            view.changeRemoteIds(remoteMap);
                        }
                    })
                    .catch(error => {
                        console.log('there was an error: ' + error.response);
                        $('.cw-blockmanager-store-icon-error').fadeIn(500);
                    });
            });
        },
        changeRemoteIds(remoteMap) {
            //TODO all lists not only blockList !!!
            $.each(remoteMap.block_map, function(remote_id, new_id) {
                $('.block-item[data-id="' + remote_id + '"]')
                    .not('.block-item-import')
                    .attr('data-id', new_id)
                    .removeClass('block-item-remote');
            });
            this.blockList = {};
        },
        removeImportClasses($item) {
            var classes =
                'chapter-item-import chapter-list-import subchapter-item-import subchapter-list-import section-item-import section-list-import block-item-import block-list-import';
            $item.removeClass(classes);
            $item
                .find(
                    '.chapter-item-import, .chapter-list-import, .subchapter-item-import, .subchapter-list-import, .section-item-import, .section-list-import, .block-item-import, .block-list-import'
                )
                .removeClass(classes);
        },
        importBlocks($item) {
            let view = this;
            var parent_id = $item.attr('data-id');
            var $blocks = $item.find('.block-item');
            var entry = [];
            $.each($blocks, function() {
                entry.push($(this).attr('data-id'));
            });
            if (entry.length > 0) {
                view.blockList[parent_id] = entry;
            }
        },

        importSections($item) {
            let view = this;
            var parent_id = $item.attr('data-id');
            var $sections = $item.find('.section-item');
            var entry = [];
            $.each($sections, function() {
                entry.push($(this).attr('data-id'));
                view.importBlocks($(this));
            });
            if (entry.length > 0) {
                view.sectionList[parent_id] = entry;
            }
        },
        importSubchapters($item) {
            let view = this;
            var parent_id = $item.attr('data-id');
            var $subchapters = $item.find('.subchapter-item');
            var entry = [];
            $.each($subchapters, function() {
                entry.push($(this).attr('data-id'));
                view.importSections($(this));
            });
            if (entry.length > 0) {
                view.subchapterList[parent_id] = entry;
            }
        },
        getRemoteCourse(event) {
            let view = this;
            axios
                .get('get_remote_course', {
                    params: {
                        cid: COURSEWARE.config.cid,
                        remote_cid: event.remoteId
                    }
                })
                .then(response => {
                    view.remoteCourseware = response.data;
                })
                .then(function() {
                    view.actionTitle = 'Import: ' + event.remoteName;
                    view.createSortablesForImport();
                    view.stopMouseListeners();
                    view.startMouseListeners();
                    $('#cw-action-selection').hide();
                    $('#user-course-list').hide();
                    $('.cw-remote-courseware').show();
                })
                .catch(error => {
                    console.log('there was an error: ' + error.response);
                });
        },
        createSortablesForImport() {
            $('.subchapter-list-import, .section-list-import, .block-list-import, .block-preview-import').hide();
            $('.chapter-list-import')
                .sortable({
                    connectWith: '.chapter-list',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    beforeStop: function(event, ui) {
                        if (ui.item.parent().hasClass('chapter-list-import')) {
                            $(this).sortable('cancel');
                        }
                    }
                })
                .disableSelection();

            $('.subchapter-list-import')
                .sortable({
                    connectWith: '.subchapter-list',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    beforeStop: function(event, ui) {
                        if (ui.item.parent().hasClass('subchapter-list-import')) {
                            $(this).sortable('cancel');
                        }
                    }
                })
                .disableSelection();

            $('.section-list-import')
                .sortable({
                    connectWith: '.section-list',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    beforeStop: function(event, ui) {
                        if (ui.item.parent().hasClass('section-list-import')) {
                            $(this).sortable('cancel');
                        }
                    }
                })
                .disableSelection();

            $('.block-list-import')
                .sortable({
                    connectWith: '.block-list',
                    placeholder: 'highlight',
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height() + 20);
                    },
                    beforeStop: function(event, ui) {
                        if (ui.item.parent().hasClass('block-list-import')) {
                            $(this).sortable('cancel');
                        }
                    }
                })
                .disableSelection();
        },
        setImport() {
            let view = this;
            view.fileError = false;
            const file0 = event.target.files[0];

            $('#cw-blockmanager-form-full-import').css('display', 'inline-block');
            $('#cw-reset-action-menu').show();

            ZipLoader.unzip(file0)
                .then(function(unziped) {
                    var text, parser, xmlDoc;
                    if (unziped.files['data.xml'] == undefined) {
                        view.fileError = true;
                        return;
                    }

                    text = unziped.extractAsText('data.xml');
                    parser = new DOMParser();
                    xmlDoc = parser.parseFromString(text, 'text/xml');

                    var chapter_counter = 0,
                        subchapter_counter = 0,
                        section_counter = 0;
                    view.importCourseware = [];
                    view.importCourseware['chapters'] = [];
                    $.each(xmlDoc.documentElement.children, function(key, node) {
                        if (node.nodeName == 'chapter') {
                            chapter_counter++;
                            node.setAttribute('temp-id', chapter_counter);
                            let chapterNum = view.importCourseware['chapters'].push({
                                title: node.getAttribute('title'),
                                id: chapter_counter,
                                publication_date: false,
                                withdraw_date: false,
                                isPublished: true
                            });
                            let current_chapter = view.importCourseware['chapters'][chapterNum - 1];

                            if (typeof current_chapter === 'undefined') {
                                return true; //skip to next
                            }
                            current_chapter.children = [];
                            $.each(node.children, function(key, node) {
                                if (node.nodeName == 'subchapter') {
                                    subchapter_counter++;
                                    node.setAttribute('temp-id', subchapter_counter);
                                    let subchapterNum = current_chapter.children.push({
                                        title: node.getAttribute('title'),
                                        id: subchapter_counter,
                                        publication_date: false,
                                        withdraw_date: false,
                                        isPublished: true
                                    });
                                    let current_subchapter = current_chapter.children[subchapterNum - 1];

                                    if (typeof current_subchapter === 'undefined') {
                                        return true; //skip to next
                                    }
                                    current_subchapter.children = [];
                                    $.each(node.children, function(key, node) {
                                        if (node.nodeName == 'section') {
                                            section_counter++;
                                            node.setAttribute('temp-id', section_counter);

                                            let sectionNum = current_subchapter.children.push({
                                                title: node.getAttribute('title'),
                                                id: section_counter,
                                                isPublished: true
                                            });
                                            let current_section = current_subchapter.children[sectionNum - 1];
                                            if (typeof current_section === 'undefined') {
                                                return true; //skip to next
                                            }
                                            current_section.children = [];
                                            $.each(node.children, function(key, node) {
                                                if (node.nodeName == 'block') {
                                                    //build block
                                                    current_section.children.push({
                                                        type: node.getAttribute('type'),
                                                        id: node.getAttribute('uuid'),
                                                        isPublished: true,
                                                        preview: NodeContentHelper.getContent(node),
                                                        readable_name: view.blockMap[node.getAttribute('type')]
                                                    });
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                    let oSerializer = new XMLSerializer();
                    view.importXML = oSerializer.serializeToString(xmlDoc);
                })
                .then(function() {
                    view.createSortablesForImport();
                    view.stopMouseListeners();
                    view.startMouseListeners();
                    $('.subchapter-list-import, .section-list-import, .block-list-import').hide();
                    $('#cw-action-selection').hide();
                    if (!view.fileError) {
                        $('#cw-import-lists').show();
                    }
                    view.actionTitle = 'Import: ' + file0.name + ' (' + view.calcFileSize(file0.size) + ')';
                });
        },
        calcFileSize(size) {
            if ((size / 1048576).toFixed(0) != 0) {
                return (size / 1048576).toFixed(1) + ' MB';
            } else {
                return (size / 1024).toFixed(1) + ' kB';
            }
        }
    }
};
</script>

<style scoped></style>

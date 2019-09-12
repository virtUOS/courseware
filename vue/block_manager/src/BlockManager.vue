<template>
    <div id="cw-blockmanager-content">
        <div class="cw-blockmanager-wrapper">
            <div class="cw-blockmanager-title">
                <p>{{ this.courseware.title }}</p>
                <ActionMenuItem :buttons="['add-child']" :element="this.courseware" @add-child="addChild" />
            </div>
            <draggable
                tag="ul"
                :list="chapters"
                :group="{ name: 'chapters' }"
                v-bind="dragOptions"
                class="chapter-list"
                ghost-class="ghost"
                handle=".chapter-handle"
                @sort="sortChapters"
            >
                <ChapterItem
                    v-for="element in this.chapters"
                    :key="element.id"
                    :element="element"
                    :importContent="false"
                    :remoteContent="false"
                    @subchapterListUpdate="updateSubchapterList"
                    @blockListUpdate="updateBlockList"
                    @sectionListUpdate="updateSectionList"
                    @remove-chapter="removeChapter"
                    @isRemote="isRemoteAction"
                />
            </draggable>
        </div>
        <div id="cw-action-wrapper" class="cw-blockmanager-wrapper">
            <div id="cw-import-title" class="cw-blockmanager-title">
                <p>{{ actionTitle }}</p>
            </div>
            <div class="messagebox messagebox_error" v-if="fileError">
                Das Archiv enthält keine Coursewaredaten
            </div>
            <div v-if="showRemoteCourseware && !remoteCourseware && !loading" id="user-course-list">
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
            <div v-if="showRemoteCourseware && remoteCourseware" class="cw-remote-courseware">
                <draggable
                    tag="ul"
                    :list="this.remoteCourseware.children"
                    :group="{ name: 'chapters', pull: 'clone', put: false }"
                    class="chapter-list chapter-list-import"
                    ghost-class="ghost"
                    handle=".chapter-handle"
                    v-bind="dragOptionsRemote"
                >
                    <ChapterItem
                        v-for="remote_chapter in this.remoteCourseware.children"
                        :key="remote_chapter.id"
                        :element="remote_chapter"
                        :importContent="true"
                        :remoteContent="true"
                    />
                </draggable>
            </div>
            <div v-if="showImportCourseware && importCourseware" id="cw-import-lists">
                <draggable
                    tag="ul"
                    :list="this.importCourseware.chapters"
                    :group="{ name: 'chapters', pull: 'clone', put: false }"
                    class="chapter-list chapter-list-import"
                    ghost-class="ghost"
                    handle=".chapter-handle"
                    v-bind="dragOptionsRemote"
                >
                    <ChapterItem
                        v-for="import_chapter in this.importCourseware.chapters"
                        :key="import_chapter.id"
                        :element="import_chapter"
                        :importContent="true"
                        :remoteContent="false"
                    />
                </draggable>
            </div>
            <ul v-if="!showRemoteCourseware && !showImportCourseware" id="cw-action-selection">
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
            </ul>
            <div v-if="(showRemoteCourseware || showImportCourseware) && loading">
                <breeding-rhombus-spinner
                    :animation-duration="2500"
                    :size="65"
                    :color="'#28497c'"
                    class="cw-action-loading"
                />
            </div>
            <button
                v-if="(showRemoteCourseware || showImportCourseware) && !loading"
                class="button"
                id="cw-reset-action-menu"
                @click="resetActionMenu"
            >
                zurück zur Auswahl
            </button>
            <div style="clear: both;"></div>
        </div>
    </div>
</template>

<script>
import ChapterItem from './components/ChapterItem.vue';
import SemesterItem from './components/SemesterItem.vue';
import ActionMenuItem from './components/ActionMenuItem.vue';
import NodeContentHelper from './assets/NodeContentHelper.js';

import { BreedingRhombusSpinner } from 'epic-spinners';
import axios from 'axios';
import draggable from 'vuedraggable';
export default {
    name: 'BlockManager',
    components: {
        ChapterItem,
        SemesterItem,
        draggable,
        ActionMenuItem,
        BreedingRhombusSpinner
    },
    data() {
        return {
            courseware: {},
            remoteCourses: {},
            remoteCourseware: null,
            importCourseware: null,
            showRemoteCourseware: false,
            showImportCourseware: false,
            chapters: {},
            chapterList: [],
            subchapterList: {},
            sectionList: {},
            blockList: {},
            actionTitle: 'Aktionen',
            courseImportText: 'Aus Veranstaltung importieren',
            remoteData: false,
            importData: false,
            importMap: [],
            importXML: '',
            importZIP: '',
            blockMap: null,
            fileError: false,

            loading: false,
            storeLock: false
        };
    },
    created() {
        this.courseware = JSON.parse(COURSEWARE.data.courseware);
        this.remoteCourses = JSON.parse(COURSEWARE.data.remote_courses);
        this.blockMap = JSON.parse(COURSEWARE.data.block_map);
        this.chapters = this.courseware.children;
    },
    mounted() {},
    computed: {
        dragOptions() {
            return {
                animation: 200,
                disabled: false,
                ghostClass: 'ghost'
            };
        },
        dragOptionsRemote() {
            return {
                animation: 200,
                disabled: false,
                sort: false,
                ghostClass: 'ghost'
            };
        }
    },
    watch: {
        chapters: function() {
            let view = this;
            view.chapterList = [];
            this.chapters.forEach(element => {
                if (element.isRemote) {
                    view.chapterList.push('remote-' + element.id);
                    view.remoteData = true;
                    view.importData = true;
                } else if (element.isImport) {
                    view.chapterList.push('import-' + element.id);
                    view.importData = true;
                } else {
                    view.chapterList.push(element.id);
                }
            });
        }
    },
    methods: {
        isRemoteAction() {
            this.remoteData = true;
            this.importData = true;
        },
        updateSubchapterList(update) {
            let key = Object.keys(update)[0];
            this.subchapterList[key] = update[key];
            this.storeChanges();
        },
        updateSectionList(update) {
            let key = Object.keys(update)[0];
            this.sectionList[key] = update[key];
            console.log(this.sectionList);
            this.storeChanges();
        },
        updateBlockList(update) {
            let key = Object.keys(update)[0];
            this.blockList[key] = update[key];
            this.storeChanges();
        },
        sortChapters() {
            this.storeChanges();
        },
        addChild(data) {
            this.chapters.push(data);
        },
        removeChapter(data) {
            let chapters = [];
            this.chapters.forEach(element => {
                if (element.id != data.id) {
                    chapters.push(element);
                }
            });
            this.chapters = chapters;
        },
        importFromCourse() {
            this.actionTitle = this.courseImportText;
            this.showRemoteCourseware = true;
        },
        resetActionMenu() {
            this.actionTitle = 'Aktionen';
            this.fileError = false;
            this.remoteCourseware = null;
            this.importCourseware = null;
            this.showRemoteCourseware = false;
            this.showImportCourseware = false;
        },
        storeChanges() {
            if (this.storeLock) {
                return;
            }
            this.storeLock = true;
            let view = this;
            let promises = [];
            let fileData = {};
            if (view.importData && !view.remoteData) {
                console.log('use file');
                let file = this.importZIP;
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
                        view.importData = false;
                        view.remoteData = false;
                        if (response.data.courseware != '') {
                            // view.courseware = {};
                            view.courseware = response.data.courseware;
                            // view.chapters = {};
                            view.chapters = view.courseware.children;
                        }
                        view.storeLock = false;
                    });
            });
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
            this.loading = true;
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
                    view.showRemoteCourseware = true;
                    view.loading = false;
                })
                .catch(error => {
                    console.log('there was an error: ' + error.response);
                });
        },
        setImport() {
            this.loading = true;
            let view = this;
            view.fileError = false;
            this.importZIP = event.target.files[0];

            this.showImportCourseware = true;
            ZipLoader.unzip(this.importZIP)
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
                                isPublished: true,
                                isImport: true
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
                                        isPublished: true,
                                        isImport: true
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
                                                isPublished: true,
                                                isImport: true
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
                                                        isImport: true,
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
                    view.loading = false;
                    if (!view.fileError) {
                        view.showImportCourseware = true;
                    }
                    view.actionTitle =
                        'Import: ' + view.importZIP.name + ' (' + view.calcFileSize(view.importZIP.size) + ')';
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

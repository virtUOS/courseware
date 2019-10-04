<template>
    <div id="cw-blockmanager-content">
        <div class="cw-blockmanager-wrapper">
            <div class="cw-blockmanager-title">
                <p>{{ this.courseware.title }}</p>
                <div class="cw-blockmanager-title-loading">
                    <spring-spinner :animation-duration="1000" :size="30" :color="'#28497c'" v-if="storeLock" />
                </div>
                <ActionMenuItem :buttons="['add-child']" :element="this.courseware" @add-child="addChild" />
            </div>
            <draggable
                tag="ul"
                :list="chapters"
                :group="{ name: 'chapters' }"
                class="chapter-list"
                ghost-class="ghost"
                handle=".chapter-handle"
                @sort="sortChapters"
                v-bind="storeLock ? { disabled: true } : { disabled: false, animation: 200 }"
            >
                <ChapterItem
                    v-for="element in this.chapters"
                    :key="element.id"
                    :element="element"
                    :importContent="false"
                    :remoteContent="false"
                    :storeLock="storeLock"
                    @listUpdate="updateList"
                    @remove-chapter="removeChapter"
                    @isRemote="isRemoteAction"
                    @isImport="isImportAction"
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
                    v-bind="storeLock ? { disabled: true } : { disabled: false, animation: 200, sort: false }"
                    @clone="cloning"
                >
                    <ChapterItem
                        v-for="remote_chapter in this.remoteCourseware.children"
                        :key="remote_chapter.id"
                        :element="remote_chapter"
                        :importContent="true"
                        :remoteContent="true"
                        :storeLock="storeLock"
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
                    v-bind="storeLock ? { disabled: true } : { disabled: false, animation: 200, sort: false }"
                >
                    <ChapterItem
                        v-for="import_chapter in this.importCourseware.chapters"
                        :key="import_chapter.id"
                        :element="import_chapter"
                        :importContent="true"
                        :remoteContent="false"
                        :storeLock="storeLock"
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
                <spring-spinner :animation-duration="3000" :size="65" :color="'#28497c'" class="cw-action-loading" />
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
import blockManagerHelperMixin from './mixins/blockManagerHelperMixin.js';

import { SpringSpinner } from 'epic-spinners';
import axios from 'axios';
import draggable from 'vuedraggable';
export default {
    name: 'BlockManager',
    mixins: [blockManagerHelperMixin],
    components: {
        ChapterItem,
        SemesterItem,
        draggable,
        ActionMenuItem,
        SpringSpinner
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
            actionTitle: this.$i18n.t("message.tasks"),
            courseImportText: this.$i18n.t("message.importFromCourse"),
            remoteData: false,
            importData: false,
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
        
    },
    mounted() {},
    watch: {
        courseware: function() {
            this.chapters = this.courseware.children;
        }
    },
    methods: {
        sortChapters() {
            let view = this;
            let hasChildren = false;
            this.cleanLists();
            this.chapters.forEach(element => {
                if (element.isRemote) {
                    view.chapterList.push('remote_' + element.id);
                    view.isRemoteAction();
                    if (element.children != null) {
                        hasChildren = true;
                        view.buildChildrenList(element, 'remote_');
                    }
                } else if (element.isImport) {
                    view.chapterList.push('import_' + element.id);
                    view.isImportAction();
                    if (element.children != null) {
                        hasChildren = true;
                        view.buildChildrenList(element, 'import_');
                    }
                } else {
                    view.chapterList.push(element.id);
                }
            });
            if (!hasChildren) {
                this.storeChanges();
            }
        },
        fillChapterList() {
            this.chapters.forEach(element => {
                this.chapterList.push(element.id);
            });
        },
        buildChildrenList(element, type) {
            let view = this;
            let list = [];
            let hasChildren = false;
            element.children.forEach(child => {
                list.push(type + child.id);
                if (child.children != null) {
                    hasChildren = true;
                    view.buildChildrenList(child, type);
                }
            });
            if (element.type.toLowerCase() == 'section') {
                this.blockList[type + element.id] = list;
            } else {
                this[element.children[0].type.toLowerCase() + 'List'][type + element.id] = list;
            }
            if (!hasChildren) {
                this.storeChanges();
            }
        },
        isRemoteAction() {
            this.remoteData = true;
            this.importData = true;
        },
        isImportAction() {
            this.remoteData = false;
            this.importData = true;
        },
        updateList(args) {
            let list = args.list;
            let key = Object.keys(list)[0];
            this[args.type + 'List'][key] = list[key];

            if (!args.hasChildren) {
                this.fillChapterList();
                this.storeChanges();
            }
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
                console.log('storeLock');
                return;
            }
            this.storeLock = true;
            let view = this;
            let promises = [];
            let fileData = {};
            if (view.importData && !view.remoteData) {
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
                        view.cleanLists();
                        view.courseware = JSON.parse(response.data.courseware);
                        view.storeLock = false;
                    })
                    .catch(function(error) {
                        if (error.response) {
                            // The request was made and the server responded with a status code
                            // that falls out of the range of 2xx
                            console.log(error.response.data);
                            console.log(error.response.status);
                            console.log(error.response.headers);
                        } else if (error.request) {
                            // The request was made but no response was received
                            // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                            // http.ClientRequest in node.js
                            console.log(error.request);
                        } else {
                            // Something happened in setting up the request that triggered an Error
                            console.log('Error', error.message);
                        }
                        console.log(error.config);
                    });
            });
        },
        cleanLists() {
            this.chapterList = [];
            this.subchapterList = {};
            this.sectionList = {};
            this.blockList = {};
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
                                isImport: true,
                                type: node.nodeName
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
                                        isImport: true,
                                        type: node.nodeName
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
                                                isImport: true,
                                                type: node.nodeName
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
                                                        preview: view.getContent(node),
                                                        readable_name: view.blockMap[node.getAttribute('type')]
                                                    });
                                                }
                                            });
                                            if (current_section.children.length == 0) {
                                                current_section.children = null;
                                            }
                                        }
                                    });
                                    if (current_subchapter.children.length == 0) {
                                        current_subchapter.children = null;
                                    }
                                }
                            });
                            if (current_chapter.children.length == 0) {
                                current_chapter.children = null;
                            }
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

<template>
    <div id="cw-blockmanager-content">
        <div class="cw-blockmanager-wrapper">
            <div class="cw-blockmanager-title">
                <p>{{ this.courseware.title }}</p>
                <div class="cw-blockmanager-title-loading">
                    <spring-spinner :animation-duration="1000" :size="30" :color="'#28497c'" v-if="storeLock" />
                </div>
                <ActionMenuItem :buttons="['add-child']" :element="this.courseware" :courseUsers="courseUsers" :courseGroups="courseGroups" @add-child="addChild" />
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
                    :courseUsers="courseUsers"
                    :courseGroups="courseGroups"
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
                    <div v-if="this.remoteCourses.length == 0" class="messagebox messagebox_info">
                        Es sind keine Veranstaltungen für den Import verfügbar
                    </div>
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
            <div v-if="showExportCourseware">
                <!--- Export --->
            </div>
            <ul v-if="!showRemoteCourseware && !showImportCourseware && !showExportCourseware" id="cw-action-selection">
                <li>
                    <label
                        for="cw-file-upload-import"
                        id="cw-file-upload-import-label"
                        class="cw-action-menu-button"
                        title="importFromArchiveExplain"
                    >
                        <input
                            type="file"
                            name="cw-file-upload-import"
                            class="cw-file-upload-import"
                            id="cw-file-upload-import"
                            accept=".zip"
                            @change="setImport"
                        />
                        <p>Courseware-Archiv importieren</p>
                    </label>
                </li>
                <li>
                    <a :href="coursewareImportURL">
                        <div id="cw-import-link" class="cw-action-menu-button" title="importExplain">
                            <p>Courseware-Archiv importieren (alte Version)</p>
                        </div>
                    </a>
                </li>
                <li>
                    <div
                        id="cw-import-from-course"
                        class="cw-action-menu-button"
                        title="importFromCourseExplain"
                        @click="importFromCourse"
                    >
                        <p>{{ courseImportText }}</p>
                    </div>
                </li>
                <li>
                    <a :href="coursewareExportURL">
                        <div id="cw-export-download" class="cw-action-menu-button" title="exportExplain">
                            <p>Export-Archiv herunterladen</p>
                        </div>
                    </a>
                </li>
                <li>
                    <a :href="coursewareExportHtmlURL">
                        <div id="cw-export-html-download" class="cw-action-menu-button" title="exportHtmlExplain">
                            <p>Html-Export herunterladen</p>
                        </div>
                    </a>
                </li>
            </ul>
            <div v-if="(showRemoteCourseware || showImportCourseware) && loading">
                <spring-spinner :animation-duration="3000" :size="65" :color="'#28497c'" class="cw-action-loading" />
            </div>
            <div class="cw-action-button-wrapper">
                <button
                    v-if="(showRemoteCourseware || showImportCourseware || showExportCourseware) && !loading"
                    class="button"
                    id="cw-reset-action-menu"
                    @click="resetActionMenu"
                >
                    zurück zur Auswahl
                </button>
                <button
                    id="cw-import"
                    class="button"
                    v-if="showImportCourseware && !fileError && !loading"
                    @click="importCompleteArchive"
                >
                    Komplettes Archiv importieren
                </button>
                <a :href="coursewareExportURL" v-if="showExportCourseware">
                    <button id="cw-export" class="button">
                        Exportieren
                    </button>
                </a>
                <div style="clear: both;"></div>
            </div>
        </div>
         <div class="cw-blockmanager-wrapper">
            <div class="cw-blockmanager-title cw-blockmanager-help" :class="{unfolded: helpUnfolded}" @click="helpUnfolded = !helpUnfolded">
                <p>Hilfe</p>
            </div>
            <div class="helpboxes" v-show="helpUnfolded">
                <HelpBox 
                    title="Courseware Verwaltung" 
                    content="Unter Verwaltung können Kapitel, Unterkapitel, 
                    Abschnitte und Blöcke sortiert werden. Im Gegensatz zum 
                    Bearbeitungsmodus, in der die Reihenfolge der Elemente 
                    bearbeitet werden kann, können hier Elemente in andere 
                    übergeordnete Elemente einsortiert werden. Mit einem Klick 
                    öffnen Sie das Element, die enthaltenen Elemente werden 
                    dargestellt. Hinter den drei Punkten verbirgt sich das 
                    Aktionsmenü, Hinzufügen, Löschen, Bearbeiten und das 
                    Vergeben von Schreibrechten ist hier möglich."
                />
                <HelpBox 
                    title="Sortieren und Verschieben" 
                    content="Elemente lassen sich einfach per Drag&Drop 
                    verschieben. Hierbei ist es auch möglich ein Element 
                    in ein anderes übergeordnetes Element zu verschieben. 
                    Eine Verschiebung ist immer nur auf der passenden Ebene 
                    möglich. Es ist z.B. nicht möglich einen Abschnitt neben 
                    ein Unterkapitel zu verschieben." 
                />
                <HelpBox 
                    title="Freigaben für Teilnehmende" 
                    content="Ein weiterer Schritt in Richtung kollaboratives 
                    Lernen macht Courseware mit Freigaben für Teilnehmende 
                    und Gruppen. Ein gleichzeitiges Bearbeiten von Blöcken 
                    ist derzeit noch nicht verfügbar, daher sollten die 
                    Freigaben entsprechend gewählt werden. Freigegeben werden 
                    können Kapitel und Unterkapitel, dies kann über das 
                    Aktionsmenü (am Element hinter den drei Punkten) geschehen. 
                    Untergeordnete Elemente werden automatisch freigegeben. 
                    Teilnehmende können dann innerhalb des freigegeben Elements 
                    Inhalte erstellen und Unterelemente erzeugen. Verwaltung, 
                    Einstellungen und Diskussionsübersicht sind für Teilnehmende 
                    nicht sichtbar." 
                />
                <HelpBox 
                    title="Hinzufügen und Löschen" 
                    content="Über das Aktionsmenü (verbirgt sich hinter den drei Punkten) 
                    eines Elementes können Unterelemente hinzugefügt werden, 
                    Blöcke können allerdings nur im Bearbeitungsmodus der 
                    Courseware hinzugefügt werden. Löschen lassen sich alle 
                    Elemente über das Aktionsmenü."
                />
                <HelpBox 
                    title="Archiv importieren"
                    content="Ein zuvor exportiertes Archiv kann über Courseware-Archiv 
                    importieren“ eingelesen werden. Nachdem die Daten geladen wurden 
                    wird die Struktur der Courseware dargestellt. Per Drag&Drop lassen 
                    sich einzelne Elemente importieren. Über die Schaltfläche 
                    „Komplettes Archiv importieren“ können Sie den gesamten Inhalt 
                    importieren. Die Kapitel werden dann an das Ende der bestehenden 
                    Inhalte gesetzt. Bitte beachten Sie das der Upload von großen 
                    Archiven unter Umständen viel Zeit in Anspruch nehmen kann. Für 
                    das Kopieren von Inhalten aus einer anderen Veranstaltung nutzen 
                    Sie bitte „Aus Veranstaltung importieren“."
                />
                <HelpBox 
                    title="Aus Veranstaltung importieren"
                    content="Um Inhalte aus einer anderen Veranstaltung, in der Sie 
                    unter den Lehrenden eingetragen sind, zu importieren, wählen 
                    Sie unter Import & Export „Aus Veranstaltung importieren“. Eine 
                    Liste von Semestern wird angezeigt. Mit einem Klick auf ein Semester 
                    werden die verfügbaren Veranstaltungen dargestellt. Wählen Sie 
                    eine Veranstaltung aus um sich die Courseware-Inhalte dieser 
                    Veranstaltung anzeigen zu lassen. Inhalte können per Drag&Drop 
                    in die aktuelle Veranstaltung kopiert werden. Gegebenenfalls 
                    müssen Sie die Elemente der Courseware aufklappen um Elemente 
                    kopieren zu können." 
                />
                <HelpBox 
                    title="Archiv exportieren" 
                    content="Ein Courseware Archiv ist eine Zip-Datei die eine 
                    Beschreibung der Courseware (data.xml) sowie alle eingebundenen 
                    Dateien enthält. Die Zip-Datei kann über den Import eingelesen 
                    werden um Inhalte in anderen Stud.IP Installationen zu verwenden. 
                    Für das Kopieren von Inhalten aus einer anderen Veranstaltung 
                    nutzen Sie bitte „Aus Veranstaltung importieren“."
                />
                <HelpBox 
                    title="HTML exportieren" 
                    content="'Der HTML Export liefert die Inhalte von Coursware sowie 
                    den Courseware Player aus. Mit dem Courseware Player ist es möglich 
                    die Inhalte mit einem Browser direkt aus dem Dateisystem eines 
                    Rechners wiederzugeben. Der Courseware Player kann auch auf einem 
                    Webserver verwendet werden. Bitte beachten Sie das nicht alle Blöcke 
                    im Courseware Player verfügbar sind. Audio Galerie, Bestätigung, Forum, 
                    Blubber, Diskussion & Kommentare, Suche, Quiz und OpenCast können im 
                    Moment noch nicht wiedergegeben werden. Aufgaben die im interactive 
                    Video eingebunden wurden können auch nicht wiedergegeben werden."
                />
            </div>
        </div>
        <div id="errorbox" class="cw-blockmanager-wrapper" v-if="errorOccurred.length > 0">
            <div id="cw-error-title" class="cw-blockmanager-title">
                Meldungen
            </div>
            <ul>
                <li v-for="(error, errorkey) in errorOccurred" :key="errorkey">{{error.date}} | {{error.text}} </li>
            </ul>
        </div>
    </div>
</template>

<script>
import ChapterItem from './components/ChapterItem.vue';
import SemesterItem from './components/SemesterItem.vue';
import ActionMenuItem from './components/ActionMenuItem.vue';
import HelpBox from './components/HelpBox.vue';
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
        SpringSpinner,
        HelpBox
    },
    data() {
        return {
            courseware: {},
            remoteCourses: {},
            remoteCourseware: null,
            importCourseware: null,
            showRemoteCourseware: false,
            showImportCourseware: false,
            showExportCourseware: false,
            chapters: [],
            chapterList: [],
            subchapterList: {},
            sectionList: {},
            blockList: {},
            actionTitle: 'Import & Export',
            courseImportText: 'Aus Veranstaltung importieren',
            remoteData: false,
            importData: false,
            importXML: '',
            importZIP: '',
            blockMap: null,
            fileError: false,
            loading: false,
            storeLock: false,
            errorOccurred: [],
            coursewareExportURL: COURSEWARE.data.courseware_export_url,
            coursewareImportURL: COURSEWARE.data.courseware_import_url,
            coursewareExportHtmlURL: COURSEWARE.data.courseware_export_html_url,
            helpUnfolded: false,

            courseUsers: [],
            courseGroups: []
        };
    },
    created() {
        this.courseware = JSON.parse(COURSEWARE.data.courseware);
        this.remoteCourses = JSON.parse(COURSEWARE.data.remote_courses);
        this.blockMap = JSON.parse(COURSEWARE.data.block_map);
    },
    mounted() {
        this.getCourseUsers();
        this.getCourseGroups();
    },
    watch: {
        courseware: function() {
            if (this.courseware.children != null) {
                this.chapters = this.courseware.children;
            } else {
                this.chapters = [];
            }
        }
    },
    methods: {
        getCourseUsers() {
            let view = this;
            axios
                .get('get_course_users', {
                    params: {
                        cid: COURSEWARE.config.cid
                    }
                })
                .then(function(response) {
                    view.courseUsers = response.data;
                })
                .catch(function(error) {
                    console.log(error);
                    return [];
                });
        },
        getCourseGroups() {
            let view = this;
            axios
                .get('get_course_groups', {
                    params: {
                        cid: COURSEWARE.config.cid
                    }
                })
                .then(function(response) {
                    view.courseGroups = response.data;
                })
                .catch(function(error) {
                    console.log(error);
                    return [];
                });
        },
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
                    view.chapterList.push(parseInt(element.id, 10));
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
            let chapters = [];
            this.chapters.forEach(element => {
                    chapters.push(element);
            });
            chapters.push(data);
            this.chapters = chapters;
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
        showExport() {
            this.actionTitle = 'Export-Archiv herunterladen';
            this.showExportCourseware = true;
        },
        resetActionMenu() {
            this.actionTitle = 'Import & Export';
            this.fileError = false;
            this.remoteCourseware = null;
            this.importCourseware = null;
            this.exportCourseware = null;
            this.showRemoteCourseware = false;
            this.showImportCourseware = false;
            this.showExportCourseware = false;
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
                        try {
                            view.courseware = JSON.parse(response.data.courseware);
                            let errors = response.data.errors;
                            if (errors != null) {
                                errors.forEach(error => {
                                    view.addError(error);
                                });
                            }
                        }
                        catch(e) {
                            console.log(e);
                            console.log('try to reload courseware data');
                            view.addError('Ein unbekannter Fehler ist aufgetreten');
                            view.addError('Der Import von Courseware-Daten ist möglicherweise fehlgeschlagen, bitte prüfen Sie den Inhalt');
                            axios
                            .post('get_courseware', {
                                cid: COURSEWARE.config.cid
                            })
                            .then(response => {
                                view.courseware = JSON.parse(response.data.courseware);
                                view.addError('Der Import von Courseware-Daten ist möglicherweise fehlgeschlagen, bitte prüfen Sie den Inhalt');
                            })
                            .catch(error => {
                                if (error.response) {
                                    console.log(error.response.status);
                                    if (error.response.status == 500) {
                                        view.addError('Der Server konnte die Daten nicht verarbeiten (500)');
                                    }
                                } else if (error.request) {
                                    console.log(error.request);
                                } else {
                                    console.log(error.message);
                                }
                                console.log(error.config);
                            });
                        }
                        view.storeLock = false;
                    })
                    .catch(error => {
                        if (error.response) {
                            // The request was made and the server responded with a status code
                            // that falls out of the range of 2xx
                            // console.log(error.response.data);
                            console.log(error.response.status);
                            // console.log(error.response.headers);
                            if (error.response.status == 500) {
                                view.storeLock = false;
                                view.addError('Der Server konnte die Daten nicht verarbeiten (500)');
                            }
                        } else if (error.request) {
                            // The request was made but no response was received
                            // `error.request` is an instance of XMLHttpRequest in the browser and an instance of
                            // http.ClientRequest in node.js
                            console.log(error.request);
                        } else {
                            // Something happened in setting up the request that triggered an Error
                            console.log(error.message);
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
                                                        preview: view.getContent(node, unziped),
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
        },
        importCompleteArchive() {
            this.storeLock = true;
            let view = this;
            let promises = [];
            let fileData = {};
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
            Promise.all(promises).then(function() {
                axios
                    .post('import_complete_archive', {
                        cid: COURSEWARE.config.cid,
                        fileData: fileData
                    })
                    .then(response => {
                        view.importData = false;
                        view.remoteData = false;
                        view.cleanLists();
                        view.courseware = JSON.parse(response.data.courseware);
                        let errors = response.data.errors;
                        if (errors != null) {
                            errors.forEach(error => {
                                view.addError(error);
                            });
                        }
                        view.storeLock = false;
                    })
                    .catch(error => {
                        console.log(error);
                    });
            });
        },
        addError(error)
        {
            let today = new Date();
            let date = this.setDateZero(today.getDay()) + '.' + this.setDateZero(today.getMonth()) + '.' + today.getFullYear() + ' ' + this.setDateZero(today.getHours()) + ':' +this.setDateZero(today.getMinutes())+ ':' + this.setDateZero(today.getSeconds());
            this.errorOccurred.push({text: error, date: date});
        },
        setDateZero(date){
            return date < 10 ? '0' + date : date;
        },
        clearErrors()
        {
            this.errorOccurred = [];
        }
    }
};
</script>

import Vue from 'vue';
import VueI18n from 'vue-i18n';
Vue.use(VueI18n);
const messages = {
    en_GB: {
        message: {
            tasksBackButton: 'back to the selection',
            importFromCourse: 'Import from Course',
            importFromCourseExplain: 'Import content from another event where you are a lecturer',
            importFromArchiveButton: 'Upload import archive',
            importFromArchiveExplain: 'Upload a file that you have previously exported from a courseware',
            visibleFrom: 'visible from',
            invisibleFrom: 'invisible from',
            chapter: 'Chapter',
            subchapter: 'Subchapter',
            section: 'Section',
            block: 'Block',
            emptyChapter: 'This Chapter is empty',
            emptyChapterInfo: 'You can drop a subchapter here or add a new one',
            emptySubchapter: 'This Subchapter is empty',
            emptySubchapterInfo: 'You can drop a section here or add a new one',
            emptySection: 'This Section is empty',
            emptySectionInfo: 'You can add a Block in Courseware or drop one here',
            actions: 'Actions',
            addSubelement: 'Add subelement',
            editElement: 'Edit element',
            deleteElement: 'Delete element',
            setStudentsPermissions: 'Set write permissions for students',
            setGroupsPermissions: 'Set write permissions for groups',
            editDialogTitle: 'Edit {0}',
            deleteDialogTitle: 'Delete {0}',
            addDialogTitle: 'Add Subelement',
            ButtonLabelSave: 'save',
            ButtonLabelClose: 'close',
            ButtonLabelYes: 'yes',
            ButtonLabelNo: 'no',
            title: 'Title',
            deleteDialogQuestionBlock: 'Do you really want to delete this item',
            deleteDialogQuestionElement: 'Are you sure you want to delete this element and all its underlying elements',
            exportButton: 'Download export archive',
            exportExplain: 'Select content and download it as an archive file',
            exportingButton: 'Export',
            importingButton: 'Import complete archive'
        }
    },
    de_DE: {
        message: {
            tasksBackButton: 'zurück zur Auswahl',
            importFromCourse: 'Aus Veranstaltung importieren',
            importFromCourseExplain: 'Importieren Sie Inhalte aus einer anderen Veranstaltung in der Sie Dozent sind',
            importFromArchiveButton: 'Import-Archiv hochladen',
            importFromArchiveExplain: 'Laden Sie eine Datei hoch, die Sie zuvor aus einer Courseware exportiert haben',
            visibleFrom: 'sichtbar ab',
            invisibleFrom: 'unsichtbar ab',
            chapter: 'Kapitel',
            subchapter: 'Unterkapitel',
            section: 'Abschnitt',
            block: 'Block',
            emptyChapter: 'Dieses Kapitel ist leer',
            emptyChapterInfo: 'Sie können hier ein Unterkapitel ablegen oder ein neues hinzufügen',
            emptySubchapter: 'Dieses Unterkapitel ist leer',
            emptySubchapterInfo: 'Sie können hier einen Abschnitt ablegen oder einen neuen hinzufügen',
            emptySection: 'Dieser Abschnitt ist leer',
            emptySectionInfo: 'Sie können einen Block in Courseware hinzufügen oder hier einen ablegen',
            actions: 'Aktionen',
            addSubelement: 'Unterelement hinzufügen',
            editElement: 'Element bearbeiten',
            deleteElement: 'Element löschen',
            setStudentsPermissions: 'Schreibrechte für Studierenden festlegen',
            setGroupsPermissions: 'Schreibrechte für Gruppen festlegen',
            editDialogTitle: '{0} bearbeiten',
            deleteDialogTitle: '{0} löschen',
            addDialogTitle: 'Unterelement hinzufügen',
            ButtonLabelSave: 'Speichern',
            ButtonLabelClose: 'Abbrechen',
            ButtonLabelYes: 'Ja',
            ButtonLabelNo: 'Nein',
            title: 'Titel',
            deleteDialogQuestionBlock: 'Möchten Sie dieses Element wirklich löschen',
            deleteDialogQuestionElement:
                'Möchten Sie dieses Element und alle darunter liegenden Elemente wirklich löschen',
            exportButton: 'Export-Archiv herunterladen',
            exportExplain: 'Wählen Sie Inhalte aus und laden diese als Archiv-Datei herunter',
            exportingButton: 'Exportieren',
            importingButton: 'Komplettes Archiv importieren'
        }
    }
};

// Create VueI18n instance with options
const i18n = new VueI18n({
    locale: COURSEWARE.data.lang, // set locale
    messages // set locale messages
});

export default i18n;

<template>
    <li
        class="subchapter-item"
        :class="{
            'subchapter-item-import': importContent,
            'subchapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div
            class="subchapter-description subchapter-handle"
            :class="{
                'full-width': importContent || remoteContent,
                unfolded: unfolded
            }"
            @click="toggleContent"
        >
            <p class="subchapter-title" :title="title">{{ shortTitle }}</p>
            <p class="header-info-wrapper">
                <span
                    :class="{
                        'unpublished-info': !isPublished && (publication_date || withdraw_date),
                        'published-info': isPublished && (publication_date || withdraw_date)
                    }"
                >
                    {{ $t('message.subchapter') }}
                </span>
                <span
                    v-if="publication_date && !this.importContent && !this.remoteContent"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                >
                    | {{ $t('message.visibleFrom') }}: {{ publication_date_readable }}</span
                >
                <span
                    v-if="withdraw_date && !this.importContent && !this.remoteContent"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                >
                    | {{ $t('message.invisibleFrom') }}: {{ withdraw_date_readable }}</span
                >
            </p>
        </div>
        <ActionMenuItem
            v-if="!this.importContent && !this.remoteContent"
            :buttons="['edit', 'remove', 'groups', 'users', 'add-child']"
            :element="this.element"
            @edit="editElement"
            @remove="removeElement"
            @add-child="addChild"
            :class="{ unfolded: unfolded }"
        />

        <draggable
            tag="ul"
            v-if="unfolded"
            :list="sections"
            :group="draggableGroup"
            v-bind="dragOptions"
            class="section-list"
            :class="{ 'section-list-import': importContent }"
            ghost-class="ghost"
            handle=".section-handle"
            @sort="sortSections"
        >
            <SectionItem
                v-for="section in sections"
                :key="section.id"
                :element="section"
                :importContent="importContent"
                :remoteContent="remoteContent"
                :storeLock="storeLock"
                @listUpdate="updateList"
                @remove-section="removeSection"
                @isRemote="isRemoteAction"
                @isImport="isImportAction"
            />
            <p v-if="sections.length == 0">
                {{ $t('message.emptySubchapter') }}.
                <span v-if="!importContent">{{ $t('message.emptySubchapterInfo') }}.</span>
            </p>
        </draggable>
    </li>
</template>

<script>
import SectionItem from './SectionItem.vue';
import ActionMenuItem from './ActionMenuItem.vue';
import blockManagerHelperMixin from './../mixins/blockManagerHelperMixin.js';
import draggable from 'vuedraggable';
export default {
    name: 'SubchapterItem',
    mixins: [blockManagerHelperMixin],
    data() {
        return {
            id: this.element.id,
            publication_date: this.element.publication_date,
            publication_date_readable: this.getReadableDate(this.element.publication_date),
            withdraw_date: this.element.withdraw_date,
            withdraw_date_readable: this.getReadableDate(this.element.withdraw_date),
            isPublished: this.element.isPublished,
            title: this.element.title,
            shortTitle: this.element.shortTitle,
            unfolded: false,
            sections: this.element.children,
            sectionList: {},
            draggableGroup: { name: 'sections' }
        };
    },
    components: {
        SectionItem,
        ActionMenuItem,
        draggable
    },
    props: {
        element: Object,
        importContent: Boolean,
        remoteContent: Boolean,
        storeLock: Boolean
    },
    created() {
        if (this.sections == null) {
            this.sections = [];
        }
        if (this.importContent) {
            this.draggableGroup = { name: 'sections', pull: 'clone', put: false };
        }
        this.shortTitle = this.cutTitle(this.element.title, 30);
    },
    watch: {
        element: function() {
            if (this.element.children == null) {
                this.sections = [];
            } else {
                this.sections = this.element.children;
            }
        }
    },
    methods: {
        sortSections() {
            let view = this;
            let args = {};
            let sectionList = [];
            let list = [];
            let hasChildren = false;
            this.sections.forEach(element => {
                if (element.isRemote) {
                    list.push('remote_' + element.id);
                    view.isRemoteAction();
                    if (element.children != null) {
                        hasChildren = true;
                        view.buildChildrenList(element, 'remote_');
                    }
                } else if (element.isImport) {
                    list.push('import_' + element.id);
                    view.isImportAction();
                    if (element.children.length != null) {
                        hasChildren = true;
                        view.buildChildrenList(element, 'import_');
                    }
                } else {
                    list.push(element.id);
                }
            });
            sectionList[this.id] = list;
            args.list = sectionList;
            args.hasChildren = hasChildren;
            args.type = 'section';
            this.$emit('listUpdate', args);
        },
        buildChildrenList(element, type) {
            let view = this;
            let list = [];
            let updateList = [];
            let args = {};
            let hasChildren = false;
            element.children.forEach(child => {
                list.push(type + child.id);
                if (child.children != null) {
                    hasChildren = true;
                    view.buildChildrenList(child, type);
                }
            });
            updateList[type + element.id] = list;
            args.list = updateList;
            args.hasChildren = hasChildren;
            if (element.type.toLowerCase() == 'section') {
                args.type = 'block';
                this.$emit('listUpdate', args);
            } else {
                args.type = element.children[0].type.toLowerCase();
                this.$emit('listUpdate', args);
            }
        },
        updateList(args) {
            this.$emit('listUpdate', args);
        },
        isRemoteAction() {
            this.$emit('isRemote');
        },
        isImportAction() {
            this.$emit('isImport');
        },
        removeElement() {
            this.$emit('remove-subchapter', this.element);
        },
        editElement(data) {
            this.title = data.title;
            this.shortTitle = this.cutTitle(data.title);
            this.publication_date = data.publication_date * 1000;
            this.publication_date_readable = data.publication_date_readable;
            this.withdraw_date = data.withdraw_date * 1000;
            this.withdraw_date_readable = data.withdraw_date_readable;
            this.isPublished = data.isPublished;
        },
        addChild(data) {
            this.sections.push(data);
        },
        removeSection(data) {
            let sections = [];
            this.sections.forEach(element => {
                if (element.id != data.id) {
                    sections.push(element);
                }
            });
            this.sections = sections;
        },
        toggleContent() {
            this.unfolded = !this.unfolded;
        }
    },
    computed: {
        dragOptions() {
            if (this.importContent) {
                return {
                    animation: 200,
                    disabled: this.storeLock,
                    sort: false,
                    ghostClass: 'ghost'
                };
            } else {
                return {
                    animation: 200,
                    disabled: this.storeLock,
                    ghostClass: 'ghost'
                };
            }
        }
    }
};
</script>

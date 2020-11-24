<template>
    <li
        class="chapter-item"
        :class="{
            'chapter-item-import': importContent,
            'chapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div
            class="chapter-description chapter-handle"
            :class="{
                'full-width': importContent || remoteContent,
                unfolded: unfolded
            }"
            @click="toggleContent"
        >
            <p class="chapter-title" :title="title">
                {{ shortTitle }}
            </p>
            <p class="header-info-wrapper">
                <span
                    :class="{
                        'unpublished-info': !isPublished && (publication_date || withdraw_date),
                        'published-info': isPublished && (publication_date || withdraw_date)
                    }"
                >
                    {{ $t('message.chapter') }}
                </span>
                <span
                    v-if="publication_date && !this.importContent && !this.remoteContent"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                    >| {{ $t('message.visibleFrom') }}: {{ publication_date_readable }}</span
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
            :list="subchapters"
            :group="draggableGroup"
            v-bind="dragOptions"
            class="subchapter-list"
            :class="{ 'subchapter-list-import': importContent }"
            ghost-class="ghost"
            handle=".subchapter-handle"
            @sort="sortSubchapters"
        >
            <SubchapterItem
                v-for="elementChild in subchapters"
                :key="elementChild.id"
                :element="elementChild"
                :importContent="importContent"
                :remoteContent="remoteContent"
                :storeLock="storeLock"
                @listUpdate="updateList"
                @remove-subchapter="removeSubchapter"
                @isRemote="isRemoteAction"
                @isImport="isImportAction"
            />
            <p v-if="subchapters.length == 0">
                {{ $t('message.emptyChapter') }}.
                <span v-if="!importContent">{{ $t('message.emptyChapterInfo') }}.</span>
            </p>
        </draggable>
    </li>
</template>

<script>
import SubchapterItem from './SubchapterItem.vue';
import ActionMenuItem from './ActionMenuItem.vue';
import blockManagerHelperMixin from './../mixins/blockManagerHelperMixin.js';
import draggable from 'vuedraggable';
export default {
    name: 'ChapterItem',
    mixins: [blockManagerHelperMixin],
    props: {
        element: Object,
        importContent: Boolean,
        remoteContent: Boolean,
        storeLock: Boolean
    },
    data() {
        return {
            id: this.element.id,
            publication_date: this.element.publication_date,
            publication_date_readable: this.getReadableDate(this.element.publication_date),
            withdraw_date: this.element.withdraw_date,
            withdraw_date_readable: this.getReadableDate(this.element.withdraw_date),
            isPublished: this.element.isPublished,
            title: this.element.title,
            shortTitle: this.cutTitle(this.element.title, 30),
            unfolded: false,
            subchapters: this.element.children,
            subchapterList: [],
            draggableGroup: { name: 'subchapters' }
        };
    },
    components: {
        SubchapterItem,
        ActionMenuItem,
        draggable
    },
    created() {
        if (this.subchapters == null) {
            this.subchapters = [];
        }
        if (this.importContent) {
            this.draggableGroup = { name: 'subchapters', pull: 'clone', put: false };
        }
    },
    watch: {
        element: function() {
            if (this.element.children == null) {
                this.subchapters = [];
            } else {
                this.subchapters = this.element.children;
            }
        }
    },
    methods: {
        sortSubchapters() {
            let view = this;
            let args = {};
            let subchapterList = [];
            let list = [];
            let hasChildren = false;
            this.subchapters.forEach(element => {
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
                    if (element.children != null) {
                        hasChildren = true;
                        view.buildChildrenList(element, 'import_');
                    }
                } else {
                    list.push(element.id);
                }
            });
            subchapterList[this.id] = list;
            args.list = subchapterList;
            args.hasChildren = hasChildren;
            args.type = 'subchapter';
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
        isRemoteAction() {
            this.$emit('isRemote');
        },
        isImportAction() {
            this.$emit('isImport');
        },
        updateList(args) {
            this.$emit('listUpdate', args);
        },
        removeElement() {
            this.$emit('remove-chapter', this.element);
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
            this.subchapters.push(data);
        },
        removeSubchapter(data) {
            let subchapters = [];
            this.subchapters.forEach(element => {
                if (element.id != data.id) {
                    subchapters.push(element);
                }
            });
            this.subchapters = subchapters;
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

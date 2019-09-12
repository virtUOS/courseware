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
                'full-width': importContent,
                'full-width': remoteContent,
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
                    Kapitel
                </span>
                <span
                    v-if="publication_date && !this.importContent && !this.remoteContent"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                    >| sichtbar ab: {{ publication_date_readable }}</span
                >
                <span
                    v-if="withdraw_date && !this.importContent && !this.remoteContent"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                >
                    | unsichtbar ab: {{ withdraw_date_readable }}</span
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
            @set-users="setUserApproval"
            @set-groups="setGroupApproval"
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
        >
            <SubchapterItem
                v-for="elementChild in subchapters"
                :key="elementChild.id"
                :element="elementChild"
                :importContent="importContent"
                :remoteContent="remoteContent"
                @blockListUpdate="updateBlockList"
                @sectionListUpdate="updateSectionList"
                @updateParentList="updateParentList"
                @remove-subchapter="removeSubchapter"
                @isRemote="isRemoteAction"
            />
            <p v-if="subchapters.length == 0">
                This Chapter is empty. You can drop a subchapter here or add a new one.
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
        remoteContent: Boolean
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
        if (this.remoteContent && this.element.isRemote) {
            this.draggableGroup = { name: 'subchapters', pull: 'clone', put: false };
        }

        if (!this.remoteContent && this.element.isRemote) {
            this.id = 'remote-' + this.id;
            if (this.element.children) {
                let subchapters = [];
                let subchapterList = [];
                this.element.children.forEach(subchapter => {
                    subchapters.push('remote-' + subchapter.id);
                    if (subchapter.children) {
                        let sections = [];
                        let sectionList = [];
                        subchapter.children.forEach(section => {
                            sections.push('remote-' + section.id);
                            if (section.children) {
                                let blocks = [];
                                let blockList = [];
                                section.children.forEach(block => {
                                    blocks.push('remote-' + block.id);
                                });
                                blockList['remote-' + section.id] = blocks;
                                this.$emit('blockListUpdate', blockList);
                            }
                        });
                        sectionList['remote-' + subchapter.id] = sections;
                        this.$emit('sectionListUpdate', sectionList);
                    }
                });
                subchapterList[this.id] = subchapters;
                this.$emit('subchapterListUpdate', subchapterList);
            }
        }
    },
    watch: {
        subchapters: function() {
            let list = [];
            this.subchapters.forEach(element => {
                list.push(element.id);
            });
            this.subchapterList[this.id] = list;
            this.$emit('subchapterListUpdate', this.subchapterList);
        }
    },
    methods: {
        updateBlockList(data) {
            this.$emit('blockListUpdate', data);
        },
        updateSectionList(data) {
            this.$emit('sectionListUpdate', data);
        },
        updateParentList() {
            let list = [];
            let view = this;
            this.subchapters.forEach(element => {
                if (element.isRemote) {
                    list.push('remote-' + element.id);
                    view.$emit('isRemote');
                } else {
                    list.push(element.id);
                }
            });
            this.subchapterList[this.id] = list;
            this.$emit('subchapterListUpdate', this.subchapterList);
        },
        isRemoteAction() {
            this.$emit('isRemote');
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
        setUserApproval(data) {
            console.log(data);
        },
        setGroupApproval(data) {
            console.log(data);
        },
        toggleContent() {
            this.unfolded = !this.unfolded;
        }
    },
    computed: {
        dragOptions() {
            if (this.remoteContent && this.element.isRemote) {
                return {
                    animation: 200,
                    disabled: false,
                    sort: false,
                    ghostClass: 'ghost'
                };
            } else {
                return {
                    animation: 200,
                    disabled: false,
                    ghostClass: 'ghost'
                };
            }
        }
    }
};
</script>

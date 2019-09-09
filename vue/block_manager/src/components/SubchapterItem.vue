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
                'full-width': importContent,
                'full-width': remoteContent,
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
                    Unterkapitel
                </span>
                <span
                    v-if="publication_date"
                    :class="{
                        'unpublished-info': !isPublished,
                        'published-info': isPublished
                    }"
                >
                    | sichtbar ab: {{ publication_date_readable }}</span
                >
                <span
                    v-if="withdraw_date"
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
            :list="sections"
            :group="{ name: 'sections' }"
            v-bind="dragOptions"
            class="section-list"
            :class="{ 'section-list-import': importContent }"
            ghost-class="ghost"
            handle=".section-handle"
            :move="checkMove"
            @start="dragging = true"
            @sort="sortItem"
            @end="finishMove"
        >
            <SectionItem
                v-for="section in sections"
                :key="section.id"
                :element="section"
                :importContent="importContent"
                :remoteContent="remoteContent"
                @blockListUpdate="updateBlockList"
                @remove-section="removeSection"
            />
            <p v-if="sections.length == 0">This Subchapter is empty. You can drop a section here or add a new one.</p>
        </draggable>
    </li>
</template>

<script>
import SectionItem from './SectionItem.vue';
import ActionMenuItem from './ActionMenuItem.vue';
import BlockManagerHelper from './../assets/BlockManagerHelper';
import draggable from 'vuedraggable';
import axios from 'axios';
export default {
    name: 'SubchapterItem',
    data() {
        return {
            id: this.element.id,
            publication_date: this.element.publication_date,
            publication_date_readable: BlockManagerHelper.getReadableDate(this.element.publication_date),
            withdraw_date: this.element.withdraw_date,
            withdraw_date_readable: BlockManagerHelper.getReadableDate(this.element.withdraw_date),
            isPublished: this.element.isPublished,
            title: this.element.title,
            shortTitle: this.element.shortTitle,
            unfolded: false,
            sections: this.element.children,
            sectionList: {},
            dragging: false
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
        remoteContent: Boolean
    },
    created() {
        if (this.sections == null) {
            this.sections = [];
        }
        if (this.importContent && !this.remoteContent) {
            this.id = 'import-' + this.id;
        }
        if (this.importContent && this.remoteContent) {
            this.id = 'remote-' + this.id;
        }
        this.shortTitle = BlockManagerHelper.shortTitle(this.element.title, 30);
    },
    watch: {
        sections: function() {
            if (this.sections == null) {
                this.sections = [];
            }
            let list = [];
            this.sections.forEach(element => {
                list.push(element.id);
            });
            this.sectionList[this.id] = list;
        }
    },
    methods: {
        updateBlockList(data) {
            this.$emit('blockListUpdate', data);
        },
        checkMove() {},
        sortItem() {
            this.$emit('sectionListUpdate', this.sectionList);
        },
        finishMove() {
            this.dragging = false;
            //this.storeSectionMove();
        },
        storeSectionMove() {
            let view = this;
            axios
                .post('store_element_move', {
                    cid: COURSEWARE.config.cid,
                    elementList: JSON.stringify(view.sectionList),
                    type: 'Section'
                })
                .then(data => {
                    console.log(data.response);
                })
                .catch(error => {
                    console.log('there was an error: ' + error.response);
                });
        },
        removeElement() {
            this.$emit('remove-subchapter', this.element);
        },
        editElement(data) {
            this.title = data.title;
            this.shortTitle = BlockManagerHelper.shortTitle(data.title);
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
            return {
                animation: 200,
                group: 'description',
                disabled: false,
                ghostClass: 'ghost'
            };
        }
    }
};
</script>

<template>
    <li
        class="section-item"
        :class="{
            'section-item-import': importContent,
            'section-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div
            class="section-description section-handle"
            :class="{
                'full-width': importContent,
                'full-width': remoteContent,
                unfolded: unfolded
            }"
            @click="toggleContent"
        >
            <p class="section-title" :title="title">{{ shortTitle }}</p>
            <p class="header-info-wrapper">Abschnitt</p>
        </div>
        <ActionMenuItem
            v-if="!this.importContent && !this.remoteContent"
            :buttons="['edit', 'remove']"
            :element="this.element"
            @edit="editSection"
            @remove="removeElement"
            :class="{ unfolded: unfolded }"
        />
        <draggable
            tag="ul"
            v-if="unfolded"
            :list="blocks"
            :group="{ name: 'blocks' }"
            v-bind="dragOptions"
            class="block-list"
            :class="{ 'block-list-import': importContent }"
            ghost-class="ghost"
            handle=".block-handle"
            :move="checkMove"
            @start="dragging = true"
            @sort="sortItem"
            @end="finishMove"
        >
            <!-- <transition-group type="transition" :name="!dragging ? 'flip-list' : null"> -->
            <BlockItem
                v-for="block in blocks"
                :key="block.id"
                :block="block"
                :importContent="importContent"
                :remoteContent="remoteContent"
                @remove-block="removeBlock"
            />
            <p v-if="blocks.length == 0">This Section is empty. You can add Block in Courseware or drop on here.</p>
            <!-- </transition-group> -->
        </draggable>
    </li>
</template>

<script>
import BlockItem from './BlockItem.vue';
import ActionMenuItem from './ActionMenuItem.vue';
import BlockManagerHelper from './../assets/BlockManagerHelper';
import draggable from 'vuedraggable';
import axios from 'axios';
export default {
    name: 'SectionItem',
    data() {
        return {
            id: this.element.id,
            title: this.element.title,
            shortTitle: this.element.shortTitle,
            unfolded: false,
            blocks: this.element.children,
            blockList: {},
            dragging: false
        };
    },
    components: {
        BlockItem,
        ActionMenuItem,
        draggable
    },
    props: {
        element: Object,
        importContent: Boolean,
        remoteContent: Boolean
    },
    created() {
        if (this.blocks == null) {
            this.blocks = [];
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
        blocks: function() {
            let list = [];
            this.blocks.forEach(element => {
                list.push(element.id);
            });
            this.blockList[this.id] = list;
        }
    },
    methods: {
        checkMove() {},
        sortItem() {
            console.log('sort in ' + this.id);
            this.$emit('blockListUpdate', this.blockList);
        },
        finishMove() {
            this.dragging = false;
        },
        storeBlockMove() {
            let view = this;
            axios
                .post('store_element_move', {
                    cid: COURSEWARE.config.cid,
                    elementList: JSON.stringify(view.blockList),
                    type: 'Block'
                })
                .then(data => {
                    console.log(data.response);
                })
                .catch(error => {
                    console.log('there was an error: ' + error.response);
                });
        },
        removeBlock(data) {
            let blocks = [];
            this.blocks.forEach(element => {
                if (element.id != data.id) {
                    blocks.push(element);
                }
            });
            this.blocks = blocks;
        },
        removeElement() {
            this.$emit('remove-section', this.element);
        },
        editSection(data) {
            this.title = data.title;
            this.shortTitle = BlockManagerHelper.shortTitle(data.title, 30);
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

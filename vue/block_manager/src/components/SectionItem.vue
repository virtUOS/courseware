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
                'full-width': importContent || remoteContent,
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
            :group="draggableGroup"
            v-bind="dragOptions"
            class="block-list"
            :class="{ 'block-list-import': importContent }"
            ghost-class="ghost"
            handle=".block-handle"
        >
            <!-- <transition-group type="transition" :name="!dragging ? 'flip-list' : null"> -->
            <BlockItem
                v-for="block in blocks"
                :key="block.id"
                :block="block"
                :importContent="importContent"
                :remoteContent="remoteContent"
                @remove-block="removeBlock"
                @isRemote="isRemoteAction"
                @updateParentList="updateParentList"
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
            draggableGroup: { name: 'blocks' }
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
        if (this.importContent) {
            this.draggableGroup = { name: 'blocks', pull: 'clone', put: false };
        }
        this.shortTitle = BlockManagerHelper.shortTitle(this.element.title, 30);

        if (!this.remoteContent && this.element.isRemote) {
            this.id = 'remote-' + this.id;
            let view = this;
            let blocks = [];
            let blockList = [];
            this.blocks.forEach(element => {
                if (element.isRemote) {
                    blocks.push('remote-' + element.id);
                    view.$emit('isRemote');
                } else {
                    blocks.push(element.id);
                }
            });
            blockList[this.id] = blocks;
            this.$emit('blockListUpdate', blockList);
            this.$emit('updateParentList');
        }
    },
    watch: {
        blocks: function() {
            let list = [];
            this.blocks.forEach(element => {
                list.push(element.id);
            });
            this.blockList[this.id] = list;
            this.$emit('blockListUpdate', this.blockList);
        }
    },
    methods: {
        updateParentList() {
            let list = [];
            let view = this;
            this.blocks.forEach(element => {
                if (element.isRemote) {
                    list.push('remote-' + element.id);
                    view.$emit('isRemote');
                } else {
                    list.push(element.id);
                }
            });
            this.blockList[this.id] = list;
            this.$emit('blockListUpdate', this.blockList);
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
            if (this.importContent) {
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

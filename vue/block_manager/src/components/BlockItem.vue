<template>
    <li
        class="block-item"
        :class="{
            element_hidden: !block.isPublished,
            'block-item-import': importContent,
            'block-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div class="block-description" v-bind:class="{ element_hidden: !block.isPublished }">
            <span :class="['block-icon cw-block-icon-' + block.type]"></span>
            {{ block.readable_name }}
        </div>
        <ActionMenuItem :buttons="['remove']" @remove="removeBlock(block)" />
        <ul class="block-preview" :class="{ 'block-preview-import': importContent }">
            <li class="block-content-preview" v-html="preview"></li>
        </ul>
    </li>
</template>

<script>
import ActionMenuItem from './ActionMenuItem.vue';
import BlockManagerDialogs from './../assets/BlockManagerDialogs';
export default {
    name: 'BlockItem',
    data() {
        return {
            id: this.block.id,
            preview: ''
        };
    },
    components: {
        ActionMenuItem
    },
    props: {
        block: Object,
        importContent: Boolean,
        remoteContent: Boolean
    },
    created() {
        this.preview = this.block.preview;
        if (this.importContent && !this.remoteContent) {
            this.id = 'import-' + this.id;
        }
        if (this.importContent && this.remoteContent) {
            this.id = 'remote-' + this.id;
        }
    },
    methods: {
        removeBlock(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useRemoveDialog(element, false, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                    $('li[data-id=' + view.block.id + ']').remove();
                },
                fail => {
                    console.log(fail);
                }
            );
        }
    }
};
</script>

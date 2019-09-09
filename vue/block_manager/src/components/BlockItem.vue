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
        <div
            class="block-description block-handle"
            :class="{ element_hidden: !block.isPublished, unfolded: unfolded }"
            @click="toggleContent"
        >
            <span :class="['block-icon cw-block-icon-' + block.type]"></span>
            {{ block.readable_name }}
        </div>
        <ActionMenuItem
            v-if="!this.importContent && !this.remoteContent"
            :buttons="['remove']"
            :element="this.block"
            @remove="removeElement"
        />
        <ul v-if="unfolded" class="block-preview" :class="{ 'block-preview-import': importContent }">
            <li class="block-content-preview" v-html="preview"></li>
        </ul>
    </li>
</template>

<script>
import ActionMenuItem from './ActionMenuItem.vue';
export default {
    name: 'BlockItem',
    data() {
        return {
            id: this.block.id,
            preview: '',
            unfolded: false
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
        removeElement() {
            this.$emit('remove-block', this.block);
        },
        toggleContent() {
            this.unfolded = !this.unfolded;
        }
    }
};
</script>

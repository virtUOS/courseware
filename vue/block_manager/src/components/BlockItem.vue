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
        <p class="block-description" v-bind:class="{ element_hidden: !block.isPublished }">
            <span :class="['block-icon cw-block-icon-' + block.type]"></span>
            {{ block.readable_name }}
        </p>
        <ul class="block-preview" :class="{ 'block-preview-import': importContent }">
            <li class="block-content-preview" v-html="preview"></li>
        </ul>
    </li>
</template>

<script>
export default {
    name: 'BlockItem',
    data() {
        return {
            id: this.block.id,
            preview: ''
        };
    },
    components: {},
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
    }
};
</script>

<style scoped></style>

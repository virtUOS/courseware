<template>
    <li
        class="section-item"
        :class="{
            element_hidden: !section.isPublished,
            'section-item-import': importContent,
            'section-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <p class="section-description" v-bind:class="{ element_hidden: !section.isPublished }">
            {{ section.title }}
            <span class="header-info-wrapper">Abschnitt</span>
        </p>
        <ul class="block-list" :class="{ 'block-list-import': importContent }">
            <BlockItem
                v-for="block in section.children"
                :key="block.id"
                :block="block"
                :importContent="importContent"
                :remoteContent="remoteContent"
            />
        </ul>
    </li>
</template>

<script>
import BlockItem from './BlockItem.vue';
export default {
    name: 'SectionItem',
    data() {
        return {
            id: this.section.id
        };
    },
    components: {
        BlockItem
    },
    props: {
        section: Object,
        importContent: Boolean,
        remoteContent: Boolean
    },
    created() {
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

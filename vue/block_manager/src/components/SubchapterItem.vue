<template>
    <li
        class="subchapter-item"
        :class="{
            element_hidden: !subchapter.isPublished,
            'subchapter-item-import': importContent,
            'subchapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <p class="subchapter-description" v-bind:class="{ element_hidden: !subchapter.isPublished }">
            {{ subchapter.title }}
            <span class="header-info-wrapper">
                Unterkapitel
                <span v-if="subchapter.publication_date"> | veröffentlichen: {{ subchapter.publication_date }}</span>
                <span v-if="subchapter.withdraw_date"> | widerrufen: {{ subchapter.withdraw_date }}</span>
            </span>
        </p>
        <ul class="section-list" :class="{ 'section-list-import': importContent }">
            <SectionItem
                v-for="section in subchapter.children"
                :key="section.id"
                :section="section"
                :importContent="importContent"
                :remoteContent="remoteContent"
            />
        </ul>
    </li>
</template>

<script>
import SectionItem from './SectionItem.vue';
export default {
    name: 'SubchapterItem',
    data() {
        return {
            id: this.subchapter.id
        };
    },
    components: {
        SectionItem
    },
    props: {
        subchapter: Object,
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

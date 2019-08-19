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
        <div class="subchapter-description" v-bind:class="{ element_hidden: !subchapter.isPublished }">
            <p class="subchapter-title" :title="subchapter.title">{{ subchapter.shortTitle }}</p>
            <p class="header-info-wrapper">
                Unterkapitel
                <span v-if="subchapter.publication_date"> | veröffentlichen: {{ subchapter.publication_date }}</span>
                <span v-if="subchapter.withdraw_date"> | widerrufen: {{ subchapter.withdraw_date }}</span>
            </p>
        </div>
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
import BlockManagerHelper from './../assets/BlockManagerHelper';
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
        this.subchapter.shortTitle = BlockManagerHelper.shortTitle(this.subchapter.title, 30);
    }
};
</script>

<style scoped></style>

<template>
    <li
        class="chapter-item"
        :class="{
            element_hidden: !chapter.isPublished,
            'chapter-item-import': importContent,
            'chapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <p class="chapter-description" v-bind:class="{ element_hidden: !chapter.isPublished }">
            {{ chapter.title }}
            <span class="header-info-wrapper">
                <span>
                    Kapitel
                </span>
                <span v-if="chapter.publication_date">| veröffentlichen: {{ chapter.publication_date }}</span>
                <span v-if="chapter.withdraw_date"> | widerrufen: {{ chapter.withdraw_date }}</span>
            </span>
        </p>
        <ul class="subchapter-list" :class="{ 'subchapter-list-import': importContent }">
            <SubchapterItem
                v-for="subchapter in chapter.children"
                :key="subchapter.id"
                :subchapter="subchapter"
                :importContent="importContent"
                :remoteContent="remoteContent"
            />
        </ul>
    </li>
</template>

<script>
import SubchapterItem from './SubchapterItem.vue';
export default {
    name: 'ChapterItem',
    data() {
        return {
            id: this.chapter.id
        };
    },
    components: {
        SubchapterItem
    },
    props: {
        chapter: Object,
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

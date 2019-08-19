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
        <div class="chapter-description" v-bind:class="{ element_hidden: !chapter.isPublished }">
            <p class="chapter-title" :title="chapter.title">
                {{ this.chapter.shortTitle }}
            </p>
            <p class="header-info-wrapper">
                <span>
                    Kapitel
                </span>
                <span v-if="chapter.publication_date">| veröffentlichen: {{ chapter.publication_date }}</span>
                <span v-if="chapter.withdraw_date"> | widerrufen: {{ chapter.withdraw_date }}</span>
            </p>
        </div>
        <div class="element-toolbar">
            <button class="edit" @click="editChapter(chapter)"></button>
            <button class="trash" @click="removeChapter(chapter)"></button>
        </div>
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
import BlockManagerHelper from './../assets/BlockManagerHelper';
import BlockManagerDialogs from './../assets/BlockManagerDialogs';
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
        this.chapter.shortTitle = BlockManagerHelper.shortTitle(this.chapter.title, 30);
    },
    methods: {
        editChapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useEditDialog(element, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    view.chapter.title = success.title;
                    view.chapter.shortTitle = BlockManagerHelper.shortTitle(view.chapter.title, 30);
                },
                fail => {
                    console.log(fail);
                }
            );
        },
        removeChapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useRemoveDialog(element, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                    $('li[data-id=' + view.chapter.id + ']').remove();
                },
                fail => {
                    console.log(fail);
                }
            );
        }
    }
};
</script>

<style scoped></style>

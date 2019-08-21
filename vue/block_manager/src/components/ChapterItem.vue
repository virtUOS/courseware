<template>
    <li
        class="chapter-item"
        :class="{
            'chapter-item-import': importContent,
            'chapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div class="chapter-description">
            <p class="chapter-title" :title="title">
                {{ shortTitle }}
            </p>
            <p class="header-info-wrapper">
                <span
                    :class="{
                        'unpublished-info': !isPublished && (publication_date || withdraw_date),
                        'published-info': isPublished && (publication_date || withdraw_date)
                    }"
                >
                    Kapitel
                </span>
                <span
                    v-if="publication_date"
                    :class="{ 'unpublished-info': !isPublished, 'published-info': isPublished }"
                    >| veröffentlichen: {{ publication_date_readable }}</span
                >
                <span v-if="withdraw_date" :class="{ 'unpublished-info': !isPublished, 'published-info': isPublished }">
                    | widerrufen: {{ withdraw_date_readable }}</span
                >
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
            id: this.chapter.id,
            publication_date: this.chapter.publication_date,
            publication_date_readable: BlockManagerHelper.getReadableDate(this.chapter.publication_date),
            withdraw_date: this.chapter.withdraw_date,
            withdraw_date_readable: BlockManagerHelper.getReadableDate(this.chapter.withdraw_date),
            isPublished: this.chapter.isPublished,
            title: this.chapter.title,
            shortTitle: this.chapter.shortTitle
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
        this.shortTitle = BlockManagerHelper.shortTitle(this.title, 30);
    },
    methods: {
        editChapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useEditDialog(element, true, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    view.title = success.title;
                    view.chapter.title = success.title;
                    if (success.publication_date) {
                        view.publication_date = success.publication_date * 1000;
                    } else {
                        view.publication_date = null;
                    }
                    if (success.withdraw_date) {
                        view.withdraw_date = success.withdraw_date * 1000;
                    } else {
                        view.withdraw_date = null;
                    }
                    view.shortTitle = BlockManagerHelper.shortTitle(view.title, 30);
                },
                fail => {
                    console.log(fail);
                }
            );
        },
        removeChapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useRemoveDialog(element, true, resolve, reject);
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
        },

        updateIsPublished() {
            let now = new Date();
            let publication_date = new Date(this.publication_date);
            let withdraw_date = new Date(this.withdraw_date);

            if (
                (publication_date < now || publication_date == null) &&
                (withdraw_date > now || withdraw_date == null)
            ) {
                this.isPublished = true;
            } else {
                this.isPublished = false;
            }
        }
    },
    watch: {
        publication_date: function() {
            this.chapter.publication_date = this.publication_date;
            this.publication_date_readable = BlockManagerHelper.getReadableDate(this.publication_date);
            this.updateIsPublished();
        },
        withdraw_date: function() {
            this.chapter.withdraw_date = this.withdraw_date;
            this.withdraw_date_readable = BlockManagerHelper.getReadableDate(this.withdraw_date);
            this.updateIsPublished();
        }
    }
};
</script>

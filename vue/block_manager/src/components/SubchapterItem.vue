<template>
    <li
        class="subchapter-item"
        :class="{
            'subchapter-item-import': importContent,
            'subchapter-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div class="subchapter-description">
            <p class="subchapter-title" :title="title">{{ shortTitle }}</p>
            <p class="header-info-wrapper">
                <span
                    :class="{
                        'unpublished-info': !isPublished && (publication_date || withdraw_date),
                        'published-info': isPublished && (publication_date || withdraw_date)
                    }"
                >
                    Unterkapitel
                </span>
                <span
                    v-if="publication_date"
                    :class="{ 'unpublished-info': !isPublished, 'published-info': isPublished }"
                >
                    | sichtbar ab: {{ publication_date_readable }}</span
                >
                <span v-if="withdraw_date" :class="{ 'unpublished-info': !isPublished, 'published-info': isPublished }">
                    | unsichtbar ab: {{ withdraw_date_readable }}</span
                >
            </p>
        </div>
        <ActionMenuItem
            :buttons="['edit', 'remove', 'groups', 'users']"
            @edit="editSubchapter(subchapter)"
            @remove="removeSubchapter(subchapter)"
            @set-users="setUserApproval(subchapter)"
            @set-groups="setGroupApproval(subchapter)"
        />

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
import ActionMenuItem from './ActionMenuItem.vue';
import BlockManagerHelper from './../assets/BlockManagerHelper';
import BlockManagerDialogs from './../assets/BlockManagerDialogs';
export default {
    name: 'SubchapterItem',
    data() {
        return {
            id: this.subchapter.id,
            publication_date: this.subchapter.publication_date,
            publication_date_readable: BlockManagerHelper.getReadableDate(this.subchapter.publication_date),
            withdraw_date: this.subchapter.withdraw_date,
            withdraw_date_readable: BlockManagerHelper.getReadableDate(this.subchapter.withdraw_date),
            isPublished: this.subchapter.isPublished,
            title: this.subchapter.title,
            shortTitle: this.subchapter.shortTitle
        };
    },
    components: {
        SectionItem,
        ActionMenuItem
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
        this.shortTitle = BlockManagerHelper.shortTitle(this.subchapter.title, 30);
    },
    methods: {
        editSubchapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useEditDialog(element, true, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    view.title = success.title;
                    view.subchapter.title = success.title;
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
        removeSubchapter(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useRemoveDialog(element, true, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                    $('li[data-id=' + view.subchapter.id + ']').remove();
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
        },
        setUserApproval(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useUserApprovalDialog(element, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                },
                fail => {
                    console.log(fail);
                }
            );
        },
        setGroupApproval(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useGroupApprovalDialog(element, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                },
                fail => {
                    console.log(fail);
                }
            );
        }
    },
    watch: {
        publication_date: function() {
            this.subchapter.publication_date = this.publication_date;
            this.publication_date_readable = BlockManagerHelper.getReadableDate(this.publication_date);
            this.updateIsPublished();
        },
        withdraw_date: function() {
            this.subchapter.withdraw_date = this.withdraw_date;
            this.withdraw_date_readable = BlockManagerHelper.getReadableDate(this.withdraw_date);
            this.updateIsPublished();
        }
    }
};
</script>

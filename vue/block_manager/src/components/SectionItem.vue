<template>
    <li
        class="section-item"
        :class="{
            'section-item-import': importContent,
            'section-item-remote': remoteContent
        }"
        :data-id="id"
    >
        <div class="section-description">
            <p class="section-title" :title="title">{{ shortTitle }}</p>
            <p class="header-info-wrapper">Abschnitt</p>
        </div>
        <ActionMenuItem :buttons="['edit', 'remove']" @edit="editSection(section)" @remove="removeSection(section)" />
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
import ActionMenuItem from './ActionMenuItem.vue';
import BlockManagerHelper from './../assets/BlockManagerHelper';
import BlockManagerDialogs from './../assets/BlockManagerDialogs';
export default {
    name: 'SectionItem',
    data() {
        return {
            id: this.section.id,
            title: this.section.title,
            shortTitle: this.section.shortTitle
        };
    },
    components: {
        BlockItem,
        ActionMenuItem
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
        this.shortTitle = BlockManagerHelper.shortTitle(this.section.title, 30);
    },
    methods: {
        editSection(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useEditDialog(element, false, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    view.title = success.title;
                    view.section.title = success.title;
                    view.shortTitle = BlockManagerHelper.shortTitle(view.title, 30);
                },
                fail => {
                    console.log(fail);
                }
            );
        },
        removeSection(element) {
            let view = this;
            return new Promise(function(resolve, reject) {
                BlockManagerDialogs.useRemoveDialog(element, true, resolve, reject);
            }).then(
                success => {
                    success = JSON.parse(success);
                    console.log(success);
                    $('li[data-id=' + view.section.id + ']').remove();
                },
                fail => {
                    console.log(fail);
                }
            );
        }
    }
};
</script>

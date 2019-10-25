<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header" ref="modalHeader">
                        <slot name="header">
                            {{ $t('message.setGroupsPermissions') }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <ul class="groups-permissions-list">
                            <li v-for="group in groups" :key="group.id">
                                <label>
                                    <input type="checkbox" :value="group.id" v-model="checkedGroups" /> {{ group.name }}
                                </label>
                            </li>
                        </ul>
                    </section>
                    <footer class="modal-footer">
                        <slot name="footer">
                            <button type="button" class="button accept" @click="set">
                                {{ $t('message.ButtonLabelSave') }}
                            </button>

                            <button type="button" class="button cancel" @click="close">
                                {{ $t('message.ButtonLabelClose') }}
                            </button>
                        </slot>
                    </footer>
                </div>
            </div>
        </transition>
    </div>
</template>
<script>
import axios from 'axios';
export default {
    name: 'GroupsPermissionsDialog',
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            currentElement: this.element,
            groups: this.$store.state.courseGroups,
            checkedGroups: []
        };
    },
    methods: {
        close() {
            if (!this.deleting) {
                this.$emit('close');
            }
        },
        set() {
            let bid = this.element.id;
            let list = {};
            list.groups = this.checkedGroups;
            axios
                .post('set_element_approval_list', { bid: bid, list: JSON.stringify(list) })
                .then(response => {
                    console.log(response);
                    this.$emit('close');
                })
                .catch(error => {
                    console.log(error);
                    this.$emit('close');
                });
        },
        getApprovalList() {
            let bid = this.element.id;
            let view = this;
            axios
                .post('get_element_approval_list', { bid: bid, type: 'groups' })
                .then(response => {
                    if (response.data != null) {
                        view.checkedGroups = response.data;
                    }
                })
                .catch(error => {
                    console.log(error);
                });
        }
    },
    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            this.groups = this.$store.state.courseGroups;
            if (this.visible) {
                this.getApprovalList();
            } else {
                this.checkedGroups = [];
            }
        }
    }
};
</script>

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
                            <table class="students-permissions-list">
                                <thead>
                                    <th>
                                        {{ $t('message.readPerms') }}
                                    </th>
                                    <th>
                                        {{ $t('message.readWritePerms') }}
                                    </th>
                                    <th></th>
                                </thead>
                                <tbody>
                                    <tr v-for="group in groups" :key="group.id">
                                        <td class="perm">
                                            <input type="checkbox"
                                                :id="group.id + `_read`"
                                                true-value="read"
                                                false-value="none"
                                                v-model="perms[group.id]"
                                            />
                                        </td>

                                        <td class="perm">
                                            <input type="checkbox"
                                                true-value="write"
                                                false-value="none"
                                                v-model="perms[group.id]"
                                            />
                                        </td>

                                        <td>
                                            <label :for="group.id + `_read`">
                                                {{ group.name }}
                                            </label>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
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
            perms: {}
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
            list.groups = this.perms;
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
                    view.groups = view.$store.state.courseGroups;

                    if (response.data !== null && response.data.groups !== undefined) {
                        view.perms = response.data.groups;
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
            this.groups = [];
            if (this.visible) {
                this.getApprovalList();
            } else {
                this.perms = {};
            }
        }
    }
};
</script>

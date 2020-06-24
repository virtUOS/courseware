<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ $t('message.setStudentsPermissions') }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <table class="students-permissions-list">
                            <thead>
                                <th>
                                    Lesen
                                </th>
                                <th>
                                    Schreiben
                                </th>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.user_id">
                                    <td class="perm">
                                        <input type="checkbox"
                                            :id="user.user_id + `_read`"
                                            :value="user.user_id"
                                            v-model="checkedUsersRead"
                                        />
                                    </td>
                                    <td class="perm">
                                        <input type="checkbox" :value="user.user_id" v-model="checkedUsersWrite" />
                                    </td>

                                    <td>
                                        <label :for="user.user_id + `_read`">
                                            {{ user.firstname }}
                                            {{ user.lastname }}
                                            <i>{{ user.username }}</i>
                                        </label>
                                    </td>

                                </tr>
                            </tbody>
                        </table>
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
    name: 'StudentsPermissionsDialog',
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            currentElement: this.element,
            users: this.$store.state.courseUsers,
            checkedUsersRead: [],
            checkedUsersWrite: []
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
            list.users = {
                read: this.checkedUsersRead,
                write: this.checkedUsersWrite
            };

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
                .post('get_element_approval_list', { bid: bid, type: 'users' })
                .then(response => {
                    if (response.data != null) {
                        view.checkedUsersRead  = response.data.read  ? response.data.read  : [];
                        view.checkedUsersWrite = response.data.write ? response.data.write : [];
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
            this.users = this.$store.state.courseUsers;
            if (this.visible) {
                this.getApprovalList();
            } else {
                this.checkedUsersRead = [];
                this.checkedUsersWrite = [];
            }
        },

        checkedUsersWrite: function(data) {
            for (var i = 0; i < data.length; i++) {
                if (!this.checkedUsersRead.includes(data[i])) {
                    this.checkedUsersRead.push(data[i]);
                }
            }
        },

        checkedUsersRead: function(data) {
            for (var i = 0; i < this.checkedUsersWrite.length; i++) {
                if (!this.checkedUsersRead.includes(this.checkedUsersWrite[i])) {
                    this.checkedUsersWrite.splice(i, 1);
                }
            }
        }
    }
};
</script>

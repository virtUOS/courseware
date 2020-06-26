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
                        <label>
                            <input type="checkbox" class="default"
                                value="read"
                                v-model="settings.defaultRead"
                            />
                            {{ $t('message.newUserReadPerms') }}
                        </label>

                        <table class="default students-permissions-list">
                            <caption>
                                {{ this.settings.caption_autor }}
                            </caption>
                            <colgroup>
                                <col width="15%">
                                <col width="15%">
                                <col width="70%">
                            </colgroup>
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
                                <tr v-for="user in autor_members" :key="user.user_id">
                                    <td class="perm">
                                        <input type="checkbox"
                                            :id="user.user_id + `_read`"
                                            true-value="read"
                                            false-value="none"
                                            v-model="user_perms[user.user_id]"
                                        />
                                    </td>
                                    <td class="perm">
                                        <input type="checkbox"
                                            true-value="write"
                                            false-value="none"
                                            v-model="user_perms[user.user_id]"
                                        />
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

                        <table class="default students-permissions-list">
                            <caption>
                                {{ this.settings.caption_user }}
                            </caption>
                            <colgroup>
                                <col width="15%">
                                <col width="15%">
                                <col width="70%">
                            </colgroup>
                            <thead>
                                <th>
                                    {{ $t('message.readPerms') }}
                                </th>
                                <th></th>
                                <th></th>
                            </thead>
                            <tbody>
                                <tr v-for="user in user_members" :key="user.user_id">
                                    <td class="perm">
                                        <input type="checkbox"
                                            :id="user.user_id + `_read`"
                                            true-value="read"
                                            false-value="none"
                                            v-model="user_perms[user.user_id]"
                                        />
                                    </td>
                                    <td class="perm">

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
            user_perms: {},
            settings: {
                defaultRead: true,
                caption_autor: 'Studierende',
                caption_user:  'Leser/innen'
            }
        };
    },

    computed: {
        autor_members() {
            if (Object.keys(this.users).length === 0 && this.users.constructor === Object) {
                return [];
            }

            let members = this.users.filter(function(user) {
                return user.perm == 'autor';
            });

            return members;
        },

        user_members() {
            if (Object.keys(this.users).length === 0 && this.users.constructor === Object) {
                return [];
            }

            let members = this.users.filter(function(user) {
                return user.perm == 'user';
            });

            return members;
        }
    },

    methods: {
        close() {
            if (!this.deleting) {
                this.$emit('close');
            }
        },
        set() {
            let bid  = this.element.id;
            let list = {
                users:    this.user_perms,
                settings: this.settings
            }

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
                    view.users = view.$store.state.courseUsers;

                    if (response.data != null) {
                        if (response.data.users !== undefined) {
                            view.user_perms = response.data.users;
                        }

                        if (response.data.settings !== undefined) {
                            view.settings   = response.data.settings;
                        }
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
            this.users = [];

            if (this.visible) {
                this.getApprovalList();
            } else {
                this.user_perms = {};
                this.settings = {
                    defaultRead: true
                }
            }
        },

        autor_members: function() {
            let new_perms = { ...this.user_perms };

            // per default, give new users read permissions
            for (let key in this.autor_members) {
                if (this.user_perms[this.autor_members[key].user_id] === undefined) {
                    if (this.settings.defaultRead) {
                        new_perms[this.autor_members[key].user_id] = 'read';
                    } else {
                        new_perms[this.autor_members[key].user_id] = 'none';
                    }
                }
            }

            this.user_perms = new_perms;
        },

        user_members: function() {
            let new_perms = { ...this.user_perms };

            // per default, give new users read permissions
            for (let key in this.user_members) {
                if (this.user_perms[this.user_members[key].user_id] === undefined) {
                    if (this.settings.defaultRead) {
                        new_perms[this.user_members[key].user_id] = 'read';
                    } else {
                        new_perms[this.user_members[key].user_id] = 'none';
                    }
                }
            }

            this.user_perms = new_perms;
        }
    }
};
</script>

<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal cw-permission-modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ $t('message.setStudentsPermissions') }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <label class="students-permissions-new-users-label">
                            <input type="checkbox" class="default"
                                value="read"
                                v-model="settings.defaultRead"
                            />
                            {{ $t('message.newUserReadPerms') }}
                        </label>

                        <table class="default students-permissions-list" v-if="autor_members.length">
                            <caption>
                                {{ this.settings.caption_autor }}
                            </caption>
                            <colgroup>
                                <col width="20%">
                                <col width="35%">
                                <col width="45%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox"
                                            v-model="toggled.autor.read"
                                            @change="toggleAll('autor', 'read')"
                                        />
                                        {{ $t('message.readPerms') }}
                                    </th>
                                    <th>
                                        <input type="checkbox"
                                            v-model="toggled.autor.write"
                                            @change="toggleAll('autor', 'write')"
                                        />
                                        {{ $t('message.readWritePerms') }}
                                    </th>
                                    <th>{{ $t('message.name') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in autor_members" :key="user.user_id">
                                    <td class="perm">
                                        <input type="checkbox"
                                            :id="user.user_id + `_read`"
                                            true-value="read"
                                            false-value="none"
                                            v-model="user_perms[user.user_id]"
                                            @change="updateToggleStatus"
                                        />
                                    </td>
                                    <td class="perm">
                                        <input type="checkbox"
                                            true-value="write"
                                            false-value="none"
                                            v-model="user_perms[user.user_id]"
                                            @change="updateToggleStatus"
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

                        <table class="default students-permissions-list" v-if="user_members.length">
                            <caption>
                                {{ this.settings.caption_user }}
                            </caption>
                            <colgroup>
                                <col width="15%">
                                <col width="30%">
                                <col width="55%">
                            </colgroup>
                            <thead>
                                <th>
                                    <input type="checkbox"
                                        v-model="toggled.user.read"
                                        @change="toggleAll('user', 'read')"
                                    />
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
                                            @change="updateToggleStatus"
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
            },
            toggled: {
                autor: {
                    read: false,
                    write: false
                },
                user: {
                    read: true
                }
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
                .then(() => {
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
        },

        toggleAll(type, perm) {
            let current = this.toggled[type][perm];
            let new_perms = { ...this.user_perms };
            let members;

            if (type === 'autor') {
                members = this.autor_members;
                if (perm == 'write') {
                    this.toggled.autor.read = false;
                } else {
                    this.toggled.autor.write = false;
                }
            } else {
                members = this.user_members
            }

            for (let key in members) {
                new_perms[members[key].user_id] = current ? perm : 'none';
            }

            this.user_perms = new_perms;
        },

        updateToggleStatus() {
            this.toggled.autor.read = true;
            this.toggled.autor.write = true;
            this.toggled.user.read = true;

            for (let key in this.autor_members) {
                if (this.user_perms[this.autor_members[key].user_id] !== 'read') {
                    this.toggled.autor.read = false;
                }
                if (this.user_perms[this.autor_members[key].user_id] !== 'write') {
                    this.toggled.autor.write = false;
                }
            }


            for (let key in this.user_members) {
                if (this.user_perms[this.user_members[key].user_id] !== 'read') {
                    this.toggled.user.read = false;
                }
            }
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
            this.updateToggleStatus();
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
            this.updateToggleStatus();
        },

        user_perms: function() {
            this.updateToggleStatus();
        }
    }
};
</script>

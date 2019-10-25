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
                        <ul class="students-permissions-list">
                            <li v-for="user in users" :key="user.id">
                                <label>
                                    <input type="checkbox" :value="user.user_id" v-model="checkedUsers" />
                                    {{ user.firstname }}
                                    {{ user.lastname }}
                                    <i>{{ user.username }}</i>
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
            checkedUsers: []
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
            list.users = this.checkedUsers;
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
                        view.checkedUsers = response.data;
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
                this.checkedUsers = [];
            }
        }
    }
};
</script>

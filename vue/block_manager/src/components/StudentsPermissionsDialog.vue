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
                                <input type="checkbox" />
                                {{ user.firstname }} {{ user.lastname }}
                                <i>{{ user.username }}</i>
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
            users: this.$store.state.courseUsers
        };
    },
    methods: {
        close() {
            if (!this.deleting) {
                this.$emit('close');
            }
        },
        set() {
            console.log('set students permissions');
            // let data = {};
            // let view = this;
            // data.id = this.currentElement.id;
            // this.deleting = true;
            // $.ajax({
            //     type: 'DELETE',
            //     url: '../blocks/' + data.id,
            //     contentType: 'application/json',
            //     data: data,
            //     success: function() {
            //         view.$emit('remove');
            //         view.deleting = false;
            //         view.$emit('close');
            //     },
            //     error: function() {
            //         console.log('can not remove node!');
            //         view.deleting = false;
            //         view.$emit('close');
            //     }
            // });
        }
    },
    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            this.users = this.$store.state.courseUsers;
            console.log(this.users);
        }
    }
};
</script>

<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ $t('message.setGroupsPermissions') }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <p v-for="group in groups" :key="group.id">{{ group.name }}</p>
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
    name: 'GroupsPermissionsDialog',
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            currentElement: this.element,
            groups: this.$store.state.courseGroups
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
            this.groups = this.$store.state.courseGroups;
        }
    }
};
</script>

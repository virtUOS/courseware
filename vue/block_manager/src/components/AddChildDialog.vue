<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ $t('message.addDialogTitle') }}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <label for="editDialogElementTitle">{{ $t('message.title') }}:</label>
                        <input
                            type="text"
                            name="editDialogElementTitle"
                            ref="editDialogElementTitle"
                            v-model="title"
                            @keyup.enter="edit"
                            @keyup.esc="close"
                        />
                    </section>
                    <footer class="modal-footer">
                        <slot name="footer">
                            <button type="button" class="button accept" @click="edit">
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
    name: 'AddChildDialog',
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            type: this.element.childType,
            title: '',
            currentElement: this.element
        };
    },
    mounted() {},
    methods: {
        close() {
            this.$emit('close');
            this.title = '';
        },
        edit() {
            let view = this;
            if (this.title == '') {
                this.title = 'new ' + this.type;
            }
            axios
                .post('add_structure', {
                    parent: view.element.id,
                    title: view.title,
                    type: view.type,
                    cid: COURSEWARE.config.cid
                })
                .then(response => {
                    view.$emit('add-child', response.data);
                    view.close();
                })
                .catch(error => {
                    console.log('there was an error: ' + error.response);
                    view.close();
                });
        }
    },
    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            this.$nextTick(() => {
                if (this.$refs.editDialogElementTitle) {
                    this.$refs.editDialogElementTitle.focus();
                }
            });
        }
    }
};
</script>

<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal alert" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{ this.title }} löschen
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <p v-if="this.currentElement.isStrucutalElement">
                            Möchten Sie dieses Element und alle darunter liegenden Elemente wirklich löschen?
                        </p>
                        <p v-if="this.currentElement.isBlock">Möchten Sie dieses Element wirklich löschen?</p>
                    </section>
                    <footer class="modal-footer">
                        <slot name="footer">
                            <button type="button" class="button accept" @click="remove" v-if="!deleting">
                                Ja
                            </button>
                            <button type="button" class="button modal-progress" v-else>
                                <spring-spinner
                                    :animation-duration="3000"
                                    :size="14"
                                    :color="'#28497c'"
                                    class="modal-progress-spinner"
                                />
                            </button>

                            <button
                                type="button"
                                class="button cancel"
                                :class="{ 'button-inactive': deleting }"
                                @click="close"
                            >
                                Nein
                            </button>
                        </slot>
                    </footer>
                </div>
            </div>
        </transition>
    </div>
</template>
<script>
import { SpringSpinner } from 'epic-spinners';

export default {
    name: 'RemoveDialog',
    components: { SpringSpinner },
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            title: '',
            currentElement: this.element,
            deleting: false
        };
    },
    mounted() {
        if (this.currentElement) {
            if (this.currentElement.isBlock) {
                this.title = this.currentElement.readable_name;
            }
            if (this.currentElement.isStrucutalElement) {
                this.title = this.currentElement.title;
            }
        }
    },
    methods: {
        close() {
            if (!this.deleting) {
                this.$emit('close');
            }
        },
        remove() {
            let data = {};
            let view = this;
            data.id = this.currentElement.id;
            this.deleting = true;
            $.ajax({
                type: 'DELETE',
                url: '../blocks/' + data.id,
                contentType: 'application/json',
                data: data,
                success: function() {
                    view.$emit('remove');
                    view.deleting = false;
                    view.$emit('close');
                },
                error: function() {
                    console.log('can not remove node!');
                    view.deleting = false;
                    view.$emit('close');
                }
            });
        }
    },
    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
        }
    }
};
</script>
<style></style>

<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">
                    <header class="modal-header">
                        <slot name="header">
                            {{$t('message.editDialogTitle', [this.title])}}
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <label for="editDialogElementTitle">{{$t('message.title')}}:</label>
                        <input type="text" name="editDialogElementTitle" v-model="title" />
                        <br />
                        <div v-if="isChapter">
                            <label for="publication_date">{{$t('message.visibleFrom')}}:</label>
                            <input
                                type="date"
                                name="publication_date"
                                v-model="publicationDate"
                                :max="maxPublicationDate"
                            />
                            <br />
                            <label for="withdraw_date">{{$t('message.invisibleFrom')}}:</label>
                            <input type="date" name="withdraw_date" v-model="withdrawDate" :min="minWithdrawDate" />
                        </div>
                    </section>
                    <footer class="modal-footer">
                        <slot name="footer">
                            <button type="button" class="button accept" @click="edit">
                                {{$t('message.ButtonLabelSave')}}
                            </button>

                            <button type="button" class="button cancel" @click="close">
                                 {{$t('message.ButtonLabelClose')}}
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
    name: 'EditDialog',
    props: {
        DialogVisible: Boolean,
        element: Object
    },
    data() {
        return {
            visible: this.DialogVisible,
            title: '',
            currentElement: this.element,
            isChapter: false,
            publicationDate: null,
            maxPublicationDate: '',
            withdrawDate: null,
            minWithdrawDate: ''
        };
    },
    mounted() {
        if (this.currentElement) {
            this.title = this.currentElement.title;
            if (this.currentElement.type == 'Chapter' || this.currentElement.type == 'Subchapter') {
                this.isChapter = true;
                if (this.currentElement.publication_date) {
                    this.publicationDate = this.convertTimestampToDate(this.currentElement.publication_date);
                }
                if (this.currentElement.withdraw_date) {
                    this.withdrawDate = this.convertTimestampToDate(this.currentElement.withdraw_date);
                }
            }
        }
    },
    methods: {
        close() {
            this.$emit('close');
        },
        edit() {
            let view = this;
            let data = {};
            data.id = this.element.id;
            data.title = this.title;
            if (this.isChapter) {
                let pattern = /(\d{4})-(\d{2})-(\d{2})/;
                if (this.publicationDate) {
                    data.publication_date =
                        new Date(this.publicationDate.replace(pattern, '$2,$3,$1')).getTime() / 1000;
                    data.publication_date_readable = this.publicationDate.replace(pattern, '$3.$2.$1');
                }
                if (this.withdrawDate) {
                    data.withdraw_date = new Date(this.withdrawDate.replace(pattern, '$2,$3,$1')).getTime() / 1000;
                    data.withdraw_date_readable = this.withdrawDate.replace(pattern, '$3.$2.$1');
                }
                if (data.withdraw_date <= data.publication_date) {
                    return;
                }
                data.isPublished = this.isPublished(this.publicationDate, this.withdrawDate);
            }

            $.ajax({
                type: 'PUT',
                url: '../blocks/' + data.id,
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function() {
                    view.$emit('edit', data);
                    view.$emit('close');
                },
                error: function() {
                    console.log('can not remove node!');
                    view.$emit('close');
                }
            });
        },
        convertTimestampToDate(timestamp) {
            let date = new Date(timestamp);

            return (
                date.getFullYear() +
                '-' +
                ('0' + (date.getMonth() + 1)).slice(-2) +
                '-' +
                ('0' + date.getDate()).slice(-2)
            );
        },
        isPublished(publication_date, withdraw_date) {
            let now = new Date();
            if (
                (new Date(publication_date) < now || publication_date == null) &&
                (new Date(withdraw_date) > now || withdraw_date == null)
            ) {
                return true;
            } else {
                return false;
            }
        }
    },
    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
        },
        publicationDate: function() {
            this.minWithdrawDate = this.convertTimestampToDate(this.publicationDate);
        },
        withdrawDate: function() {
            this.maxPublicationDate = this.convertTimestampToDate(this.withdrawDate);
        }
    }
};
</script>

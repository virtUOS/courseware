<template>
    <div v-if="this.visible" class="cw-dialog">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal cw-permission-modal" role="dialog">
                    <header class="modal-header" ref="modalHeader">
                        <slot name="header">
                            Lese- und Schreibrechte für Gruppen festlegen
                            <span class="modal-close-button" @click="close"></span>
                        </slot>
                    </header>
                    <section class="modal-body">
                        <table class="default groups-permissions-list" v-if="groups.length">
                            <colgroup>
                                <col width="20%">
                                <col width="35%">
                                <col width="45%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox"
                                            v-model="toggled.read"
                                            @change="toggleAll('read')"
                                        />
                                        Lesen
                                    </th>
                                    <th>
                                        <input type="checkbox"
                                            v-model="toggled.write"
                                            @change="toggleAll('write')"
                                        />
                                        Lesen und Schreiben
                                    </th>
                                    <th>Gruppenname</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="group in groups" :key="group.id">
                                    <td class="perm">
                                        <input type="checkbox"
                                            :id="group.id + `_read`"
                                            true-value="read"
                                            false-value="none"
                                            v-model="perms[group.id]"
                                            @change="updateToggleStatus"
                                        />
                                    </td>

                                    <td class="perm">
                                        <input type="checkbox"
                                            true-value="write"
                                            false-value="none"
                                            v-model="perms[group.id]"
                                            @change="updateToggleStatus"
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

                        <span v-else>
                            In dieser Veranstaltung gibt es bisher keine Grupppen!
                        </span>
                    </section>
                    <footer class="modal-footer">
                        <slot name="footer">
                            <button type="button" class="button accept" @click="set">
                                speichern
                            </button>

                            <button type="button" class="button cancel" @click="close">
                                abbrechen
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
        element: Object,
        groups: Array
    },

    data() {
        return {
            visible: this.DialogVisible,
            currentElement: this.element,
            // groups: this.$store.state.courseGroups,
            perms: {},
            toggled: {
                read: false,
                write: false
            }
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
                    //view.groups = view.$store.state.courseGroups;

                    if (response.data !== null && response.data.groups !== undefined) {
                        view.perms = response.data.groups;
                    } else {
                        for (let key in view.groups) {
                            view.perms[this.groups[key].id] = "none";
                        }
                    }
                })
                .catch(error => {
                    console.log(error);
                });
        },

        toggleAll(perm) {
            let current = this.toggled[perm];
            let new_perms = { ...this.perms };
            let groups = this.groups;

            if (perm == 'write') {
                this.toggled.read = false;
            } else {
                this.toggled.write = false;
            }

            for (let key in groups) {
                new_perms[groups[key].id] = current ? perm : 'none';
            }

            this.perms = new_perms;
        },

        updateToggleStatus() {
            this.toggled.read = true;
            this.toggled.write = true;

            for (let key in this.groups) {
                if (this.perms[this.groups[key].id] !== 'read') {
                    this.toggled.read = false;
                }

                if (this.perms[this.groups[key].id] !== 'write') {
                    this.toggled.write = false;
                }

            }
        }
    },

    watch: {
        DialogVisible: function() {
            this.visible = this.DialogVisible;
            if (this.visible) {
                this.getApprovalList();
            } else {
                this.perms = {};
            }
        }
    }
};
</script>

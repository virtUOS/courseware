<template>
    <div class="strucutal-element-menu-wrapper">
        <nav class="action-menu bymousedown strucutal-element-menu" ref="actionMenu">
            <a class="action-menu-icon" title="Aktionen" href="#">
                <div></div>
                <div></div>
                <div></div>
            </a>
            <div class="action-menu-content">
                <div class="action-menu-title">
                    Aktionen
                </div>
                <ul class="action-menu-list">
                    <li v-if="this.users_button" class="action-menu-item">
                        <a
                            href="#"
                            class="set-users"
                            @click="
                                setStudentsPermissionsDialogVisible = true;
                                $refs.actionMenu.classList.remove('active');
                            "
                        >
                            Lese- und Schreibrechte für Studierende festlegen
                        </a>
                    </li>
                    <li v-if="this.groups_button" class="action-menu-item">
                        <a
                            href="#"
                            class="set-groups"
                            @click="
                                setGroupsPermissionsDialogVisible = true;
                                $refs.actionMenu.classList.remove('active');
                            "
                        >
                            Lese- und Schreibrechte für Gruppen festlegen
                        </a>
                    </li>
                    <li v-if="this.edit_button" class="action-menu-item">
                        <a
                            href="#"
                            class="edit-element"
                            @click="
                                editDialogVisible = true;
                                $refs.actionMenu.classList.remove('active');
                            "
                        >
                            Element bearbeiten
                        </a>
                    </li>
                    <li v-if="this.remove_button" class="action-menu-item">
                        <a
                            href="#"
                            class="remove-element"
                            @click="
                                removeDialogVisible = true;
                                $refs.actionMenu.classList.remove('active');
                            "
                        >
                            Element löschen
                        </a>
                    </li>
                    <li v-if="this.add_child_button" class="action-menu-item">
                        <a
                            href="#"
                            class="add-child-element"
                            @click="
                                addChildDialogVisible = true;
                                $refs.actionMenu.classList.remove('active');
                            "
                        >
                            Unterelement hinzufügen
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <StudentsPermissionsDialog
            v-if="this.users_button"
            :DialogVisible="this.setStudentsPermissionsDialogVisible"
            :element="this.element"
            :users="courseUsers"
            @close="setStudentsPermissionsDialogVisible = false"
            @set="setStudentsPermissionsAction"
        />
        <GroupsPermissionsDialog
            v-if="this.groups_button"
            :DialogVisible="this.setGroupsPermissionsDialogVisible"
            :element="this.element"
            :groups="courseGroups"
            @close="setGroupsPermissionsDialogVisible = false"
            @set="setGroupsPermissionsAction"
        />
        <EditDialog
            v-if="this.edit_button"
            :DialogVisible="this.editDialogVisible"
            :element="this.element"
            @close="editDialogVisible = false"
            @edit="editDialogAction"
        />
        <RemoveDialog
            v-if="this.remove_button"
            :DialogVisible="this.removeDialogVisible"
            :element="this.element"
            @close="removeDialogVisible = false"
            @remove="$emit('remove')"
        />
        <AddChildDialog
            v-if="this.add_child_button"
            :DialogVisible="this.addChildDialogVisible"
            :element="this.element"
            @close="addChildDialogVisible = false"
            @add-child="addChildDialogAction"
        />
    </div>
</template>
<script>
import RemoveDialog from './RemoveDialog.vue';
import EditDialog from './EditDialog.vue';
import AddChildDialog from './AddChildDialog.vue';
import StudentsPermissionsDialog from './StudentsPermissionsDialog.vue';
import GroupsPermissionsDialog from './GroupsPermissionsDialog.vue';
export default {
    props: {
        buttons: Array,
        element: Object,
        courseUsers: Array,
        courseGroups: Array
    },
    components: { RemoveDialog, EditDialog, AddChildDialog, StudentsPermissionsDialog, GroupsPermissionsDialog },
    data() {
        return {
            edit_button: false,
            remove_button: false,
            groups_button: false,
            users_button: false,
            add_child_button: false,
            removeDialogVisible: false,
            editDialogVisible: false,
            addChildDialogVisible: false,
            setStudentsPermissionsDialogVisible: false,
            setGroupsPermissionsDialogVisible: false
        };
    },
    created() {
        let view = this;
        this.buttons.forEach(element => {
            switch (element) {
                case 'edit':
                    view.edit_button = true;
                    break;
                case 'remove':
                    view.remove_button = true;
                    break;
                case 'add-child':
                    view.add_child_button = true;
                    break;
                case 'groups':
                    view.groups_button = true;
                    break;
                case 'users':
                    view.users_button = true;
                    break;
            }
        });
    },
    methods: {
        editDialogAction(data) {
            this.$emit('edit', data);
        },
        addChildDialogAction(data) {
            this.$emit('add-child', data);
        },
        setStudentsPermissionsAction(data) {
            this.$emit('set-students-permissions', data);
        },
        setGroupsPermissionsAction(data) {
            this.$emit('set-groups-permissions', data);
        }
    }
};
</script>

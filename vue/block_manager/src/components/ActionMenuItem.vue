<template>
    <div class="strucutal-element-menu-wrapper">
        <nav class="action-menu bymousedown strucutal-element-menu">
            <a class="action-menu-icon" title="Aktionen" aria-expanded="true" aria-label="Aktionsmenü" href="#">
                <div></div>
                <div></div>
                <div></div>
            </a>
            <div class="action-menu-content">
                <div class="action-menu-title">
                    {{$t('message.actions')}}
                </div>
                <ul class="action-menu-list">
                    <li v-if="this.users_button" class="action-menu-item">
                        <a href="#" class="set-users" @click="$emit('set-users')">{{$t('message.setStudentPermissions')}}</a>
                    </li>
                    <li v-if="this.groups_button" class="action-menu-item">
                        <a href="#" class="set-groups" @click="$emit('set-groups')">{{$t('message.setGroupsPermissions')}}</a>
                    </li>
                    <li v-if="this.edit_button" class="action-menu-item">
                        <a href="#" class="edit-element" @click="editDialogVisible = true">{{$t('message.editElement')}}</a>
                    </li>
                    <li v-if="this.remove_button" class="action-menu-item">
                        <a href="#" class="remove-element" @click="removeDialogVisible = true">{{$t('message.deleteElement')}}</a>
                    </li>
                    <li v-if="this.add_child_button" class="action-menu-item">
                        <a href="#" class="add-child-element" @click="addChildDialogVisible = true">{{$t('message.addSubelement')}}</a>
                    </li>
                </ul>
            </div>
        </nav>
        <RemoveDialog
            :DialogVisible="this.removeDialogVisible"
            :element="this.element"
            @close="removeDialogVisible = false"
            @remove="$emit('remove')"
        />
        <EditDialog
            :DialogVisible="this.editDialogVisible"
            :element="this.element"
            @close="editDialogVisible = false"
            @edit="editDialogAction"
        />
        <AddChildDialog
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
export default {
    props: {
        buttons: Array,
        element: Object
    },
    components: { RemoveDialog, EditDialog, AddChildDialog },
    data() {
        return {
            edit_button: false,
            remove_button: false,
            groups_button: false,
            users_button: false,
            add_child_button: false,
            removeDialogVisible: false,
            editDialogVisible: false,
            addChildDialogVisible: false
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
        }
    }
};
</script>

<template>
    <nav class="action-menu bymousedown strucutal-element-menu">
        <a class="action-menu-icon" title="Aktionen" aria-expanded="true" aria-label="Aktionsmenü" href="#">
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
                    <a href="#" class="set-users" @click="$emit('set-users')">Freigabe für Nutzer setzen</a>
                </li>
                <li v-if="this.groups_button" class="action-menu-item">
                    <a href="#" class="set-groups" @click="$emit('set-groups')">Freigabe für Gruppen setzen</a>
                </li>
                <li v-if="this.edit_button" class="action-menu-item">
                    <a href="#" class="edit-element" @click="$emit('edit')">bearbeiten</a>
                </li>
                <li v-if="this.remove_button" class="action-menu-item">
                    <a href="#" class="remove-element" @click="$emit('remove')">löschen</a>
                </li>
            </ul>
        </div>
    </nav>
</template>
<script>
export default {
    props: {
        buttons: Array
    },
    data() {
        return {
            edit_button: false,
            remove_button: false,
            groups_button: false,
            users_button: false
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
                case 'groups':
                    view.groups_button = true;
                    break;
                case 'users':
                    view.users_button = true;
                    break;
            }
        });
    }
};
</script>

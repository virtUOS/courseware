<template>
    <div>
        <ul>
            <li v-on:click="selectList('groups', $event)" ref="groups">
                <h3 v-bind:class="[{ active: groupsActive }]">Gruppen</h3>
            </li>
            <li v-on:click="selectList('members', $event)" ref="members">
                <h3 v-bind:class="[{ active: membersActive }]">Teilnehmer</h3>
            </li>
        </ul>
    </div>
</template>
<script>
export default {
    name: 'ListSelector',
    components: {},
    data: function() {
        return {
            groupsActive: false,
            membersActive: false,
            listSelection: this.selection
        };
    },
    props: {
        selection: String
    },
    created() {
        if (this.selection == 'groups') {
            this.groupsActive = true;
            this.membersActive = false;
        } else {
            this.groupsActive = false;
            this.membersActive = true;
        }
    },
    methods: {
        selectList: function(list) {
            switch (list) {
                case 'groups':
                    this.groupsActive = true;
                    this.membersActive = false;
                    break;
                case 'members':
                    this.groupsActive = false;
                    this.membersActive = true;
                    break;
                default:
                    return false;
            }
            this.listSelection = list;
            this.$emit('listSelection', this.listSelection);
        }
    }
};
</script>
<style scoped>
ul {
    padding: 0;
}
li {
    float: left;
    list-style: none;
}
h3 {
    border: solid 1px #28497c;
    margin: 0;
    font-weight: 600;
    cursor: pointer;
    background-color: #ffffff;
    color: #28497c;
    padding: 16px;
    height: 1em;
    width: 116px;
}
h3.active {
    background-color: #28497c;
    color: #fff;
}
</style>

<template>
    <div id="editDialog" title="">
        <input type="hidden" id="editDialogElementId" />
        <label for="editDialogElementTitle">Titel:</label>
        <input type="text" id="editDialogElementTitle" name="editDialogElementTitle" />
        <br />
        <label for="publication_date">sichtbar ab:</label>
        <input type="date" name="publication_date" id="editDialogElementPublicationDate" @change="setMin" />
        <br />
        <label for="withdraw_date">unsichtbar ab:</label>
        <input type="date" name="withdraw_date" id="editDialogElementWithdrawDate" />
    </div>
</template>
<script>
export default {
    methods: {
        setMin: function(event) {
            $('#editDialogElementWithdrawDate').attr('min', $(event.currentTarget).val());
        }
    }
};
</script>

var editDialogNode = null;
var removeDialogNode = null;

export default {
    createEditDialog(node) {
        editDialogNode = node;
    },
    createRemoveDialog(node) {
        removeDialogNode = node;
    },

    useEditDialog(element, resolve, reject) {
        editDialogNode.find('#editDialogElementId').val(element.id);
        editDialogNode.find('#editDialogElementTitle').val(element.title);
        editDialogNode.dialog({
            resizable: false,
            title: element.title + ' bearbeiten',
            height: 'auto',
            width: 'auto',
            modal: true,
            buttons: {
                Speichern: function() {
                    var data = {};
                    data.id = $(this)
                        .find('#editDialogElementId')
                        .val();
                    data.title = $(this)
                        .find('#editDialogElementTitle')
                        .val();
                    data = JSON.stringify(data);
                    $.ajax({
                        type: 'PUT',
                        url: '../blocks/' + element.id,
                        contentType: 'application/json',
                        data: data,
                        success: function() {
                            resolve(data);
                        },
                        error: function() {
                            reject('can not store data!');
                        }
                    });
                    $(this).dialog('close');
                },
                Abbrechen: function() {
                    $(this)
                        .dialog('destroy')
                        .hide();
                }
            },
            close: function() {
                $(this)
                    .dialog('destroy')
                    .hide();
            },
            create: function() {
                let $buttons = $(this)
                    .parent()
                    .find('.ui-dialog-buttonset .ui-button');
                console.log($buttons);
                $buttons.eq(0).addClass('accept');
                $buttons.eq(1).addClass('cancel');
            }
        });
    },
    useRemoveDialog(element, resolve, reject) {
        removeDialogNode.find('#editDialogElementId').val(element.id);
        removeDialogNode.find('#editDialogElementTitle').val(element.title);
        removeDialogNode
            .dialog({
                resizable: false,
                title: element.title + ' löschen',
                height: 'auto',
                width: 'auto',
                modal: true,
                buttons: {
                    Ja: function() {
                        var data = {};
                        data.id = $(this)
                            .find('#editDialogElementId')
                            .val();
                        data = JSON.stringify(data);
                        $.ajax({
                            type: 'DELETE',
                            url: '../blocks/' + element.id,
                            contentType: 'application/json',
                            data: data,
                            success: function() {
                                resolve(data);
                            },
                            error: function() {
                                reject('can not remove node!');
                            }
                        });
                        $(this).dialog('close');
                    },
                    Nein: function() {
                        $(this)
                            .dialog('destroy')
                            .hide();
                    }
                },
                close: function() {
                    $(this)
                        .dialog('destroy')
                        .hide();
                },
                create: function() {
                    let $buttons = $(this)
                        .parent()
                        .find('.ui-dialog-buttonset .ui-button');
                    console.log($buttons);
                    $buttons.eq(0).addClass('accept');
                    $buttons.eq(1).addClass('cancel');
                }
            })
            .prev('.ui-dialog-titlebar')
            .css('background', '#d60000');
    }
};

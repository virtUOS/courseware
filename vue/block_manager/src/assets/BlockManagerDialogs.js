var editDialogNode = null;
var removeDialogNode = null;

export default {
    createEditDialog(node) {
        editDialogNode = node;
    },
    createRemoveDialog(node) {
        removeDialogNode = node;
    },

    useEditDialog(element, isChapter, resolve, reject) {
        editDialogNode.find('#editDialogElementId').val(element.id);
        editDialogNode.find('#editDialogElementTitle').val(element.title);
        if (isChapter) {
            editDialogNode.find('label[for=publication_date]').show();
            editDialogNode.find('label[for=withdraw_date]').show();
            if (element.publication_date) {
                editDialogNode
                    .find('#editDialogElementPublicationDate')
                    .val(new Date(element.publication_date).toISOString().substr(0, 10))
                    .show();
                editDialogNode
                    .find('#editDialogElementWithdrawDate')
                    .attr('min', new Date(element.publication_date).toISOString().substr(0, 10));
            } else {
                editDialogNode
                    .find('#editDialogElementPublicationDate')
                    .val('')
                    .show();
                editDialogNode.find('#editDialogElementWithdrawDate').attr('min', null);
            }
            if (element.withdraw_date) {
                editDialogNode
                    .find('#editDialogElementWithdrawDate')
                    .val(new Date(element.withdraw_date).toISOString().substr(0, 10))
                    .show();
            } else {
                editDialogNode
                    .find('#editDialogElementWithdrawDate')
                    .val('')
                    .show();
            }
        } else {
            editDialogNode.find('label[for=publication_date]').hide();
            editDialogNode.find('label[for=withdraw_date]').hide();
            editDialogNode
                .find('#editDialogElementPublicationDate')
                .val('')
                .hide();
            editDialogNode
                .find('#editDialogElementWithdrawDate')
                .val('')
                .hide();
        }
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
                    if (isChapter) {
                        let pattern = /(\d{4})-(\d{2})-(\d{2})/;
                        let PublicationDate = $(this)
                            .find('#editDialogElementPublicationDate')
                            .val();
                        if (PublicationDate) {
                            data.publication_date = Math.floor(Date.parse(PublicationDate)) / 1000;
                            data.publication_date_readable = PublicationDate.replace(pattern, '$3.$2.$1');
                        } else {
                            data.publication_date = NaN;
                        }
                        let WithdrawDate = $(this)
                            .find('#editDialogElementWithdrawDate')
                            .val();
                        if (WithdrawDate) {
                            data.withdraw_date = Math.floor(Date.parse(WithdrawDate)) / 1000;
                            data.withdraw_date_readable = WithdrawDate.replace(pattern, '$3.$2.$1');
                        } else {
                            data.withdraw_date = NaN;
                        }
                        if (data.withdraw_date <= data.publication_date) {
                            data.withdraw_date = NaN;
                        }
                    }
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
                $buttons.eq(0).addClass('accept');
                $buttons.eq(1).addClass('cancel');
            }
        });
    },
    useRemoveDialog(element, isStrucutalElement, resolve, reject) {
        let title = element.title;
        removeDialogNode.find('#editDialogElementId').val(element.id);
        removeDialogNode.find('#editDialogElementTitle').val(element.title);
        if (isStrucutalElement) {
            removeDialogNode.find('.remove-strucutal-element-question').show();
            removeDialogNode.find('.remove-block-element-question').hide();
        } else {
            removeDialogNode.find('.remove-strucutal-element-question').hide();
            removeDialogNode.find('.remove-block-element-question').show();
            title = element.readable_name;
        }
        removeDialogNode
            .dialog({
                resizable: false,
                title: title + ' löschen',
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

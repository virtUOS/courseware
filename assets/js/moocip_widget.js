jQuery(function ($) {

    $(document).on('click', 'a.kill', function (event) {
        event.preventDefault();

        var article = $(this).closest('article'),
            cid = article.attr('data-cid');

        if (confirm('Wollen Sie wirklich die Mitgliedschaft beenden?')) {
            $(event.target).showAjaxNotification();

            $.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/mooc/courses/leave/' + cid,
                type: 'POST',
                dataType: 'json'
            }).then(
                function onSuccess(data) {
                    $(event.target).hideAjaxNotification();
                    $.when(article.hide('blind')).then(function () { article.remove(); })
                },

                function onError(jqXHR, textStatus, errorThrown) {
                    $(event.target).hideAjaxNotification();
                    article.effect('shake');
                    console.log(textStatus);
                });
        }
    });
});

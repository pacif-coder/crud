$(document).ready(function () {
    $('[data-role="grid-button-send"]').click(function (event) {
        var grid = '#' + $(this).data('target');
        if ('matrix-grid-view' == $(grid).data('role')) {
            var rows = $(grid).matrixGridView('getSelectedRows');
        } else {
            var rows = $(grid).yiiGridView('getSelectedRows');
        }        

        if (!rows.length) {
            return;
        }

        var text = $(this).data('confirmMessage');
        if (text && !window.confirm(text)) {
            return;
        }

        if ($(this).data('isInsideForm')) {
            $(grid).parents('form').attr('action', $(this).data('url')).submit();
            return;
        }        

        var form = $("<form/>", {
            'action': $(this).data('url'),
            'method': 'post',
            'class': 'gridview-delete-form',
            'style': 'display:none',
        }).appendTo($(grid));

        var name = $(this).data('checkboxName') ? $(this).data('checkboxName') : 'selection[]';
        $.each(rows, function (key, value) {
            form.append($('<input/>').attr({type: 'hidden', name: name, value: value}));
        });

        var csrfParam = window.yii.getCsrfParam();
        var csrfToken = window.yii.getCsrfToken();
        if (csrfParam && csrfToken) {
            form.append($('<input/>').attr({'type': 'hidden', 'name': csrfParam, 'value': csrfToken}));
        }

        form.submit();
    });
});
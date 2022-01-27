$(document).ready(function () {

    $('[data-role="grid-button-send"]').each(function() {
        var button = $(this);

        if (button.data('checkboxRole')) {
            var checkboxRole = button.data('checkboxRole');
            var grid = $('#' + $(this).data('target'));
            var checkboxSelect = '[data-role="' + checkboxRole + '"]';

            function onSelectionCnange () {
                var count = $(checkboxSelect + ':checked', grid).length + 0;
                button.prop('disabled', count == 0);
            }

            $(checkboxSelect, grid).click(onSelectionCnange);
            $(checkboxSelect, grid).change(onSelectionCnange);
            onSelectionCnange();
        }

        button.click(function (event) {
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
});
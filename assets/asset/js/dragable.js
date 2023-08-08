$(document).ready(function () {
    $('[data-dragable]').each(function() {
        var url = $(this).data('dragableUrl');
        var selector = $(this).data('dragableSelector');
        var orderParam = $(this).data('dragableOrderParam');
        var isGrid = $(this).data('isGrid');

        var handleSelector = '[data-role="drag-icon"]';
        var handleExist = $(selector + ' ' + handleSelector, this).length > 0;

        $(selector, this).sortable({
            axis: isGrid? false : 'y',
            cancel: 'a',
            cursor: 'move',
            handle: handleExist? handleSelector : false,
            placeholder: isGrid? false : 'ui-state-highlight',

            stop : function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-key'});

                var form = $("<form/>", {
                    'action': url,
                    'method': 'post',
                    'class': 'gridview-sortable-form',
                    'style': 'display:none'
                }).appendTo($(this).sortable());

                $.each(order, function (key, value) {
                    form.append($('<input/>').attr({type: 'hidden',
                        name: orderParam + '[' + key + ']', value: value}));
                });

                var csrfParam = window.yii.getCsrfParam();
                var csrfToken = window.yii.getCsrfToken();
                if (csrfParam && csrfToken) {
                    form.append($('<input/>').attr({'type': 'hidden', 'name': csrfParam, 'value': csrfToken}));
                }

                form.submit();
            },

            helper: function (e, tr)
            {
                var originals = tr.children();
                var helper = tr.clone();
                helper.width(tr.width());

                // Set helper td width to the original width
                helper.children().each(function (index) {
                    $(this).width(originals.eq(index).width());
                });

                return helper;
            }
        });

    });
});
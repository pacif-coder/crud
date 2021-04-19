$(document).ready(function () {
    $('[data-dragable]').each(function() {
        var url = $(this).data('dragableUrl');
        var selector = $(this).data('dragableSelector');
        var orderParam = $(this).data('dragableOrderParam');        
                
        $(selector, this).sortable({
            axis: 'y',
            cancel: 'a',
            cursor: 'move',
            forcePlaceholderSize: true,
            forceHelperSize: true,
            placeholder: 'ui-state-highlight',

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

                // Set helper td width to the original width
                helper.children().each(function (index)
                {
                    $(this).width(originals.eq(index).width());
                });

                return helper;
            }            
        });        

    });
});
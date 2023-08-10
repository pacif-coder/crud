$(document).ready(function () {
    function sendAjax() {
        var ajaxUrl = $(this).data('ajaxUrl');

        var jqxhr = $.getJSON({
            url: ajaxUrl,
        }).done(function(data) {
            $.each(data, function(id, html){
                $(id).replaceWith(html)
            });

            window.setTimeout(parseAjaxForm, 1000);
        }).fail(function(event) {
            console.log(event);
        });
    }

    function parseAjaxForm() {
        $('[data-role="refresh-ajax-form"]').each(sendAjax);
    }

    parseAjaxForm();
});
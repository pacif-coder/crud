$(document).ready(function () {
    $('[data-role="submit-form-with-url"]').click(function () {
        var form = $(this).parents('form');
        var url = $(this).data('url');

        form.attr('action', url);
        form.submit();
    });
});
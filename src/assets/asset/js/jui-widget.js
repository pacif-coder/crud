$(document).ready(function () {
    $('[data-widget="autocomplete"]').each(function () {
        var options = $(this).data();
        $(this).autocomplete(options);
    });
});
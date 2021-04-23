$(document).ready(function() {
    $("[data-role='hier-checkbox-martix-list'] [data-role='hier-checkbox-martix-list-header-checkbox']").click(function() {
        var is_checked = $(this).prop('checked');
        var block =$(this).parents("[data-role='hier-checkbox-martix-list-checkboxs']");

        $(':checkbox', block).prop('checked', is_checked);
    });
});
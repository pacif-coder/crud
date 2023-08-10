$(document).ready(function () {
    $('.to-expand').click(function () {
        var node = $(this).parent('.tree-node');
        node.removeClass('collapsed').addClass('expanded');
    });

    $('.to-collapse').click(function () {
        var node = $(this).parent('.tree-node');
        node.removeClass('expanded').addClass('collapsed');
    });

//    $('.category').click(function () {
//        var node = $(this).parent('.tree-node');
//        if (node.hasClass('collapsed')) {
//            node.removeClass('collapsed').addClass('expanded');
//        } else {
//            node.removeClass('expanded').addClass('collapsed');
//        }
//    });
//
//    var currentHeight = $('.current').outerHeight();
//    $('.current .current_line').css('height', currentHeight);
});
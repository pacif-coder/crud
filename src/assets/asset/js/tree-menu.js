$(document).ready(function () {
    $('[data-role="tree-menu"]').each(function() {
        var control = $(this);
        var source = control.data('source');
        var parents = control.data('parents');
        var notOpenLastParent = control.data('notOpenLastParent');

        var loadParents = false;
        var parentIndex = 0;

        var xhr;

        var template = $('[data-role="template"]', control).clone();
        $('[data-role="template"]', control).remove();

        template.removeClass('d-none');
        template.attr('data-role', 'node');

        function toCol() {
            var node = $(this).closest('[data-role="node"]');
            node.removeClass('open');
        }

        function toExp() {
            loadParents = false;

            var node = $(this).closest('[data-role="node"]');
            loadTo(node.data('id'), node.data('type'));
        }

        function loadTo(id, type) {
            var targetNode;
            if (id == '' && type == '') {
                targetNode = control;
            } else {
                var sel = '[data-id="' + id + '"][data-type="' + type + '"]';
                var targetNode = $(sel, control);
            }

            if (notOpenLastParent && loadParents && parents.length <= (parentIndex + 1)) {

            } else {
                targetNode.addClass('open');
            }

            if (targetNode.data('isLoaded')) {
                return;
            }

            if (xhr) {
                xhr.abort();
            }

            var get = {};
            if (targetNode.data('id')) {
                get['id'] = targetNode.data('id');
            }
            if (targetNode.data('type')) {
                get['type'] = targetNode.data('type');
            }

            var children = $('[data-role="children"]', targetNode);
            if (!children.length) {
                children = targetNode;
            }

            xhr = $.ajax({
                url: source,
                data: get
            }).done(function(data) {
                $('[data-role="wait"]', children).remove();

                var nodeData;
                for (var key in data) {
                    var nodeData = data[key];
                    var newNode = template.clone();

                    var link = $('[data-role="link"]', newNode);
                    if ('text' in nodeData) {
                        link.text(nodeData.text);
                    }

                    if ('link' in nodeData) {
                        link.attr('href', nodeData.link);
                    }

                    if ('id' in nodeData) {
                        newNode.attr('data-id', nodeData.id);
                    }

                    if ('type' in nodeData) {
                        newNode.attr('data-type', nodeData.type);
                    }

                    if ('isFolder' in nodeData && !nodeData.isFolder) {
                        $('[data-role="exp-col"]', newNode).remove();
                    }

                    children.append(newNode);
                }

                targetNode.data('isLoaded', true);

                $('[data-role="node"]', control).removeClass('current');
                targetNode.addClass('current');

                $('[data-role="node"] [data-role="to-col"]', children).bind('click', toCol);
                $('[data-role="node"] [data-role="to-exp"]', children).bind('click', toExp);

                if (!loadParents) {
                    return;
                }

                parentIndex++;
                if (parents.length <= parentIndex) {
                    loadParents = false;
                    return;
                }

                var nextNodeDesc = parents[parentIndex];
                loadTo(nextNodeDesc.id, nextNodeDesc.type);
            });
        }

        loadParents = true;
        loadTo('', '');
    });
});
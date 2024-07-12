$(document).ready(function () {
    $('[data-role="checkbox-dropdown"]').each(function() {
        var control = $(this);

        var toggle = control.find('[data-role="toggle"]');
        var radios = control.find('[data-role="radios"]');
        var emptyLabel = toggle.find('[data-role="emptyLabel"]');

        function toggleEmptyLabel()
        {
            if (!emptyLabel) {
                return;
            }

            var someOneChecked = radios.find(':checkbox:checked').length > 0;
            emptyLabel.toggleClass('d-none', someOneChecked);
        }

        function toggleTextInSelection(inputID, isChecked)
        {
            var select = 'span[data-input-id="' + inputID + '"]';
            toggle.find(select).toggleClass('d-none', !isChecked);

            toggleEmptyLabel();

            radios.find(':checkbox:first-child').trigger('change');
        }

        function changeTitle()
        {
            var select = 'span[data-input-id]:not(.d-none)';
        }

        toggleEmptyLabel();
        $('div', radios).each(function() {
            var div = $(this);

            var label = div.find('label');
            var labelText = label.text();

            var span = $('<span>').text(labelText);
            span.attr('data-input-id', label.attr('for'));
            span.append('<sup class="bi bi-x-lg text-danger" data-role="del"></sup>');

            var isChecked = div.find(':checkbox').is(':checked');
            if (!isChecked) {
                span.addClass('d-none');
            }

            toggle.append(span);
        });

        $(':checkbox', radios).on('click', function(event) {
            event.stopPropagation();

            var id = $(this).attr('id');
            toggleTextInSelection(id, $(this).is(':checked'));
        });

        $('[data-role="del"]', toggle).on('click', function(event) {
            event.stopPropagation();

            var id = $(this).closest('span').attr('data-input-id');
            var checkbox = ':checkbox[id="' + id + '"]';
            radios.find(checkbox).prop('checked', false);

            toggleTextInSelection(id, false);
        });

        toggle.on('click', function(event) {
            event.stopPropagation();

            control.toggleClass('open');
        });

        $('body').on('click', function(event) {
            if (event.target.closest('[data-role="checkbox-dropdown"]')) {
                return;
            }

            control.removeClass('open');
        });

        $(document).on('keydown', function(event) {
            if (event.key === "Escape" || event.keyCode === 27) {
                control.removeClass('open');
            }
        });
    });
});
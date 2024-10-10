$(document).ready(function () {
    $('[data-role="checkbox-dropdown"]').each(function() {
        var control = $(this);

        var toggle = control.find('[data-role="toggle"]');
        var radios = control.find('[data-role="radios"]');
        var emptyLabel = toggle.find('[data-role="emptyLabel"]');
        let preventSendChange = true;

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

//            radios.find(':checkbox:first-child').trigger('change');
        }

        function checkChange()
        {
            if (control.hasClass('open')) {
                return;
            }

            let isChange = false;
            radios.find(':checkbox').each(function() {
                let checkbox = $(this);
                let isChecked = checkbox.is(':checked');
                if (checkbox.data('isChecked') != isChecked) {
                    isChange = true;
                }
            });

            if (!isChange) {
                return;
            }

            preventSendChange = false;
            radios.find(':checkbox:first-child').trigger('change');
        }

        function changeTitle()
        {
            var select = 'span[data-input-id]:not(.d-none)';
        }

        toggleEmptyLabel();
        $('div', radios).each(function() {
            let div = $(this);

            let label = div.find('label');
            let labelText = label.text();

            let span = $('<span>').text(labelText);
            span.attr('data-input-id', label.attr('for'));
            span.append('<sup class="bi bi-x-lg text-danger" data-role="del"></sup>');

            let checkbox = div.find(':checkbox');
            let isChecked = checkbox.is(':checked');
            checkbox.data('isChecked', isChecked);
            if (!isChecked) {
                span.addClass('d-none');
            }

            toggle.append(span);
        });

        $(':checkbox', radios).on('change', function(event) {
            if (preventSendChange) {
                event.stopPropagation();
            }

            var id = $(this).attr('id');
            toggleTextInSelection(id, $(this).is(':checked'));
        });

        $('[data-role="del"]', toggle).on('click', function(event) {
            event.stopPropagation();

            var id = $(this).closest('span').attr('data-input-id');
            var checkbox = ':checkbox[id="' + id + '"]';
            radios.find(checkbox).prop('checked', false);

            toggleTextInSelection(id, false);
            if (control.hasClass('open')) {
                return;
            }

            preventSendChange = false;
            radios.find(':checkbox:first-child').trigger('change');
        });

        toggle.on('click', function(event) {
            event.stopPropagation();

            control.toggleClass('open');
            checkChange();
        });

        $('body').on('click', function(event) {
            if (event.target.closest('[data-role="checkbox-dropdown"]')) {
                return;
            }

            control.removeClass('open');
            checkChange();
        });

        $(document).on('keydown', function(event) {
            if (event.key === "Escape" || event.keyCode === 27) {
                control.removeClass('open');
                checkChange();
            }
        });
    });
});
(function() {
    var $generic = $('.line[data-type="generic"]');

    var selectOneLine = function() {
        var $linerow = $(this);
        var linetype = $linerow.attr('data-type');
        var id = $linerow.attr('data-id');
        var $line = $('.line[data-type="' + (linetype == 'transferout' && 'transferin' || linetype) + '"]');

        $('.linerow').not($linerow).removeClass('selected').find('.select-column [type="checkbox"]').prop('checked', false);
        $linerow.addClass('selected').find('.select-column [type="checkbox"]').prop('checked', true);

        blends_api.lineGet(linetype, id, function(line) {
            $line.attr('data-id', id).show();
            $('.line').removeAttr('data-id').not($line).hide();

            if (linetype == 'transferout') {
                line.amount = (-line.amount).toFixed(2);
                line.from = line.jar;
                line.jar = line.to;
                delete line.to;
            }

            for (const property in line) {
                var $property = $line.find('[name="' + property + '"]');

                $property.val(line[property]);
            }
        });
    };

    var refreshDisplayedLineEditor = function() {
        var $selected = $('.select-column [type="checkbox"]:checked');

        $(this).closest('tr[data-id]').toggleClass('selected', $(this).is(':checked'));

        if ($selected.length == 0) {
            $('.line').removeAttr('data-id').hide();
        } else if ($selected.length == 1) {
            selectOneLine.apply($selected.closest('.linerow')[0]);
        } else {
            var generic_builder = {};
            var generic_fields = $generic.find('[name]:not([type="submit"])').map(function(){
                return $(this).attr('name');
            });

            $.each(generic_fields, function(){
                generic_builder[this + ''] = [];
            });

            $selected.each(function() {
                var $linerow = $(this).closest('.linerow');

                $.each(generic_fields, function() {
                    var name = this + '';
                    var value = $linerow.find('[data-name="' + name + '"]').attr('data-value');

                    if (generic_builder[name].length < 2 && generic_builder[name].indexOf(value) == -1) {
                         generic_builder[name].push(value);
                    }
                });
            });

            $.each(generic_fields, function() {
                var field = this + '';
                var use = generic_builder[field].length == 1;
                var $field = $generic.find('[name="' + field + '"]');
                var $checkbox = $generic.find('[data-for="' + field + '"]');

                $field.val(use && generic_builder[field][0] || '');
                $checkbox.prop('checked', use);
            });

            $('.line').not($generic).removeAttr('data-id').hide();
            $generic.show();
        }
    };

    $('.linerow').on('click', selectOneLine);
    $('.trigger-add-line').on('click', function(event){
        event.stopPropagation();
        event.preventDefault();
        var $plus = $(this);
        var linetype = $plus.attr('data-type');
        var $line = $('.line[data-type="' + linetype + '"]');

        $('.line').find('[name]').val(null);

        var fields = $line.find('[name]:not([type="submit"])').map(function(){
            return $(this).attr('name');
        });

        $.each(fields, function(){
            $('.line').find('[name=' + this + ']').val($plus.attr('data-' + this));
        });

        $('.line').removeAttr('data-id').not($line).hide();
        $line.show();
        closeModals();
    });
    $('.select-column input[type="checkbox"], .selectall').on('click', refreshDisplayedLineEditor);

    $('.edit-form .saveline').on('click', function(e) {
        e.preventDefault();

        var $line = $(this).closest('.line');
        var $form = $(this).closest('form');
        var $linerow = $('tr[data-type="' + $line.attr('data-type') + '"][data-id="' + $line.attr('data-id') + '"]');
        var formData = new FormData($form[0]);
        var line = Object.fromEntries(formData);

        var handleSave = function() {
            blends_api.lineSave($line.attr('data-type'), [line], function(response){
                location.reload();
            });
        };

        var $fileInputs = $form.find('input[type="file"]');
        var numLoadedFiles = 0;

        if (!$fileInputs.length) {
            handleSave();
        }

        $fileInputs.each(function(){
            var $input = $(this);
            var file = $input[0].files[0];
            delete data[$input.attr('name')];

            if (!file) {
                numLoadedFiles++;

                if (numLoadedFiles == $fileInputs.length) {
                    handleSave();
                }

                return;
            }

            var reader = new FileReader();

            reader.onload = function(event) {
                data[$input.attr('name')] = btoa(event.target.result);
                numLoadedFiles++;

                if (numLoadedFiles == $fileInputs.length) {
                    handleSave();
                }
            };

            reader.readAsBinaryString(file);
        });
    });

    $('.bulk-edit-form .bulksave').on('click', function(e){
        e.preventDefault();

        var $editForm = $(this).closest('form');
        var form = $editForm[0];
        var data = {};
        var blend = $editForm.data('blend');
        var $selected = getSelected();
        var query;
        var $fileInputs = $editForm.find('input[type="file"]');

        $editForm.find("input[data-for]:checked").each(function() {
            var rel_field = $(this).attr('data-for');
            var $rel_field = $editForm.find('[name="' + rel_field + '"]');

            data[rel_field] = $rel_field.val();
        });

        $fileInputs.each(function() {
            var rel_field = $(this).attr('name') + '_delete';

            $editForm.find('[name="' + rel_field + '"]').each(function(){
                data[rel_field] = $(this).val();
            });
        });

        if (!Object.keys(data).length && !$fileInputs.length) {
            closeModals();
            return;
        }

        if ($selected.length) {
            query = getSelectionQuery($selected);
        } else {
            query = current_filter;
        }

        var handleSave = function() {
            blends_api.updateBlend('ledger', query, data, function(){
                window.location.reload();
            });
        };

        var numLoadedFiles = 0;

        if (!$fileInputs.length) {
            handleSave();
        }

        $fileInputs.each(function(){
            var $input = $(this);
            var file = $input[0].files[0];

            if (!file) {
                numLoadedFiles++;

                if (numLoadedFiles == $fileInputs.length) {
                    handleSave();
                }

                return;
            }

            var reader = new FileReader();

            reader.onload = function(event) {
                data[$input.attr('name')] = btoa(event.target.result);
                numLoadedFiles++;

                if (numLoadedFiles == $fileInputs.length) {
                    handleSave();
                }
            };

            reader.readAsBinaryString(file);
        });
    });

    $('.delete-selected').on('click', function() {
        var $selected = getSelected();

        if (!$selected.length) {
            return;
        }

        if (confirm('Delete ' + $selected.length + ' lines?')) {
            blends_api.blendDelete('ledger', getSelectionQuery($selected), function(){
                window.location.reload();
            });
        }
    });


    $('.select-column input').prop('checked', false);

    var onResize = function() {
        if ($('.easy-table').length) {
            $('.line').css({top: $('.easy-table').offset().top + 'px', left: ($('.easy-table').offset().left + $('.easy-table').outerWidth() + 30) + 'px'});
        }
    }

    var resizeTimer = null;

    $(window).on('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 300); });

    onResize();
})();
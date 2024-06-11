(function() {
    var $generic = $('.line[data-type="generic"]');

    var clearInputs = function() {
        $(this).find('input[type="file"]').each(function(){
            var $controls = $(this).closest('.file-field-controls');
            var $actions = $controls.find('.file-field-controls__actions');
            var $inputs = $controls.find('.file-field-controls__input');
            var $downloadlink = $controls.find('a');
            var $cancel = $controls.find('.file-field-controls__cancel');

            $actions.hide();
            $cancel.hide();
            $inputs.show();
            $downloadlink.removeAttr('href');
        });

        $(this).find('[name]').each(function () {
            if ($(this).is('[type="checkbox"]')) {
                $(this).prop('checked', false);
            } else {
                $(this).val(null);
            }
        });
    };

    var deselectAllLines = function() {
        $('.linerow').removeClass('selected').find('.select-column [type="checkbox"]').prop('checked', false);
        $('.line [name]').val(null);
        $('.line').hide();
        $('.delete-selected').addClass('disabled');
    };

    var getLine = function (linetype, id) {
        for (var i = 0; i < window.lines.length; i++) {
            var line = window.lines[i];

            if (line.type == linetype && line.id == id) {
                return ledger_map_line(line);
            }
        }

        return null;
    };

    var saveLines = function(lines, success) {
        $.ajax(window.location.pathname + '/ajax/save', {
            method: 'post',
            contentType: false,
            processData: false,
            headers: {
                'X-Auth': getCookie('token'),
                'X-Base-Version': base_version,
            },
            data: JSON.stringify(lines),
            success: success,
            error: function(data){
                alert(data.responseJSON.error);
            }
        });
    };

    var selectOneLine = function() {
        var $linerow = $(this);
        var line = getLine($linerow.attr('data-type'), $linerow.attr('data-id'));

        if (!line) {
            alert('Could not find ' + $linerow.attr('data-type') + '/' + $linerow.attr('data-id'));
            return;
        }

        var $line = $('.line[data-type="' + line.type + '"]');

        if (!$line) {
            alert('Could not find the linetype form for  ' + line.type);
            return;
        }

        $('.linerow').not($linerow).removeClass('selected').find('.select-column [type="checkbox"]').prop('checked', false);

        $linerow.addClass('selected').find('.select-column [type="checkbox"]').prop('checked', true);

        $line.find('.saveline').show();
        $line.find('.bulkadd').hide();
        $line.find('[name="id"]').closest('.form-row').show();

        clearInputs.apply($line);
        $line.find('[name="date"]').closest('.form-row').show();

        $('.delete-selected').removeClass('disabled');

        $line.attr('data-id', line.id).show();
        $('.line').removeAttr('data-id').not($line).hide();

        for (const _property in line) {
            var property = _property.replace(/_path$/, '');
            var $property = $line.find('[name="' + property + '"]');

            if ($property.is('select')) {
                $property.find('[data-adhoc]').remove();

                if (line[property] && !$property.find('option[value="' + line[property] + '"]').length) {
                    $property.prepend('<option data-adhoc="1" value="' + line[property] + '" selected="selected">' + line[property] + '</option>');
                }
            }

            if ($property.is('[type="file"]')) {
                var $controls = $property.closest('.file-field-controls');
                var $actions = $controls.find('.file-field-controls__actions');
                var $inputs = $controls.find('.file-field-controls__input');
                var $downloadlink = $controls.find('a');
                var $cancel = $controls.find('.file-field-controls__cancel');

                if (line[property + '_path']) {
                    $cancel.show();
                    $actions.show();
                    $inputs.hide();
                    $downloadlink.attr('href', '/api/download/' + line[property + '_path']);
                }
            } else if ($property.attr("type") == 'checkbox') {
                $property.prop('checked', line[property]);
            } else {
                $property.val(line[property]);
            }
        }
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

        $('.delete-selected').toggleClass('disabled', !$selected.length);
    };

    $('.linerow').on('click', selectOneLine);

    $('.trigger-add-line').on('click', function(event){
        event.stopPropagation();
        event.preventDefault();
        $('.linerow').removeClass('selected').find('.select-column [type="checkbox"]').prop('checked', false);

        var $plus = $(this);
        var linetype = $plus.attr('data-type');
        var $line = $('.line[data-type="' + linetype + '"]');

        $line.find('.saveline').show();
        $line.find('.bulkadd').hide();
        $line.find('[name="id"]').closest('.form-row').hide();

        clearInputs.apply($line);

        var fields = $line.find('[name]:not([type="submit"])').map(function(){
            return $(this).attr('name');
        });

        $.each(fields, function(){
            $('.line').find('[name=' + this + ']').val($plus.attr('data-' + this));
        });

        $('.line').removeAttr('data-id').not($line).hide();
        $line.show();
        $('.delete-selected').addClass('disabled');
        closeModals();
    });

    $('.select-column input[type="checkbox"], .selectall').on('click', refreshDisplayedLineEditor);

    $('.edit-form .saveline').on('click', function(e) {
        e.preventDefault();

        var $line = $(this).closest('.line');
        var $form = $(this).closest('form');
        var formData = new FormData($form[0]);
        var line = Object.fromEntries(formData);

        $form.find('[name][type="checkbox"]').each(function () {
            line[$(this).attr('name')] = $(this).prop('checked');
        });

        line.type = $line.attr('data-type');

        line = ledger_unmap_line(line);

        var handleSave = function() {
            saveLines([line], function(data, textStatus, request) {
                $('#new-vars-here').append($('<input name="version" value="' + request.getResponseHeader('X-Version') + '">'))
                cvsApply();
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
            delete line[$input.attr('name')];

            if (!file) {
                numLoadedFiles++;

                if (numLoadedFiles == $fileInputs.length) {
                    handleSave();
                }

                return;
            }

            var reader = new FileReader();

            reader.onload = function(event) {
                line[$input.attr('name')] = btoa(event.target.result);
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

        var $form = $(this).closest('form');
        var form = $form[0];
        var data = {};
        var $selected = getSelected();
        var query;
        var $fileInputs = $form.find('input[type="file"]');

        $form.find("input[data-for]:checked").each(function() {
            var rel_field = $(this).attr('data-for');
            var $rel_field = $form.find('[name="' + rel_field + '"]');

            data[rel_field] = $rel_field.val();
        });

        $fileInputs.each(function() {
            var rel_field = $(this).attr('name') + '_delete';

            $form.find('[name="' + rel_field + '"]').each(function(){
                data[rel_field] = $(this).val();
            });
        });

        if (!Object.keys(data).length && !$fileInputs.length) {
            closeModals();
            return;
        }

        if (!$selected.length) {
            return;
        }

        query = getSelectionQuery($selected);

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

    $('.edit-form .bulkadd').on('click', function(e){
        e.preventDefault();

        var $line = $(this).closest('.line');
        var data = {};
        var linetype = $line.attr('data-type');

        $line.find("[name]").each(function() {
            data[$(this).attr('name')] = $(this).val();
        });

        delete data.id;
        delete data.date;

        var handleSave = function() {
            blends_api.linetypeAdd(linetype, repeater, range_from, range_to, data);
        };

        var $fileInputs = $line.find('input[type="file"]');
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

    $('.delete-selected').on('click', function(e) {
        e.preventDefault();

        var $selected = getSelected();

        if (!$selected.length) {
            return;
        }

        if (confirm('Delete ' + $selected.length + ' line' + ($selected.length != 1 && 's' || '') + '?')) {
            var lines = $selected.map(function () {
                return {
                    "type": $(this).data('type'),
                    "id": $(this).data('id'),
                    "_is": false
                };
            }).get();

            saveLines(lines, function(data, textStatus, request) {
                $('#new-vars-here').append($('<input name="version" value="' + request.getResponseHeader('X-Version') + '">'))
                cvsApply();
            });
        }
    });

    $('.trigger-bulk-add').on('click', function(event){
        closeModals();
        deselectAllLines();

        var linetype = $(this).attr('data-type');
        var $line = $('.line[data-type="' + linetype + '"]');

        clearInputs.apply($line);

        $line.find('.saveline').hide();
        $line.find('.bulkadd').show();
        $line.find('[name="id"]').closest('.form-row').hide();

        $('.line').not($line).hide();
        $line.show();
        $line.find('[name="date"]').closest('.form-row').hide();
    });

    $('.select-column input').prop('checked', false);

    var onResize = function() {
        if ($('.easy-table').length) {
            if ($(window).width() >= 1200) {
                $('.floatline').css({top: $('.easy-table').offset().top + 'px', left: ($('.easy-table').offset().left + $('.easy-table').outerWidth() + 30) + 'px', width: ''});
            } else {
                $('.floatline').css({top: $('.navbar').outerHeight() + 'px', left: 0, width: '100%'});
            }
        }
    }

    var resizeTimer = null;

    $(window).on('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 300); });

    onResize();

    $('.lineclose').on('click', function() {
        deselectAllLines();
    });

    window.ledger_map_line = function (line) { return line; }
    window.ledger_unmap_line = function (line) { return line; }
})();

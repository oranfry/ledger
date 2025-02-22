(function() {
    var $lastSelected = null;
    var $lineContainer = $('#line-container');
    var lineContainerBg = null;

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

        $(this).find('.includeme').prop('checked', false);

        $(this).find('[name]').each(function () {
            if ($(this).is('[type="checkbox"]')) {
                $(this).prop('checked', false);
            } else {
                $(this).val(null);
            }
        });
    };

    var deselectAllLines = function() {
        $('.linerow').removeClass('selected');
        $('.delete-selected').addClass('disabled');

        refreshDisplayedLineEditor();
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

    var saveLines = function(lines, differential, success) {
        let headers = {
            'X-Auth': getCookie('token'),
            'X-Base-Version': base_version,
        };

        if (differential) {
            headers['X-Differential'] = 'True';
        }

        $.ajax(window.location.pathname + '/ajax/save', {
            method: 'post',
            contentType: false,
            processData: false,
            headers: headers,
            data: JSON.stringify(lines),
            success: success,
            error: function(data) {
                alert(data.responseJSON.error);
            }
        });
    };

    var modifySelection = function(append, range) {
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

        if (!append) {
            $('.linerow').not($linerow).removeClass('selected');
        }

        if (range && $lastSelected && $lastSelected != $linerow) {
            var $found = $(), opened = false, closed = false;

            $linerow.closest('.easy-table').find('.linerow').each(function () {
                var isBoundary = $(this).is($linerow) || $(this).is($lastSelected);

                if (isBoundary && !opened) {
                    isBoundary = false;
                    opened = true;
                }

                if (opened && !closed) {
                    $found.push(this);
                }

                if (isBoundary && opened) {
                    closed = true;
                }
            });

            $linerow = $found;
        }

        $linerow.addClass('selected');
    };

    var createLine = function (linetype) {
        let $form, $line = $('<div class="line floatline edit-form"></div>')
            .data('type', linetype.name)
            .append($('<div class="lineclose"><i class="icon icon--times icon--gray"></i></div>'))
            .append($('<h3>').html(String(linetype.name).charAt(0).toUpperCase() + String(linetype.name).slice(1)))
            .append($form = $('<form method="post">'));

        $line.on('click', function (e) { e.stopPropagation(); });
        $line.find('.lineclose').on('mouseup touchstart', function (e) { e.stopPropagation(); e.preventDefault(); deselectAllLines(); });

        $.each(linetype.fields, function () {
            let $label = $('<div class="form-row__label">')
                .html(this.label || this.name);

            let $field = window.fieldtypes.create(this);

            let $includeme, $value = $('<div class="form-row__value">')
                .append($includeme = $('<input class="includeme" type="checkbox">').data('for', this.name));

            if (this.readonly) {
                $includeme.prop('disabled', true).prop('checked', false);
                $value.addClass('noedit');
            }

            $value.append($field);

            let $row = $('<div class="form-row">')
                .attr('data-field-name', this.name)
                .data('type', this.type)
                .data('field', $field)
                .append($label, $value, $('<div style="clear: both">'));

            $form.append($row);
        });

        let $saveRow = $(
            '<div class="form-row">' +
                '<div class="form-row__label">&nbsp;</div>' +
                '<div class="form-row__value"></div>' +
                '<div style="clear: both"></div>'+
            '</div>'
        );

        let $saveButton = $('<button class="saveline button button--main" type="button">Save</button>')
            .on('click', saveLine);

        $saveRow
            .find('.form-row__value')
            .append($saveButton);

        $form.append($saveRow);

        return $line;
    };

    var refreshDisplayedLineEditor = function() {
        $lineContainer.empty();

        var $selected = $('.linerow.selected');

        $('.delete-selected').toggleClass('disabled', !$selected.length);
        $lineContainer.css('display', $selected.length && 'block' || 'none');

        if ($selected.length == 0) {
            return;
        }

        let lines = $selected.map(function () {
            return getLine($(this).data('type'), $(this).data('id'));
        });

        var linetypes = [...new Set($.map(lines, function (line) {
            return line.type;
        }))];

        if (linetypes.length > 1) { // multiple linetypes not supported for now
            return;
        }

        let linetype = window.linetypes[linetypes[0]];
        let $line = createLine(linetype);
        let bulk = $selected.length > 1;

        $line
            .data('bulk', bulk)
            .toggleClass('bulk', bulk)
            .find('.includeme')
            .toggle(bulk);

        $lineContainer.append($line);

        onResize();

        let generic_builder = {}, ids = [];

        $.each(linetype.fields, function(){
            generic_builder[this.name] = [];
        });

        $selected.each(function(index) {
            let $linerow = $(this);
            let line = lines[index];

            ids.push($linerow.data('id'))

            $.each(linetype.fields, function() {
                let name = this.name + '';

                if (name === 'id') {
                    return;
                }

                let value = line[name];

                if (generic_builder[name].length < 2 && generic_builder[name].indexOf(value) == -1) {
                     generic_builder[name].push(value);
                }
            });
        });

        generic_builder.id = [ids.join(',')];

        $.each(linetype.fields, function() {
            let value;

            if (generic_builder[this.name].length == 1 && (value = generic_builder[this.name][0]) || !bulk) {
                let $row = $line.find('[data-field-name="' + this.name + '"]');
                let $field = $row.data('field');

                $row.find('.includeme').prop('checked', !this.readonly);

                if (this.name === 'id' || this.readonly) {
                    $row.find('.includeme').hide();
                }

                window.fieldtypes.types[this.type].set($field, value);
            }
        });
    };

    let saveLine = function(e) {
        e.preventDefault();

        $(this).prop('disabled', true).addClass('disabled');

        let $line = $(this).closest('.line');
        let bulk = $line.data('bulk');
        let $form = $(this).closest('form');

        let line = {
            type: $line.data('type')
        };

        $line.find('[data-field-name]').each(function () {
            let $row = $(this);

            if (!$row.find('.includeme').prop('checked')) {
                return;
            }

            let $field = $row.data('field');
            let name = $row.attr('data-field-name');
            let type = $row.data('type');

            line[name] = window.fieldtypes.types[type].get($field);
        });

        if (typeof line.id === 'undefined') {
            line.id = '';
        }

        let ids = line.id.split(',');
        delete line.id;

        let lines = [];

        $.each(ids, function () {
            let _line = structuredClone(line);
            let id = this + '';

            if (id) {
                _line.id = id;
            }

            lines.push(ledger_unmap_line(_line));
        });

        var handleSave = function() {
            saveLines(lines, bulk, function(data, textStatus, request) {
                $('#new-vars-here').append($('<input name="version" value="' + request.getResponseHeader('X-Version') + '">'));
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
    };

    let getSelectionQuery = function($selected) {
        var deepids = $selected.map(function(){
            return $(this).data('type') + ':' + $(this).data('id');
        }).get();

        return 'deepid=' + deepids.join(',');
    }

    let getSelected = function() {
        return $('.linerow[data-id].selected');
    }

    $('.linerow').on('click', function (e) {
        modifySelection.apply(this, [
            e.ctrlKey || e.metaKey,
            e.shiftKey && $lastSelected[0] != this
        ]);

        if (!e.shiftKey || !$lastSelected) {
            $lastSelected = $(this);
        }

        refreshDisplayedLineEditor();
    });

    $('.trigger-add-line').on('click', function(event){
        event.stopPropagation();
        event.preventDefault();

        deselectAllLines();
        closeModals();

        let $plus = $(this);
        let linetype = window.linetypes[$plus.attr('data-type')];
        let $line = createLine(linetype);

        $.each(linetype.fields, function() {
            let $row = $line.find('[data-field-name="' + this.name + '"]');
            let value = $plus.attr('data-' + this.name);

            $row.find('.includeme').prop('checked', !this.readonly);

            if (value) {
                let $field = $row.data('field');

                window.fieldtypes.types[this.type].set($field, value);
            }
        });

        $line
            .data('bulk', false)
            .find('.includeme')
            .hide();

        $lineContainer.append($line);

        onResize();
    });

    $('.select-column input[type="checkbox"], .selectall').on('click', refreshDisplayedLineEditor);

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

            saveLines(lines, false, function(data, textStatus, request) {
                $('#new-vars-here').append($('<input name="version" value="' + request.getResponseHeader('X-Version') + '">'))
                cvsApply();
            });
        }
    });

    var onResize = function() {
        if (lineContainerBg === null) {
            lineContainerBg = $lineContainer.css('background');
        }

        if ($('.easy-table').length) {
            let wide = $(window).width() >= 1200;
            let margin = 30;
            let top = $('.easy-table').offset().top;
            let left = wide && ($('.easy-table').offset().left + $('.easy-table').outerWidth() + margin) || 0;
            let height = $(window).height();
            let width = $(window).width() - left;
            let background = !wide && lineContainerBg || 'none';

            $lineContainer.css({
                'box-sizing': 'border-box',
                top: 0,
                left: left,
                width: width + 'px',
                height: height + 'px',
                'padding-bottom': margin + 'px',
                'padding-top': top + 'px',
                'pointer-events': wide && 'none' || '',
                background: background
            });
        }
    }

    var resizeTimer = null;

    $(window).on('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 300); });

    onResize();

    window.ledger_map_line = function (line) { return line; }
    window.ledger_unmap_line = function (line) { return line; }
    $lineContainer.on('click', deselectAllLines);
    refreshDisplayedLineEditor();
})();

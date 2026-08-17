(function() {
    var $lastSelected = null;
    var $lineContainer = $('#line-container');
    var lineContainerBg = null;

    let alertException = function (data) {
        if (data.responseJSON.error) {
            alert(data.responseJSON.error);
        }

        let exception = data.responseJSON.exception ?? 'Unknown Exception';
        let message = data.responseJSON.message ?? 'No message was given';

        if (typeof data.responseJSON.private_message !== 'undefined') {
            exception = data.responseJSON.private_exception;
            message = data.responseJSON.private_message;
        }

        alert(exception + '\n\n' + message);
    };

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

        refreshDisplayedLineEditor();
    };

    var getLine = function (linetype, id) {
        for (var i = 0; i < window.lines.length; i++) {
            var line = window.lines[i];

            if (line.type == linetype && line.id == id) {
                if (typeof window.ledgerMapLine !== 'undefined') {
                    $.each(window.ledgerMapLine, function () {
                        this(line);
                    });
                }

                return line;
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

        $.ajax(window.ledgerBaseUrl + '/ajax/save', {
            method: 'post',
            contentType: false,
            processData: false,
            headers: headers,
            data: JSON.stringify(lines),
            success: success,
            error: alertException
        });
    };

    var modifySelection = function(append, range) {
        var $linerow = $(this);
        var deselecting = $linerow.hasClass('selected');
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

        if (!append) {
            $('.linerow').not($linerow).removeClass('selected');
        }

        $linerow.toggleClass('selected', !deselecting);
    };

    var createLine = function (linetype) {
        let $form, $line = $('<div class="line floatline edit-form"></div>')
            .data('type', linetype.name)
            .append($('<div class="lineclose"><i class="icon icon--times icon--gray"></i></div>'))
            .append($('<a class="delete-selected" href="#" style="display: none"><i class="icon icon--gray icon--bin"></i></a>'))
            .append($('<h3>').html(String(linetype.name).charAt(0).toUpperCase() + String(linetype.name).slice(1)))
            .append($form = $('<form method="post">'));

        $line.find('.delete-selected').on('click', deleteClicked);

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
            '<div class="form-row line-buttons">' +
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
        $('#sums').remove();

        var $selected = $('.linerow.selected');

        $lineContainer.css('display', $selected.length && 'block' || 'none');

        if ($selected.length) {
            let lines = $selected.map(function () {
                return getLine($(this).data('type'), $(this).data('id'));
            });

            var linetypes = [...new Set($.map(lines, function (line) {
                return line.type;
            }))];

            let sums = {};

            $selected.each(function(index) {
                $.each(sum_fields, function () {
                    if (typeof(sums[this]) == 'undefined') {
                        sums[this] = 0;
                    }

                    sums[this] = parseFloat((sums[this] + parseFloat(lines[index][this] ?? '0')).toFixed(2));
                });
            });

            let $line;

            if (linetypes.length == 1) { // multiple linetypes not supported for now
                let linetype = window.linetypes[linetypes[0]];
                $line = createLine(linetype);
                let bulk = $selected.length > 1;

                $line.find('.delete-selected').show();
                $line.find('.includeme').toggle(bulk);

                $line
                    .data('bulk', bulk)
                    .toggleClass('bulk', bulk);

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
            }

            if (Object.keys(sums).length && $selected.length > 1) {
                let $sums = $('<div id="sums"></div>')
                    .on('click', function (e) {
                        e.stopPropagation();
                    })
                    .css({
                        width: Math.min(typeof $line === 'undefined' && 320 || $line.outerWidth()) + 'px',
                    });

                $.each(sum_fields, function () {
                    $sums.append('<div class="form-row"><div class="form-row__label">Σ&nbsp;' + this + '</div><div class="form-row__value"><input class="field value disabled" disabled="disabled" type="text" autocomplete="off" value="' + sums[this] + '"></div><div style="clear: both"></div></div>');
                });

                if (typeof $line !== 'undefined') {
                    $lineContainer.append($('<br><br>'));
                }

                $lineContainer.append($sums);
            }
        } else {
            $lastSelected = null;
            $('.linerow').removeClass('last-selected');
        }

        if (typeof window.postRefreshLineEditor !== 'undefined') {
            $.each(window.postRefreshLineEditor, function () {
                this();
            });
        }
    };

    let saveLine = function(e) {
        e.preventDefault();

        $(this).prop('disabled', true).addClass('disabled');

        let $line = $(this).closest('.line');
        let bulk = $line.data('bulk');
        let $form = $(this).closest('form');

        let lineTemplate = {
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

            lineTemplate[name] = window.fieldtypes.types[type].get($field);
        });

        if (typeof lineTemplate.id === 'undefined') {
            lineTemplate.id = '';
        }

        let ids = lineTemplate.id.split(',');
        delete lineTemplate.id;

        let lines = [];

        $.each(ids, function () {
            let line = structuredClone(lineTemplate);
            let id = this + '';

            if (id) {
                line.id = id;
            }

            if (typeof window.ledgerUnmapLine !== 'undefined') {
                $.each(window.ledgerUnmapLine, function () {
                    line = this(line);
                });
            }

            lines.push(line);
        });

        var handleSave = function() {
            saveLines(lines, bulk, function(data, textStatus, request) {
                if (typeof window.ledgerPostSave !== 'undefined') {
                    $.each(window.ledgerPostSave, function () {
                        this(data, textStatus, request);
                    });
                }

                window.contextVariableSets.version = request.getResponseHeader('X-Version');
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
            $('.linerow').not($lastSelected).removeClass('last-selected');
            $lastSelected.addClass('last-selected');
        }

        refreshDisplayedLineEditor();

        if (e.shiftKey || e.ctrlKey || e.metaKey) {
            window.getSelection().removeAllRanges();
        }
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

        $lineContainer.css('display', 'block');
        onResize();
    });

    $('.select-column input[type="checkbox"], .selectall').on('click', refreshDisplayedLineEditor);

    let deleteClicked = function(e) {
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
                window.contextVariableSets.version = request.getResponseHeader('X-Version');
                cvsApply();
            });
        }
    };

    var onResize = function() {
        if (lineContainerBg === null) {
            lineContainerBg = $lineContainer.css('background');
        }

        if ($('.easy-table').length) {
            let wide = $(window).width() >= 1200;
            let verticalMargin = 43;
            let horizontalMargin = 20;
            let top = wide && $('.easy-table').offset().top || verticalMargin;
            let left = wide && ($('.easy-table').offset().left + $('.easy-table').outerWidth() + horizontalMargin) || 0;
            let height = $(window).height();
            let width = $(window).width() - left;
            let background = !wide && lineContainerBg || 'none';

            $lineContainer.css({
                'box-sizing': 'border-box',
                top: 0,
                left: left,
                width: width + 'px',
                height: height + 'px',
                'padding-bottom': verticalMargin + 'px',
                'padding-top': top + 'px',
                'pointer-events': wide && 'none' || '',
                background: background
            });
        }
    }

    let rawlineSave = function(e) {
        e.preventDefault();
        var data;

        try {
            data = JSON.parse($(this).closest('form').find('[name="raw"]').val());
        } catch(e) {
            alert(e);

            return;
        }

        if (data.constructor !== Array) {
            if (typeof data === 'object') {
                data = [data];
            } else {
                alert('Please provide an object or array of objects');

                return;
            }
        }

        $.ajax(window.ledgerBaseUrl + '/ajax/save', {
            method: 'post',
            contentType: false,
            processData: false,
            data: JSON.stringify(data),
            headers: {'X-Base-Version': base_version},
            success: function(data, textStatus, request) {
                window.contextVariableSets.version = request.getResponseHeader('X-Version');
                window.contextVariableSets.raw__value = 0;
                cvsApply();
            },
            error: alertException
        });
    };

    var resizeTimer = null;

    $(window).on('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(onResize, 300); });

    window.ledgerRefreshDisplayedLineEditor = refreshDisplayedLineEditor;
    window.ledgerOnResize = onResize;

    $('.savelineraw').on('click', rawlineSave);

    $lineContainer.on('click', deselectAllLines);
})();

(function() {
    window.fieldtypes.types.string = {
        create: function(spec) {
            let $field;

            if (typeof spec.options !== 'undefined') {
                $field = $('<select>')
                    .attr('name', spec.name);

                if (spec.readonly) {
                    $field.prop('disabled', true);
                }

                if (spec.constrained || spec.options.length > 1) {
                    $field.append($('<option>'));
                }

                $.each(spec.options, function () {
                    let $option = $('<option>')
                        .attr('value', this)
                        .html(this);

                    $field.append($option);
                });

                if (!spec.constrained) {
                    let $adhoc = $('<span class="button adhoc-toggle noedit-invisible">&hellip;</span>')
                        .on('click', function(e) {
                            e.preventDefault();

                            let adhocvalue = prompt("New value");

                            if (adhocvalue) {
                                let $option = $('<option>' + adhocvalue + '</option>');

                                $option.insertBefore($field.children().first());
                                $field.val(adhocvalue);
                                $field.change();
                            }
                        });
                    
                    let $wrapper = $('<span style="white-space: nowrap">');

                    $wrapper.append($field, $adhoc);

                    return $wrapper;
                }
            } else {
                let multiline = !!spec.multiline;

                $field = multiline ? $('<textarea style="height: 10em">') : $('<input>');

                $field.attr('name', spec.name);

                if (!multiline) {
                    $field
                        .attr('type', 'text')
                        .attr('autocomplete', 'off');
                }

                if (spec.readonly) {
                    $field.prop('disabled', true);
                }
            }

            return $field;
        },
        set: function ($field, value) {
            if (!$field.is('select, input, textarea')) {
                $field = $field.find('select, input, textarea').first();
            }

            if ($field.is('select') && !$field.find('option[value="' + value + '"]').length) {
                $field.prepend($('<option>').html(value).prop('value', value));
            }

            $field.val(value);
        },
        get: function ($field) {
            if (!$field.is('select, input, textarea')) {
                $field = $field.find('select, input, textarea').first();
            }

            return $field.val();
        }
    };
})();
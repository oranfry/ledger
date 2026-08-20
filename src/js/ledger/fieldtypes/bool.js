(function() {
    window.fieldtypes.types.bool = {
        create: function(spec) {
            let $field = $('<input type="checkbox" class="field value">')
                .attr('name', spec.name);

            if (spec.readonly) {
                $field.prop('disabled', true);
            }

            return $field;
        },
        set: function ($field, value) {
            $field.prop('checked', value);
        },
        get: function ($field) {
            return $field.prop('checked');
        }
    };
})();
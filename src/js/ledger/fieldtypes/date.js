(function() {
    window.fieldtypes.types.date = {
        create: function(spec) {
            let $wrapper = $('<span style="white-space: nowrap">');

            let $field = $('<input class="field value" type="text" style="width: 8em">')
                .attr('name', spec.name);

            if (spec.readonly) {
                $field.prop('disabled', true);
            }

            let $fromToday = $('<span class="button noedit-invisible">&bull;</span>').on('click', function (e) {
                e.preventDefault();
                let today = new Date();

                $field.val(today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0'));
            });

            $wrapper.append($field, $fromToday);
            return $wrapper;
        },
        set: function ($field, value) {
            $field.find('input').val(value);
        },
        get: function ($field) {
            return $field.find('input').val();
        }
    };
})();
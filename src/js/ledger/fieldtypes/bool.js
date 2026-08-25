(function() {
    window.fieldtypes.types.bool = class {
        constructor(spec) {
            this.$field = $('<input type="checkbox" class="field value">')
                .attr('name', spec.name);

            if (spec.readonly) {
                this.$field.prop('disabled', true);
            }
        }

        field() {
            return this.$field;
        }

        get() {
            return this.$field.prop('checked');
        }

        set(value) {
            this.$field.prop('checked', !!value);
        }
    };
})();
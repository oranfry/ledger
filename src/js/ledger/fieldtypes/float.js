(function() {
    let klass = class {
        constructor(spec) {
            let that = this;
            let step = 1 / Math.pow(10, spec.dp ?? 0);

            this.$field = $('<input class="field value" type="number" autocomplete="off" style="width: 8em">')
                .attr('name', spec.name)
                .attr('step', step);

            if (spec.readonly) {
                this.$field.prop('disabled', true);
            }

            this._buttons = [{
                content: $(document.createTextNode('±')),
                action: function () {
                    that.$field.val(0 - that.$field.val());
                }
            }];
        }

        buttons() {
            return this._buttons;
        }

        field() {
            return this.$field;
        }

        set(value) {
            this.$field.val(value);
        }

        get() {
            return this.$field.val();
        }
    };

    window.fieldtypes.types.float = klass;
    window.fieldtypes.types.number = klass;
})();
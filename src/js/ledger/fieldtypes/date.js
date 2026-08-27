(function() {
    window.fieldtypes.types.date = class {
        constructor(spec) {
            let that = this;

            this.$field = $('<input class="field value" type="text" style="width: 8em">')
                .attr('name', spec.name);

            if (spec.readonly) {
                this.$field.prop('disabled', true);
            }

            this._buttons = [{
                content: $(document.createTextNode('●')),
                action: function () {
                    let today = new Date();

                    that.$field.val(
                        today.getFullYear()
                        + '-'
                        + String(today.getMonth() + 1).padStart(2, '0')
                        + '-'
                        + String(today.getDate()).padStart(2, '0')
                    );
                }
            }];
        }

        buttons() {
            return this._buttons;
        }   

        field() {
            return this.$field;
        }

        get () {
            return this.$field.val();
        }

        set(value) {
            this.$field.val(value);
        }
    };
})();
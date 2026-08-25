(function() {
    window.fieldtypes.types.string = class {
        constructor(spec) {
            this.spec = spec;
            this._buttons = [];
            this.downloadButton = null;
            this.adhocButton = null;

            let that = this;

            if (typeof spec.options !== 'undefined') {
                this.$field = $('<select>')
                    .attr('name', spec.name);

                if (spec.readonly) {
                    this.$field.prop('disabled', true);
                }

                if (spec.constrained || spec.options.length > 1) {
                    this.$field.append($('<option>'));
                }

                $.each(spec.options, function () {
                    let $option = $('<option>')
                        .attr('value', this)
                        .html(this);

                    that.$field.append($option);
                });

                if (!this.spec.constrained) {
                    this.adhocButton = {
                        content: $(document.createTextNode('⚙')),
                        action: function() {
                            let adhocvalue = prompt("New value");

                            if (adhocvalue) {
                                let $option = $('<option>' + adhocvalue + '</option>')
                                    .val(adhocvalue);

                                $option.insertBefore(that.$field.children().first());

                                that.$field
                                    .val(adhocvalue)
                                    .change();
                            }
                        }
                    };

                    this._buttons.push(this.adhocButton);
                }
            } else {
                if (spec.multiline) {
                    this.$field = $('<textarea style="height: 10em">')
                        .attr('type', 'text')
                        .attr('autocomplete', 'off');
                } else {
                    this.$field = $('<input>');
                }

                this.$field.attr('name', spec.name);

                if (spec.readonly) {
                    this.$field.prop('disabled', true);
                }
            }

            if (spec.downloadable) {
                this.downloadButton = {
                    content: $(
                        spec.download_icon
                            ? '<i class="icon icon--gray icon--' + spec.download_icon + '">'
                            : document.createTextNode('⬇')
                    ),
                    action: function () {
                        let value = that.$field.val();
                        let $line = that.$field.closest('.line');
                        let linetype = $line.data('type');
                        let id = $line.find('[name="id"]').val();

                        if (!id.includes(',')) {
                            const link = document.createElement('a');

                            link.href = window.ledgerBaseUrl + '/-download/' + linetype + '/' + id + '/' + that.spec.name;
                            link.toggleAttribute('download', true);
                            link.style.display = 'none';
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        }
                    }
                };

                this._buttons.push(this.downloadButton);
            }
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
            if (
                this.$field.is('select')
                && !this.$field.find('option[value="' + value + '"]').length
            ) {
                this.$field.prepend($('<option>').html(value).prop('value', value));
            }

            this.$field.val(value);
        }
    };
})();
(function() {
    "use strict";

    window.Ledger = class Ledger {
        constructor(containerQuery, apiBase) {
            let container = document.querySelector(containerQuery);

            if (!container) {
                console.error('Could not ledgerize: container not found');
            }

            this.container = container;
            this.$container = $(container);
            this.apiBase = apiBase;
            this.config = null;
            this.lines = null;
            this.booted = false;
            this.$table = false;
        }

        static ledgerize(containerQuery, apiBase) {
            let ledger = new this(containerQuery, apiBase);

            ledger.load()
        }

        boot() {
            if (this.config === null || this.lines === null) {
                return;
            }

            this.setupTable();
            this.renderLines();

            this.booted = true;
        }

        setupTable() {
            this.$table = $('<table class="easy-table">');
            this.$container.append(this.$table);

            let $thead = $('<thead>');
            let $tr = $('<tr>');

            this.$table.append($thead.append($tr));

            $.each(this.config.fields, function (index, field) {
                let $th = $('<th>');

                if (field.type === 'number') {
                    $th.addClass('right');
                }

                if (!field.supress_header && field.type !== 'icon') {
                    $th.html(field.name);
                }

                $tr.append($th);
            });

            this.$table.append(this.$tbody = $('<tbody>'));
        }

        renderLines() {
            let dateinfoField;

            if (this.config.dateinfo !== null && this.config.dateinfo.field) {
                dateinfoField = this.config.dateinfo.field;
            }

            for (let i = 0; i < this.lines.length; i++) {
                let line = this.lines[i];
                let $tr = $('<tr class="linerow">');

                $tr.attr('data-type', line.type);

                if (line.id) {
                    $tr.attr('data-id', line.id);
                }

                if (dateinfoField && line[dateinfoField]) {
                    $tr.attr('data-group', line[dateinfoField]);
                }

                if (line.broken) {
                    $tr.addClass('broken');
                }

                $.each(this.config.fields, function (fi, field) {
                    let value = line[field.name];
                    let $td = $('<td>');

                    $td.attr('data-name', field.name);
                    $td.attr('data-value', value);

                    if (field.type === 'number') {
                        $td.addClass('right');
                    }

                    $td.append('<div class="select-column"><input style="display: none" type="checkbox"></div>');

                    if (field.type === 'icon') {
                        $td.append($('<i class="icon icon--gray icon--' + value + '"></i>'));
                    } else if (field.type === 'color') {
                        $td.append($('<span style="display: inline-block; height: 1em; width: 1em; background-color: #' + value + '">&nbsp;</span>'));
                    } else if (field.type === 'number' && typeof field.dp !== 'undefined') {
                        $td.html(value.toFixed(field.dp));
                    } else if (field.type == 'number') {
                        $td.html(value ?? '0');
                    } else {
                        $td.html(value);
                    }

                    $tr.append($td);
               });

                this.$tbody.append($tr);

            }
        }

        load() {
            let ledger = this;

            // get ledger config

            $.ajax(this.apiBase + '/config', {
                method: 'get',
                success: function(data, textStatus, request) {
                    ledger.config = data;
                    ledger.boot();
                },
                error: function (data) {
                    alert(data.responseJSON.error);
                }
            });

            // get lines

            $.ajax(this.apiBase + '/lines', {
                method: 'get',
                success: function(data, textStatus, request) {
                    ledger.lines = data;
                    ledger.boot();
                },
                error: function (data) {
                    alert(data.responseJSON.error);
                }
            });
        }
    }
})();

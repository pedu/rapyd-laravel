var MassCheckbox = new function() {

        this.init = function() {
            $('.rpd-datagrid [data-masscheckbox-head]').on('change', this.onHeadCheckbox.bind(this));
            $('.rpd-datagrid [data-masscheckbox-button]').off('click.formSend').on('click.formSend', this.onFormSend.bind(this));
            $('.rpd-datagrid').find('input[data-masscheckbox]').on('change', this.toggleButtonsVisibility.bind(this));
            this.toggleButtonsVisibility();
        }


        this.getButtons = function() {
            return $('.rpd-datagrid [data-masscheckbox-button]');
        }


        this.onHeadCheckbox = function (e) {
            let $this = $(e.target);
            let checked = $this.is(':checked');
            $this.closest('table').find('input[data-masscheckbox]').prop('checked', checked);

            this.toggleButtonsVisibility();
        }


        this.toggleButtonsVisibility = function() {
            let $this = this;
            $('.rpd-datagrid').each(function (i, grid) {
                let c = $(grid).find('input[data-masscheckbox]:checked').length;
                if (c > 0) {
                    $this.getButtons().attr('disabled', false);
                } else {
                    $this.getButtons().attr('disabled', true);
                }
            });
        }


        this.onFormSend = function(e) {
            let $this = $(e.target);
            let data = this.getCheckboxData($this);
            let ask = $this.data('ask');

            if (data.length > 0) {
                let url = $this.data('url');
                $this.closest('form').attr('action', url);
                $this.closest('form').append('<input type="hidden" name="data" value="' + JSON.stringify(data) + '"/>');
            } else {
                window.alert('no data to send');
            }
        }

        this.getCheckboxData = function(button) {
            let checkboxes = button.closest('.rpd-datagrid').find('input[data-masscheckbox]:checked');
            let data = [];
            checkboxes.each(function (i, checkbox) {
                data.push($(checkbox).data('id'));
            });

            return data;
        }


        this.init();
    }


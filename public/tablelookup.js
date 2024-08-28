var TableLookupWizard = (function() {
    "use strict";

    return function(name, options) {

        var timer, widget, href, separator_row, sortables;

        function checked(event) {
            var parent = event.target.getParent('tr');

            if (event.target.checked) {
                parent.removeClass('found')
                    .addClass('selected')
                    .inject(widget.getElement('tr.search'), 'before');

                if (options && options.enableSorting) {
                    parent.getElement('.drag-handle').setStyle('display', 'inline');
                    sortables.addItems(parent);
                }

                window.fireEvent('tableLookupWizard_postModified', { name: name, operation: 'selected', element: parent });

            } else {
                parent.destroy();
                widget.send(href);

                if (options && options.enableSorting) {
                    sortables.removeItems(parent);
                }

                window.fireEvent('tableLookupWizard_postModified', { name: name, operation: 'unselected', element: parent });
            }
        }

        function selected(event) {
            event.target.getParent('tr')
                .removeClass('found')
                .inject(separator_row, 'before');
            event.target.getParent('tr').getAllPrevious().destroy();
            widget.send(href);
        }

        function initSortables() {
            sortables = new Sortables(widget.getElements('tbody'), {
                constrain: true,
                clone: false,
                handle: '.drag-handle'
            });

            // Override getDroppables() so it only takes the row.selected as droppables, not the whole table rows
            sortables.getDroppables = function() {
                return widget.getElements('tbody tr.row.selected');
            };

            // Remove the search and reset and search rows from the sortables otherwise they can be dragged
            sortables.removeItems(widget.getElements('tbody tr.search'), widget.getElements('tbody tr.reset'));
        }

        widget = document.id('ctrl_' + name);
        separator_row = widget.getElement('tr.reset, tr.search');
        href = window.location.href + '&tableLookupWizard=' + name;

        if (options && options.enableSorting) {
            initSortables();
        }

        widget.getElements('tbody tr').forEach(function(row) {

            var check = row.getElement('input[type=checkbox]') || row.getElement('input[type=radio]');

            if (check) {
                check.addEvent('change', function(event) {

                    // Do not destroy reset element (if selected)
                    if (event.target.getParent('tr').hasClass('reset')) {
                        event.target.getParent('tr').getAllPrevious().destroy();
                    } else {
                        event.target.getParent('tr').destroy();
                        window.fireEvent('tableLookupWizard_postModified', { name: name, operation: 'unselected', element: event.target.getParent('tr') });
                    }

                    widget.send(href);
                });
            }
        });

        widget.set('send', {
            method: 'get',
            link: 'cancel',
            onSuccess: function(text) {
                var rows;

                try {
                    text = JSON.decode(text).content;
                } catch (error){}

                widget.getElements('.search input.tl_text').removeClass('loading');
                widget.getElements('tr.found').destroy();

                rows = Elements.from(text, false);
                widget.getElement('tbody').adopt(rows);
                rows.forEach(function(row) {
                    if (row.getElement('input[type=checkbox]'))
                        row.getElement('input[type=checkbox]').addEvent('click', checked);

                    if (row.getElement('input[type=radio]'))
                        row.getElement('input[type=radio]').addEvent('click', selected);

                });

                window.fireEvent('tableLookupWizard_postSearch', { name: name, result: text });
            }
        }).addEvent('keyup', function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                widget.getElement('.search input.tl_text').addClass('loading');
                widget.send(href);
            }, 300);
        });
    };
})();

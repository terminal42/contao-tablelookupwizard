/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2013 Isotope eCommerce Workgroup
 *
 * @package    TableLookupWizard
 * @link       http://www.isotopeecommerce.com
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */


var TableLookupWizard = (function() {
"use strict";

    var timer, widget, href, separator_row;

    var checked = function(event)
    {
        if (event.target.checked) {
            event.target.getParent('tr').removeClass('found').inject(widget.getElement('tr.search'), 'before');
        } else {
            event.target.getParent('tr').destroy();
            widget.send(href);
        }
    };

    var selected = function(event)
    {
        event.target.getParent('tr').removeClass('found').inject(separator_row, 'before');
        event.target.getParent('tr').getAllPrevious().destroy();
        widget.send(href);
    };

    return function(name) {

        widget = document.id('ctrl_'+name);
        separator_row = widget.getElement('tr.reset, tr.search');
        href = window.location.href + '&tableLookupWizard=' + name;

        widget.getElement('.jserror').setStyle('display', 'none');
        widget.getElement('.search').setStyle('display', ((Browser.ie && Browser.version < 6) ? 'block' : 'table-row'));

        widget.getElements('tbody tr').each(function(row) {

            var check = row.getElement('input[type=checkbox]') || row.getElement('input[type=radio]');

            if (check) {
                check.addEvent('change', function(event) {

                    // Do not destroy reset element (if selected)
                    if (event.target.getParent('tr').hasClass('reset')) {
                        event.target.getParent('tr').getAllPrevious().destroy();
                    } else {
                        event.target.getParent('tr').destroy();
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

                widget.getElements('.search input.tl_text').setStyle('background-image', 'none');
                widget.getElements('tr.found').destroy();

                rows = Elements.from(text, false);
                widget.getElement('tbody').adopt(rows);
                rows.each(function(row) {
                    if (row.getElement('input[type=checkbox]'))
                        row.getElement('input[type=checkbox]').addEvent('click', checked);

                    if (row.getElement('input[type=radio]'))
                        row.getElement('input[type=radio]').addEvent('click', selected);

                });
            }
        }).addEvent('keyup', function() {
        	// Fix problem with multiple tableList fields
	        widget = document.id('ctrl_'+name);
	        separator_row = widget.getElement('tr.reset, tr.search');
	        href = window.location.href + '&tableLookupWizard=' + name;

            clearTimeout(timer);
            timer = setTimeout(function() {
                widget.getElement('.search input.tl_text').setStyle('background-image', 'url(system/modules/tablelookupwizard/assets/loading.gif)');
                widget.send(href);
            }, 300);
        });
    };
})();

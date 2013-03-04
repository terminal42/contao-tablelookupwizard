/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2013 Isotope eCommerce Workgroup
 *
 * @package    TableLookupWizard
 * @link       http://www.isotopeecommerce.com
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */


var TableLookupWizard = new Class(
{
    Binds: ['send', 'show', 'checked', 'selected'],

    initialize: function(name)
    {
        var self = this;
        this.element = name;
        this.separator_row = document.getElement('#ctrl_'+this.element+' tr.reset, #ctrl_'+this.element+' tr.search');

        $$(('#ctrl_'+name+' .jserror')).setStyle('display', 'none');
        $$(('#ctrl_'+name+' .search')).setStyle('display', (((Browser.ie && Browser.version < 8) || (Browser.Engine.trident && Browser.Engine.version < 6)) ? 'block' : 'table-row'));

        $$(('#ctrl_'+name+' tbody tr')).each( function(row)
        {
            var check = row.getElement('input[type=checkbox]') ? row.getElement('input[type=checkbox]') : row.getElement('input[type=radio]');
            if (check)
            {
                check.addEvent('change', function(event)
                {
                    // Do not destroy reset element (if selected)
                    event.target.getParent('tr').hasClass('reset') ? event.target.getParent('tr').getAllPrevious().destroy() : event.target.getParent('tr').destroy();

                    $(('ctrl_'+name)).send((window.location.href + '&tableLookupWizard=' + self.element));
                });
            }
        });

        $(('ctrl_'+name)).set('send',
        {
            method: 'get',
            link: 'cancel',
            onSuccess: this.show
        }).addEvent('keyup', this.send);
    },

    send: function()
    {
        clearTimeout(this.timer);
        this.timer = setTimeout( function() {
            $$(('#ctrl_'+this.element+' .search input.tl_text')).setStyle('background-image', 'url(system/modules/tablelookupwizard/html/loading.gif)');
            $(('ctrl_'+this.element)).send((window.location.href + '&tableLookupWizard=' + this.element));
        }.bind(this), 300);
    },

    show: function(text)
    {
        var json;
        var text;

        try
        {
            json = JSON.decode(text);

            // Automatically set the new request token
            if (json.token && AjaxRequest && AjaxRequest.updateTokens)
            {
                AjaxRequest.updateTokens(json.token);
            }

            text = json.content;
        }
        catch (error){}

        $$(('#ctrl_'+this.element+' .search input.tl_text')).setStyle('background-image', 'none');
        $$(('#ctrl_'+this.element+' tr.found')).each( function(el)
        {
            el.destroy();
        });

        var rows = Elements.from(text, false);
        $$(('#ctrl_'+this.element+' tbody')).adopt(rows);
        rows.each( function(row)
        {
            if (row.getElement('input[type=checkbox]'))
                row.getElement('input[type=checkbox]').addEvent('click', this.checked);

            if (row.getElement('input[type=radio]'))
                row.getElement('input[type=radio]').addEvent('click', this.selected);

        }.bind(this));
    },

    checked: function(event)
    {
        if (event.target.checked)
        {
            event.target.getParent('tr').removeClass('found').inject($$(('#ctrl_'+this.element+' tr.search'))[0], 'before');
        }
        else
        {
            event.target.getParent('tr').destroy();
            $(('ctrl_'+this.element)).send((window.location.href + '&tableLookupWizard=' + this.element));
        }
    },

    selected: function(event)
    {
        event.target.getParent('tr').removeClass('found').inject(this.separator_row, 'before');
        event.target.getParent('tr').getAllPrevious().destroy();
        $(('ctrl_'+this.element)).send((window.location.href + '&tableLookupWizard=' + this.element));
    }
});

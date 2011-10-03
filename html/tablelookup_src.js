/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Isotope eCommerce Workgroup 2009-2011
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id: $
 */


var TableLookupWizard = new Class(
{
	Binds: ['send', 'show', 'checked', 'selected'],

	initialize: function(name)
	{
		this.element = name;
		this.separator_row = document.getElement('#ctrl_'+this.element+' tr.reset, #ctrl_'+this.element+' tr.search');

		$$(('#ctrl_'+name+' .jserror')).setStyle('display', 'none');
		$$(('#ctrl_'+name+' .search')).setStyle('display', 'table-row');

		$$(('#ctrl_'+name+' tbody tr')).each( function(row)
		{
			var check = row.getElement('input[type=checkbox]') ? row.getElement('input[type=checkbox]') : row.getElement('input[type=radio]');
			if (check)
			{
				check.addEvent('change', function(event)
				{
					// Do not destroy reset element (if selected)
					event.target.getParent('tr').hasClass('reset') ? event.target.getParent('tr').getAllPrevious().destroy() : event.target.getParent('tr').destroy();

					$(('ctrl_'+name)).send();
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

		try
		{
			json = JSON.decode(text);

			// Automatically set the new request token
			if (json.token)
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
			$(('ctrl_'+this.element)).send();
		}
	},

	selected: function(event)
	{
		event.target.getParent('tr').removeClass('found').inject(this.separator_row, 'before');
		event.target.getParent('tr').getAllPrevious().destroy();
		$(('ctrl_'+this.element)).send();
	}
});


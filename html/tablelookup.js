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
 * @copyright  Isotope eCommerce Workgroup 2009-2012
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
var TableLookupWizard=new Class({Binds:["send","show","checked","selected"],initialize:function(a){var b=this;this.element=a,this.separator_row=document.getElement("#ctrl_"+this.element+" tr.reset, #ctrl_"+this.element+" tr.search"),$$("#ctrl_"+a+" .jserror").setStyle("display","none"),$$("#ctrl_"+a+" .search").setStyle("display",Browser.ie&&8>Browser.version||Browser.ie&&6>Browser.version?"block":"table-row"),$$("#ctrl_"+a+" tbody tr").each(function(c){var d=c.getElement("input[type=checkbox]")?c.getElement("input[type=checkbox]"):c.getElement("input[type=radio]");d&&d.addEvent("change",function(c){c.target.getParent("tr").hasClass("reset")?c.target.getParent("tr").getAllPrevious().destroy():c.target.getParent("tr").destroy(),$("ctrl_"+a).send(window.location.href+"&tableLookupWizard="+b.element)})}),$("ctrl_"+a).set("send",{method:"get",link:"cancel",onSuccess:this.show}).addEvent("keyup",this.send)},send:function(){clearTimeout(this.timer),this.timer=setTimeout(function(){$$("#ctrl_"+this.element+" .search input.tl_text").setStyle("background-image","url(system/modules/tablelookupwizard/html/loading.gif)"),$("ctrl_"+this.element).send(window.location.href+"&tableLookupWizard="+this.element)}.bind(this),300)},show:function(a){var b,a;try{b=JSON.decode(a),b.token&&AjaxRequest&&AjaxRequest.updateTokens&&AjaxRequest.updateTokens(b.token),a=b.content}catch(c){}$$("#ctrl_"+this.element+" .search input.tl_text").setStyle("background-image","none"),$$("#ctrl_"+this.element+" tr.found").each(function(a){a.destroy()});var d=Elements.from(a,!1);$$("#ctrl_"+this.element+" tbody").adopt(d),d.each(function(a){a.getElement("input[type=checkbox]")&&a.getElement("input[type=checkbox]").addEvent("click",this.checked),a.getElement("input[type=radio]")&&a.getElement("input[type=radio]").addEvent("click",this.selected)}.bind(this))},checked:function(a){a.target.checked?a.target.getParent("tr").removeClass("found").inject($$("#ctrl_"+this.element+" tr.search")[0],"before"):(a.target.getParent("tr").destroy(),$("ctrl_"+this.element).send(window.location.href+"&tableLookupWizard="+this.element))},selected:function(a){a.target.getParent("tr").removeClass("found").inject(this.separator_row,"before"),a.target.getParent("tr").getAllPrevious().destroy(),$("ctrl_"+this.element).send(window.location.href+"&tableLookupWizard="+this.element)}});
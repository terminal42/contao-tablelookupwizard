var TableLookupWizard=new Class({Binds:["send","show","checked","selected"],initialize:function(b){var a=this;this.element=b;this.separator_row=document.getElement("#ctrl_"+this.element+" tr.reset, #ctrl_"+this.element+" tr.search");$$(("#ctrl_"+b+" .jserror")).setStyle("display","none");$$(("#ctrl_"+b+" .search")).setStyle("display",(((Browser.ie&&Browser.version<8)||(Browser.Engine.trident&&Browser.Engine.version<6))?"block":"table-row"));$$(("#ctrl_"+b+" tbody tr")).each(function(d){var c=d.getElement("input[type=checkbox]")?d.getElement("input[type=checkbox]"):d.getElement("input[type=radio]");if(c){c.addEvent("change",function(e){e.target.getParent("tr").hasClass("reset")?e.target.getParent("tr").getAllPrevious().destroy():e.target.getParent("tr").destroy();$(("ctrl_"+b)).send((window.location.href+"&tableLookupWizard="+a.element))})}});$(("ctrl_"+b)).set("send",{method:"get",link:"cancel",onSuccess:this.show}).addEvent("keyup",this.send)},send:function(){clearTimeout(this.timer);this.timer=setTimeout(function(){$$(("#ctrl_"+this.element+" .search input.tl_text")).setStyle("background-image","url(system/modules/tablelookupwizard/html/loading.gif)");$(("ctrl_"+this.element)).send((window.location.href+"&tableLookupWizard="+this.element))}.bind(this),300)},show:function(d){var b;var d;try{b=JSON.decode(d);if(b.token&&AjaxRequest&&AjaxRequest.updateTokens){AjaxRequest.updateTokens(b.token)}d=b.content}catch(a){}$$(("#ctrl_"+this.element+" .search input.tl_text")).setStyle("background-image","none");$$(("#ctrl_"+this.element+" tr.found")).each(function(e){e.destroy()});var c=Elements.from(d,false);$$(("#ctrl_"+this.element+" tbody")).adopt(c);c.each(function(e){if(e.getElement("input[type=checkbox]")){e.getElement("input[type=checkbox]").addEvent("click",this.checked)}if(e.getElement("input[type=radio]")){e.getElement("input[type=radio]").addEvent("click",this.selected)}}.bind(this))},checked:function(a){if(a.target.checked){a.target.getParent("tr").removeClass("found").inject($$(("#ctrl_"+this.element+" tr.search"))[0],"before")}else{a.target.getParent("tr").destroy();$(("ctrl_"+this.element)).send((window.location.href+"&tableLookupWizard="+this.element))}},selected:function(a){a.target.getParent("tr").removeClass("found").inject(this.separator_row,"before");a.target.getParent("tr").getAllPrevious().destroy();$(("ctrl_"+this.element)).send((window.location.href+"&tableLookupWizard="+this.element))}});
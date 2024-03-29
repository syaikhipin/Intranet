function IntranetUsers(name, multiple, bSubordinateOnly) {
	this.name = name;
	this.multiple = multiple;
	this.arSelected = [];
	this.bSubordinateOnly = bSubordinateOnly;
	this.ajaxUrl = '';
}

IntranetUsers.arEmployees = {
	'group' : {}
};
IntranetUsers.arEmployeesData = {};
IntranetUsers.ajaxUrl = '';

IntranetUsers.prototype.loadGroup = function(groupId)
{
	var obSection = BX(this.name + '_group_section_' + groupId);
	function __onLoadEmployees(data)
	{
		IntranetUsers.arEmployees['group'][groupId] = data;
		this.show(groupId, data, 'g');
	}

	groupId = parseInt(groupId);
	if (IntranetUsers.arEmployees['group'][groupId] != null)
	{
		this.show(groupId, IntranetUsers.arEmployees['group'][groupId], 'g');
	}
	else
	{
		var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&GROUP_ID=' + groupId;
		BX.ajax.loadJSON(url, BX.proxy(__onLoadEmployees, this));
	}

	BX.toggleClass(obSection, "company-department-opened");
	BX.toggleClass(BX(this.name + '_gchildren_' + groupId), "company-department-children-opened");
}


IntranetUsers.prototype.load = function(sectionID, bShowOnly, bScrollToSection)
{
	function __onLoadEmployees(data)
	{
		IntranetUsers.arEmployees[sectionID] = data;
		this.show(sectionID);
	}

	if (null == bShowOnly) bShowOnly = false;
	if (null == bScrollToSection) bScrollToSection = false;

	if (sectionID != 'extranet') sectionID = parseInt(sectionID);

	var obSection = BX(this.name + '_employee_section_' + sectionID);
	if (!obSection.BX_LOADED)
	{
		if (IntranetUsers.arEmployees[sectionID] != null)
		{
			this.show(sectionID);
		}
		else
		{
			var url = this.getAjaxUrl() + '&MODE=EMPLOYEES&SECTION_ID=' + sectionID;
			BX.ajax.loadJSON(url,  BX.proxy(__onLoadEmployees, this));
		}
	}

	if (bScrollToSection)
	{
		BX(this.name + '_employee_search_layout').scrollTop = obSection.offsetTop - 40;
	}

	BX.toggleClass(obSection, "company-department-opened");

	BX.toggleClass(BX(this.name + '_children_' + sectionID), "company-department-children-opened");
}

IntranetUsers.prototype.show = function (sectionID, usersData, sectionPrefixName)
{
	sectionPrefixName = sectionPrefixName || '';
	var obSection = BX(this.name + '_' + sectionPrefixName + 'employee_section_' + sectionID);
	var arEmployees = usersData || IntranetUsers.arEmployees[sectionID];

	if(obSection !== null)
	{
		obSection.BX_LOADED = true;
	}

	var obSectionDiv = BX(this.name + '_' + sectionPrefixName + 'employees_' + sectionID);
	if (obSectionDiv)
	{
		obSectionDiv.innerHTML = '';

		for (var i = 0; i < arEmployees.length; i++)
		{

			var obUserRow;
			var bSelected = false;

			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			}

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}
			
			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}
			
			obInput.value = arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "company-department-employee" + (bSelected ? " company-department-employee-selected" : "")
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "company-department-employee-avatar"
						},
						style : {
							background : arEmployees[i].PHOTO ? "url('" + arEmployees[i].PHOTO + "') no-repeat center center" : ""
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-icon"
						}
					}),
					BX.create("div", {
						props : {
							className : "company-department-employee-info"
						},
						children : [
							BX.create("div", {
								props : {
									className : "company-department-employee-name"
								},
								text : arEmployees[i].NAME
							}),
							BX.create("div", {
								props : {
									className : "company-department-employee-position"
								},
								html : !arEmployees[i].HEAD && !arEmployees[i].WORK_POSITION ? "&nbsp;" : (BX.util.htmlspecialchars(arEmployees[i].WORK_POSITION) + (arEmployees[i].HEAD && arEmployees[i].WORK_POSITION ? ', ' : '') + (arEmployees[i].HEAD ? BX.message('INTRANET_EMP_HEAD') : ''))
							})
						]
					})
				]
			})
			
			obSectionDiv.appendChild(obUserRow);
		}
	}
}

IntranetUsers.prototype.select = function(e)
{
	var obCurrentTarget;
	var i = 0;
	
	var target = e.target || e.srcElement;

	if (e.currentTarget)
	{
		obCurrentTarget = e.currentTarget;
	}
	else // because IE does not support currentTarget
	{
		obCurrentTarget = target;
		
		while(!BX.hasClass(obCurrentTarget, "finder-box-item") && !BX.hasClass(obCurrentTarget, "company-department-employee"))
		{
			obCurrentTarget = obCurrentTarget.parentNode;
		}
	}
	
	var obInput = BX.findChild(obCurrentTarget, {tag: "input"});

	if (!this.multiple)
	{
		var arInputs = document.getElementsByName(this.name);
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value != obInput.value)
			{
				BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
			else
			{
				BX.addClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
			}
		}
		obInput.checked = true;
		BX.addClass(obCurrentTarget, BX.hasClass(obCurrentTarget, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
		
		this.searchInput.value = IntranetUsers.arEmployeesData[obInput.value].name;

		this.arSelected = [];
		this.arSelected[obInput.value] = {
			id : obInput.value,
			name : IntranetUsers.arEmployeesData[obInput.value].name,
			sub : IntranetUsers.arEmployeesData[obInput.value].sub,
			sup : IntranetUsers.arEmployeesData[obInput.value].sup,
			position : IntranetUsers.arEmployeesData[obInput.value].position,
			photo : IntranetUsers.arEmployeesData[obInput.value].photo
		};
	}
	else
	{
		var arInputs = document.getElementsByName(this.name + "[]");
		if (!BX.util.in_array(obInput, arInputs)) { // IE7
			obInput.checked = false;
			BX.toggleClass(obInput.parentNode, BX.hasClass(obInput.parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
		}
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value == obInput.value)
			{
				arInputs[i].checked = false;
				BX.toggleClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}
		
		if (BX.hasClass(obInput.parentNode, "finder-box-item-selected") || BX.hasClass(obInput.parentNode, "company-department-employee-selected"))
		{
			obInput.checked = true;
		}
		
		if (obInput.checked)
		{
			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});
			
			if (!BX(this.name + "_employee_selected_" + obInput.value))
			{
				var obUserRow = BX.create('DIV');
				obUserRow.id = this.name + '_employee_selected_' + obInput.value;
				obUserRow.className = 'finder-box-selected-item';
				
				var obNameDiv = BX.findChild(obCurrentTarget, {tag: "DIV", className: "finder-box-item-text"}, true) || BX.findChild(obCurrentTarget, {tag: "DIV", className: "company-department-employee-name"}, true);

				obUserRow.innerHTML =  "<div class=\"finder-box-selected-item-icon\" id=\"user-selector-unselect-" + obInput.value + "\" onclick=\"O_" + this.name + ".unselect(" + obInput.value + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + obNameDiv.innerHTML + "</span>";
				obSelected.appendChild(obUserRow);
				
				var countSpan = BX(this.name + "_current_count");
				countSpan.innerHTML = parseInt(countSpan.innerHTML) + 1;

				this.arSelected[obInput.value] = {
					id : obInput.value,
					name : IntranetUsers.arEmployeesData[obInput.value].name,
					sub : IntranetUsers.arEmployeesData[obInput.value].sub,
					sup : IntranetUsers.arEmployeesData[obInput.value].sup,
					position : IntranetUsers.arEmployeesData[obInput.value].position,
					photo : IntranetUsers.arEmployeesData[obInput.value].photo
				};
			}
		}
		else
		{
			BX.remove(BX(this.name + '_employee_selected_' + obInput.value));
			
			var countSpan = BX(this.name + "_current_count");
			countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;

			this.arSelected[obInput.value] = null;
		}
	}

	if (!BX.util.in_array(obInput.value, IntranetUsers.lastUsers))
	{
		IntranetUsers.lastUsers.unshift(obInput.value);
		BX.userOptions.save('intranet', 'user_search', 'last_selected', IntranetUsers.lastUsers.slice(0, 10));
	}
	
	if (this.onSelect)
	{
		var emp = this.arSelected.pop();
		this.arSelected.push(emp);
		this.onSelect(emp);
	}
	
	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.unselect = function(employeeID)
{
	var link = BX("user-selector-unselect-" + employeeID);
	var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
	for(var i = 0; i < arInputs.length; i++)
	{
		if (arInputs[i].value == employeeID)
		{
			arInputs[i].checked = false;
			BX.removeClass(arInputs[i].parentNode, BX.hasClass(arInputs[i].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected");
		}
	}
	if (this.multiple)
	{
		if (link)
		{
			BX.remove(link.parentNode);
		}
		var countSpan = BX(this.name + "_current_count");
		countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;
	}

	this.arSelected[employeeID] = null;
	
	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

IntranetUsers.prototype.setSelected = function(arEmployees)
{
	for(var i = 0, count = this.arSelected.length; i < count; i++)
	{
		if (this.arSelected[i] && this.arSelected[i].id)
			this.unselect(this.arSelected[i].id);
	}

	if (!this.multiple)
	{
		arEmployees = [arEmployees[0]];
	}
	this.arSelected = [];
	for(var i = 0, count = arEmployees.length; i < count; i++)
	{
		this.arSelected[arEmployees[i].id] = arEmployees[i];

		var hiddenInput = BX.create("input", {
			props: {
				className: "intranet-hidden-input",
				value: arEmployees[i].id,
				checked: "checked",
				name: this.name + (this.multiple ? "[]" : "")
			}
		});

		BX(this.name + "_last").appendChild(hiddenInput);

		if (this.multiple)
		{
			var obSelected = BX.findChild(BX(this.name + "_selected_users"), {className: "finder-box-selected-items"});
			var obUserRow = BX.create("div", {
				props: {
					className: "finder-box-selected-item",
					id: this.name + '_employee_selected_' + arEmployees[i].id
				},
				html: "<div class=\"finder-box-selected-item-icon\" id=\"user-selector-unselect-" + arEmployees[i].id + "\" onclick=\"O_" + this.name + ".unselect(" + arEmployees[i].id + ", this);\"></div><span class=\"finder-box-selected-item-text\">" + BX.util.htmlspecialchars(arEmployees[i].name) + "</span>"
			});
			obSelected.appendChild(obUserRow);
		}

		var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
		for(var j = 0; j < arInputs.length; j++)
		{
			if (arInputs[j].value == arEmployees[i].id)
			{
				BX.toggleClass(arInputs[j].parentNode, BX.hasClass(arInputs[j].parentNode, "finder-box-item") ?  "finder-box-item-selected" : "company-department-employee-selected")
			}
		}
	}

	if (this.multiple)
	{
		BX.adjust(BX(this.name + "_current_count"), {text: arEmployees.length});
	}
}

IntranetUsers.prototype.search = function(e)
{
	if(!e) e = window.event;

	function __onLoadEmployees(data)
	{
		this.showResults(data);
	}
	
	if (this.searchInput.value.length > 0)
	{
		this.displayTab("search");

		var url = this.getAjaxUrl() + '&MODE=SEARCH&SEARCH_STRING=' + encodeURIComponent(this.searchInput.value);
		if (this.bSubordinateOnly)
		{
			url += "&S_ONLY=Y";
		}
		BX.ajax.loadJSON(url, BX.proxy(__onLoadEmployees, this));
	}
}

IntranetUsers.prototype.showResults = function(data)
{
	var arEmployees = data;
	var obSectionDiv = BX(this.name + '_search');
	
	var arInputs = obSectionDiv.getElementsByTagName("input");
	for(var i = 0, count = arInputs.length; i < count; i++)
	{
		if (arInputs[i].checked)
		{
			BX(this.name + '_last').appendChild(arInputs[i]);
		}
	}
	
	if (obSectionDiv)
	{
		obSectionDiv.innerHTML = '';
		
		var table = BX.create("table", {
			props : {
				className : "finder-box-tab-columns",
				cellspacing : "0"
			},
			children : [
				 BX.create("tbody")
			]
		});
		
		var tr = BX.create("tr");
		table.firstChild.appendChild(tr);

		var td = BX.create("td");
		tr.appendChild(td);
		
		obSectionDiv.appendChild(table);
		
		for (var i = 0; i < arEmployees.length; i++)
		{
			var obUserRow;
			var bSelected = false;
			IntranetUsers.arEmployeesData[arEmployees[i].ID] = {
				id : arEmployees[i].ID,
				name : arEmployees[i].NAME,
				sub : arEmployees[i].SUBORDINATE == "Y" ? true : false,
				sup : arEmployees[i].SUPERORDINATE == "Y" ? true : false,
				position : arEmployees[i].WORK_POSITION,
				photo : arEmployees[i].PHOTO
			}

			var obInput = BX.create("input", {
				props : {
					className : "intranet-hidden-input"
				}
			});

			if (this.multiple)
			{
				obInput.name = this.name + "[]";
				obInput.type = "checkbox";
			}
			else
			{
				obInput.name = this.name;
				obInput.type = "radio";
			}
			
			var arInputs = document.getElementsByName(obInput.name);
			var j = 0;
			while(!bSelected && j < arInputs.length)
			{
				if (arInputs[j].value == arEmployees[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}
			
			obInput.value = arEmployees[i].ID;

			var text = arEmployees[i].NAME;
			/*
			TODO: good look and feel
			if (arEmployees[i].WORK_POSITION.length > 0)
				text = text + ', ' + arEmployees[i].WORK_POSITION;*/

			var anchor_user_id = "finded_anchor_user_id_" + arEmployees[i].ID;

			obUserRow = BX.create("div", {
				props : {
					className : "finder-box-item" + (bSelected ? " finder-box-item-selected" : ""),
					id: anchor_user_id
				},
				events : {
					click : BX.proxy(this.select, this)
				},
				children : [
					obInput,
					BX.create("div", {
						props : {
							className : "finder-box-item-text"
						},
						text : text
					}),
					BX.create("div", {
						props : {
							className : "finder-box-item-icon"
						}
					})
				]
			})
			
			td.appendChild(obUserRow);
			
			if (i == Math.ceil(arEmployees.length / 2) - 1)
			{
				td = BX.create("td");
				table.firstChild.appendChild(td);
			}

			BX.tooltip(arEmployees[i].ID, anchor_user_id, "");
		}
	}	
}

IntranetUsers.prototype.displayTab = function(tab)
{
	BX.removeClass(BX(this.name + "_last"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_search"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_structure"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_groups"), "finder-box-tab-content-selected");
	BX.addClass(BX(this.name + "_" + tab), "finder-box-tab-content-selected");
	
	BX.removeClass(BX(this.name + "_tab_last"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_search"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_structure"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_groups"), "finder-box-tab-selected");
	BX.addClass(BX(this.name + "_tab_" + tab), "finder-box-tab-selected");
}

IntranetUsers.prototype._onFocus = function()
{
	this.searchInput.value = "";
}

IntranetUsers.prototype.getAjaxUrl = function()
{
    return this.ajaxUrl || IntranetUsers.ajaxUrl;
}

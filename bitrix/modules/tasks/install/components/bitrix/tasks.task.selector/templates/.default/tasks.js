function TasksTask(name, multiple) {
	this.name = name;
	this.multiple = multiple;
	this.arSelected = [];
}

TasksTask.arTasks = {};
TasksTask.arTasksData = {};

TasksTask.prototype.select = function(e)
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

		while(!BX.hasClass(obCurrentTarget, "finder-box-item"))
		{
			obCurrentTarget = obCurrentTarget.parentNode;
		}
	}

	var obInput = BX.findChild(obCurrentTarget, {tag: "input"});
	var obNameDiv = BX.findChild(obCurrentTarget, {tag: "DIV", className: "finder-box-item-text"}, true);
	var obTask = {
		id : obInput.value,
		name : BX.util.htmlspecialcharsback(obNameDiv.innerHTML),
		sub : TasksTask.arTasksData[obInput.value].sub,
		position : TasksTask.arTasksData[obInput.value].position
	};

	if (!this.multiple)
	{
		var arInputs = document.getElementsByName(this.name);
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value != obInput.value)
			{
				BX.removeClass(arInputs[i].parentNode, "finder-box-item-selected");
			}
			else
			{
				BX.addClass(arInputs[i].parentNode, "finder-box-item-selected");
			}
		}
		obInput.checked = true;
		BX.addClass(obCurrentTarget, "finder-box-item-selected");

		this.searchInput.value = BX.util.htmlspecialcharsback(obNameDiv.innerHTML);

		this.arSelected = [];
		this.arSelected[obInput.value] = obTask;
	}
	else
	{
		var arInputs = document.getElementsByName(this.name + "[]");
		for(var i = 0; i < arInputs.length; i++)
		{
			if (arInputs[i].value == obInput.value)
			{
				arInputs[i].checked = false;
				BX.toggleClass(arInputs[i].parentNode, "finder-box-item-selected")
			}
		}

		if (BX.hasClass(obInput.parentNode, "finder-box-item-selected"))
		{
			obInput.checked = true;
		}

		if (obInput.checked)
		{

			var obSelected =  BX.findChild(BX(this.name + "_selected_tasks"), {className: "finder-box-selected-items"});

			if (!BX(this.name + "_task_selected_" + obInput.value))
			{
				var obUserRow = BX.create('DIV');
				obUserRow.id = this.name + '_task_selected_' + obInput.value;
				obUserRow.className = 'finder-box-selected-item';

				obUserRow.innerHTML =  "<div class=\"finder-box-selected-item-icon\" onclick=\"O_" + this.name + ".unselect(" + obInput.value + ", this);\" id=\"task-unselect-" + obInput.value + "\"></div><a href=\"" + BX.message("TASKS_PATH_TO_TASK").replace("#task_id#", obInput.value).replace("#action#", "view") + "\" target=\"_blank\" class=\"finder-box-selected-item-text\">" + obNameDiv.innerHTML + "</a>";
				obSelected.appendChild(obUserRow);

				var countSpan = BX(this.name + "_current_count");
				countSpan.innerHTML = parseInt(countSpan.innerHTML) + 1;

				this.arSelected[obInput.value] = obTask;
			}
		}
		else
		{
			BX.remove(BX(this.name + '_task_selected_' + obInput.value));

			var countSpan = BX(this.name + "_current_count");
			countSpan.innerHTML = parseInt(countSpan.innerHTML) - 1;

			this.arSelected[obInput.value] = null;
		}
	}

	if (this.onSelect)
	{
		this.onSelect(obTask);
	}

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

TasksTask.prototype.unselect = function(taskID, link)
{
	var arInputs = document.getElementsByName(this.name + (this.multiple ? "[]" : ""));
	for(var i = 0; i < arInputs.length; i++)
	{
		if (arInputs[i].value == taskID)
		{
			arInputs[i].checked = false;
			BX.removeClass(arInputs[i].parentNode, "finder-box-item-selected");
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

	this.arSelected[taskID] = null;

	if (this.onChange)
	{
		this.onChange(this.arSelected);
	}
}

TasksTask.prototype.search = function(e)
{
	if(!e) e = window.event;

	function __onLoadTasks(data)
	{
		this.showResults(data);
	}

	if (this.searchInput.value.length > 0)
	{
		this.displayTab("search");

		var url = TasksTask.ajaxUrl + '&MODE=SEARCH&SEARCH_STRING=' + encodeURIComponent(this.searchInput.value) + "&" + BX.ajax.prepareData(this.filter, "FILTER");
		BX.ajax.loadJSON(url, BX.proxy(__onLoadTasks, this));
	}
}

TasksTask.prototype.showResults = function(data)
{
	var arTasks = data;
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

		for (var i = 0; i < arTasks.length; i++)
		{
			var obUserRow;
			var bSelected = false;

			TasksTask.arTasksData[arTasks[i].ID] = {
				id : arTasks[i].ID,
				name : arTasks[i].TITLE,
				status : arTasks[i].STATUS
			}

			var obInput = BX.create("input", {
				props : {
					className : "tasks-hidden-input"
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
				if (arInputs[j].value == arTasks[i].ID && arInputs[j].checked)
				{
					bSelected = true;
				}
				j++;
			}

			obInput.value = arTasks[i].ID;

			switch(parseInt(arTasks[i].STATUS))
			{
				case -1:
					var sStatusClass = " task-status-overdue";
					break;
				case -2:
				case 1:
					var sStatusClass = " task-status-new";
					break;
				case 2:
					var sStatusClass = " task-status-accepted";
					break;
				case 3:
					var sStatusClass = " task-status-in-progress";
					break;
				case 4:
					var sStatusClass = " task-status-waiting";
					break;
				case 5:
					var sStatusClass = " task-status-completed";
					break;
				case 6:
					var sStatusClass = " task-status-delayed";
					break;
				case 7:
					var sStatusClass = " task-status-declined";
					break;
				default:
					var sStatusClass = "";
			}

			obTaskRow = BX.create("div", {
				props : {
					className : "finder-box-item" + sStatusClass + (bSelected ? " finder-box-item-selected" : "")
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
						text : arTasks[i].TITLE
					}),
					BX.create("div", {
						props : {
							className : "finder-box-item-icon"
						}
					})
				]
			})

			obSectionDiv.appendChild(obTaskRow);
		}
	}
}

TasksTask.prototype.displayTab = function(tab)
{
	BX.removeClass(BX(this.name + "_last"), "finder-box-tab-content-selected");
	BX.removeClass(BX(this.name + "_search"), "finder-box-tab-content-selected");
	BX.addClass(BX(this.name + "_" + tab), "finder-box-tab-content-selected");

	BX.removeClass(BX(this.name + "_tab_last"), "finder-box-tab-selected");
	BX.removeClass(BX(this.name + "_tab_search"), "finder-box-tab-selected");
	BX.addClass(BX(this.name + "_tab_" + tab), "finder-box-tab-selected");
}
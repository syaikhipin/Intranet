<?
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile(__FILE__);

CModule::IncludeModule('intranet');

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("INTR_SETTINGS"), "ICON" => "intranet_settings", "TITLE" => GetMessage("INTR_SETTINGS_TITLE"),
	),
	array(
		"DIV" => "edit2", "TAB" => GetMessage("INTR_SETTINGS_IMPORT"), "ICON" => "intranet_settings", "TITLE" => GetMessage("INTR_SETTINGS_IMPORT_TITLE"),
	),
	array(
		"DIV" => "edit3", "TAB" => GetMessage("INTR_SETTINGS_STSSYNC"), "ICON" => "intranet_settings", "TITLE" => GetMessage("INTR_SETTINGS_STSSYNC_TITLE"),
	),
	array(
		"DIV" => "edit4", "TAB" => GetMessage("INTR_SETTINGS_SEARCH"), "ICON" => "intranet_settings", "TITLE" => GetMessage("INTR_SETTINGS_SEARCH_TITLE"),
	),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$arSiteSettings = array('iblock_absence', 'iblock_calendar', 'iblock_group_calendar', 'iblock_tasks', /*'search_user_url',*/ 'path_task_group', 'path_task_group_entry', 'path_task_user', 'path_task_user_entry', 'path_user');
$arDefaultValues = array(
	'path_user' => '/company/personal/user/#USER_ID#/',
	'path_task_user' => '/company/personal/user/#USER_ID#/tasks/',
	'path_task_user_entry' => '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/',
	'path_task_group' => '/workgroups/group/#GROUP_ID#/tasks/',
	'path_task_group_entry' => '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/',
);

$dbSites = CSite::GetList($by = 'sort', $order = 'asc', array('ACTIVE' => 'Y'));
$arSites = array();
$default_site = '';
while ($arRes = $dbSites->GetNext())
{
	$arSites[$arRes['ID']] = '('.$arRes['ID'].') '.$arRes['NAME'];
	if ($arRes['DEF'] == 'Y')
		$default_site = $arRes['ID'];
}

$aSubTabs = array();
$aSubTabs[] = array("DIV" => "opt_common", "TAB" => GetMessage('INTR_SUBTAB_COMMON'), 'TITLE' => GetMessage('INTR_SUBTAB_TITLE_COMMON'));

foreach ($arSites as $SITE_ID => $SITE_NAME)
{
	$aSubTabs[] = array("DIV" => "opt_site_".$SITE_ID, "TAB" => $SITE_NAME, 'TITLE' => GetMessage('INTR_SUBTAB_TITLE_SITE').' '.$SITE_ID);
}

$childTabControl = new CAdminViewTabControl("childTabControl", $aSubTabs);
array_shift($aSubTabs);

foreach ($aSubTabs as $key => $tab) $aSubTabs[$key]['DIV'] .= '_1';
$childTabControl_1 = new CAdminViewTabControl("childTabControl_1", $aSubTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("intranet");
	}
	else
	{
		COption::SetOptionString("intranet", 'iblock_type', $_REQUEST['IBLOCK_TYPE'], GetMessage('INTR_OPTION_IBLOCK_TYPE'));
		COption::SetOptionInt("intranet", 'iblock_structure', $_REQUEST['IBLOCK_STRUCTURE'], GetMessage('INTR_OPTION_IBLOCK_STRUCTURE'));
		//COption::SetOptionInt("intranet", 'iblock_absence', $_REQUEST['IBLOCK_ABSENCE'], GetMessage('INTR_OPTION_IBLOCK_ABSENCE'));
		COption::SetOptionInt("intranet", 'iblock_honour', $_REQUEST['IBLOCK_HONOUR'], GetMessage('INTR_OPTION_IBLOCK_HONOUR'));
		COption::SetOptionInt("intranet", 'iblock_state_history', $_REQUEST['IBLOCK_STATE_HISTORY'], GetMessage('INTR_OPTION_IBLOCK_STATE_HISTORY'));

		COption::SetOptionString("intranet", 'iblock_type_calendar', $_REQUEST['IBLOCK_TYPE_CALENDAR'], GetMessage('INTR_OPTION_IBLOCK_TYPE_CALENDAR'));
		//COption::SetOptionInt("intranet", 'iblock_calendar', $_REQUEST['IBLOCK_CALENDAR'], GetMessage('INTR_OPTION_IBLOCK_CALENDAR'));

		COption::SetOptionString("intranet", 'iblock_type_tasks', $_REQUEST['IBLOCK_TYPE_TASKS'], GetMessage('INTR_OPTION_IBLOCK_TYPE_TASKS'));
		//COption::SetOptionInt("intranet", 'iblock_tasks', $_REQUEST['IBLOCK_TASKS'], GetMessage('INTR_OPTION_IBLOCK_TASKS'));

		COption::SetOptionString("intranet", "use_tasks_2_0", $_REQUEST['USE_TASKS_2_0'] == "Y" ? "Y" : "N");

		if (is_array($_REQUEST['IMPORT']))
		{
			foreach ($_REQUEST['IMPORT'] as $key => $value)
			{
				if (is_array($value)) $value = serialize($value);

				COption::SetOptionString('intranet', 'import_'.$key, $value);
			}
		}

		$first_week_day = substr($_REQUEST['first_week_day'], 0, 2);
		COption::SetOptionString('intranet', 'first_week_day', $first_week_day);

		$tz_transition = $_REQUEST['tz_transition'] == 'Y' ? 'Y' : 'N';
		$tz_transition_standard = $_REQUEST['tz_transition_standard'] ? $_REQUEST['tz_transition_standard'] : '';
		$tz_transition_daylight = $_REQUEST['tz_transition_daylight'] ? $_REQUEST['tz_transition_daylight'] : '';

		COption::SetOptionString('intranet', 'tz_transition', $tz_transition);
		COption::SetOptionString('intranet', 'tz_transition_standard', $tz_transition_standard);
		COption::SetOptionString('intranet', 'tz_transition_daylight', $tz_transition_daylight);

		// COption::SetOptionString('intranet', 'path_user', $_REQUEST['path_user']);

		// COption::SetOptionString('intranet', 'path_task_user', $_REQUEST['path_task_user']);
		// COption::SetOptionString('intranet', 'path_task_user_entry', $_REQUEST['path_task_user_entry']);
		// COption::SetOptionString('intranet', 'path_task_group', $_REQUEST['path_task_group']);
		// COption::SetOptionString('intranet', 'path_task_group_entry', $_REQUEST['path_task_group_entry']);

		foreach ($arSiteSettings as $param_name)
		{
			$bDiffer = false;
			$value = '';
			foreach ($arSites as $SITE_ID => $SITE_NAME)
			{
				$new_value = $_REQUEST[$param_name.'_'.$SITE_ID];
				if ($value === '')
					$value = $new_value;
				elseif ($value !== $new_value)
				{
					$bDiffer = true;
					break;
				}
			}

			COption::RemoveOption('intranet', $param_name);
			if ($param_name == 'path_user')
				COption::RemoveOption('intranet', 'search_user_url');

			if (!$bDiffer)
			{
				COption::SetOptionString('intranet', $param_name, $value);
				if ($param_name == 'path_user')
					COption::SetOptionString('intranet', 'search_user_url', $value);
			}
			else
			{
				foreach ($arSites as $SITE_ID => $SITE_NAME)
				{
					COption::SetOptionString('intranet', $param_name, $_REQUEST[$param_name.'_'.$SITE_ID], false, $SITE_ID);
					if ($param_name == 'path_user')
						COption::SetOptionString('intranet', 'search_user_url', $_REQUEST[$param_name.'_'.$SITE_ID], false, $SITE_ID);
				}
			}
		}

		COption::SetOptionString('intranet', 'ws_contacts_get_images', $_REQUEST['ws_contacts_get_images'] == 'Y' ? 'Y' : 'N');

		$ar = array();
		$i = 0;
		while(array_key_exists("search_file_extension_".$i, $_REQUEST))
		{
			$ext = trim($_REQUEST["search_file_extension_".$i]);
			if(strlen($ext) > 0)
			{
				$ar[$ext] = $ext;
				COption::SetOptionString("intranet", "search_file_extension_".$ext, $ext);
				COption::SetOptionString("intranet", "search_file_extension_exe_".$ext, trim($_REQUEST["search_file_extension_exe_".$i]));
				COption::SetOptionString("intranet", "search_file_extension_cd_".$ext, trim($_REQUEST["search_file_extension_cd_".$i]));
			}
			$i++;
		}
		COption::SetOptionString("intranet", "search_file_extensions", implode(",", $ar));
	}

	if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
	{
		LocalRedirect($_REQUEST["back_url_settings"]);
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
	}
}

$dbIBlockType = CIBlockType::GetList();
$arIBTypes = array();
$arIB = array();
while ($arIBType = $dbIBlockType->Fetch())
{
	if ($arIBTypeData = CIBlockType::GetByIDLang($arIBType["ID"], LANG))
	{
		$arIB[$arIBType['ID']] = array();
		$arIBTypes[$arIBType['ID']] = '['.$arIBType['ID'].'] '.$arIBTypeData['NAME'];
	}
}

$dbIBlock = CIBlock::GetList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y'));
while ($arIBlock = $dbIBlock->Fetch())
{
	$arIB[$arIBlock['IBLOCK_TYPE_ID']][$arIBlock['ID']] = ($arIBlock['CODE'] ? '['.$arIBlock['CODE'].'] ' : '').$arIBlock['NAME'];
}

foreach ($arSiteSettings as $param_name)
{
	//${$param_name.'_by_site'} = COption::GetOptionString('intranet', $param_name.'_by_site', 'N');
	//if (true || ${$param_name.'_by_site'} == 'Y')
	//{
		$$param_name = array();
		foreach ($arSites as $site => $site_name)
		{
			${$param_name}[$site] = COption::GetOptionString('intranet', $param_name, $arDefaultValues[$param_name], $site);
		}
	//}
	//else
	//{
	//	$$param_name = COption::GetOptionString('intranet', $param_name, '');
	//}
}

$current_ibtype = COption::GetOptionString('intranet', 'iblock_type', '');
$current_ib_structure = COption::GetOptionString('intranet', 'iblock_structure', '');
//$current_ib_absence = COption::GetOptionString('intranet', 'iblock_absence', '');
$current_ib_honour = COption::GetOptionString('intranet', 'iblock_honour', '');
$current_ib_state_history = COption::GetOptionString('intranet', 'iblock_state_history', '');

$current_ibtype_calendar = COption::GetOptionString('intranet', 'iblock_type_calendar', '');
//$current_ib_calendar = COption::GetOptionString('intranet', 'iblock_calendar', '');

$current_ibtype_tasks = COption::GetOptionString('intranet', 'iblock_type_tasks', '');
//$current_ib_tasks = COption::GetOptionString('intranet', 'iblock_tasks', '');

$first_week_day = COption::GetOptionString('intranet', 'first_week_day', 'mo');

$tz_transition = COption::GetOptionString('intranet', 'tz_transition', 'Y');
$tz_transition_standard = COption::GetOptionString('intranet', 'tz_transition_standard', '');
$tz_transition_daylight = COption::GetOptionString('intranet', 'tz_transition_daylight', '');

$use_tasks_2_0 = COption::GetOptionString("intranet", "use_tasks_2_0", "N");

//$path_user = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/');

//$path_task_user = COption::GetOptionString('intranet', 'path_task_user', '/company/personal/user/#USER_ID#/tasks/');
//$path_task_user_entry = COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/');
//$path_task_group = COption::GetOptionString('intranet', 'path_task_group', '/workgroups/group/#GROUP_ID#/tasks/');
//$path_task_group_entry = COption::GetOptionString('intranet', 'path_task_group_entry', '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/');

$ws_contacts_get_images = COption::GetOptionString('intranet', 'ws_contacts_get_images', 'Y');

$tabControl->Begin();
?>
<script type="text/javascript">
var arIB = <?echo CUtil::PhpToJsObject($arIB)?>;

function change_iblock_list(value, index)
{
	if (null == index)
		index = 0;

	if (value && (!arIB[value] || arIB[value].length <= 0)) return;

	var arControls = [
		[document.forms.intr_opt_form.IBLOCK_STRUCTURE, document.forms.intr_opt_form.IBLOCK_HONOUR, document.forms.intr_opt_form.IBLOCK_STATE_HISTORY<?foreach ($arSites as $SITE_ID => $SITE_NAME) echo ', document.forms.intr_opt_form.IBLOCK_ABSENCE_'.$SITE_ID;?>],
		[<?$i = 0; foreach ($arSites as $SITE_ID => $SITE_NAME) echo ($i++<=0?'':', ').'document.forms.intr_opt_form.IBLOCK_CALENDAR_'.$SITE_ID;?>],
		[<?$i = 0; foreach ($arSites as $SITE_ID => $SITE_NAME) echo ($i++<=0?'':', ').'document.forms.intr_opt_form.IBLOCK_GROUP_CALENDAR_'.$SITE_ID;?>],
		[<?$i = 0; foreach ($arSites as $SITE_ID => $SITE_NAME) echo ($i++<=0?'':', ').'document.forms.intr_opt_form.IBLOCK_TASKS_'.$SITE_ID;?>]
	];

	for (var i = 0; i < arControls[index].length; i++)
	{
		while (arControls[index][i].options.length > 0) arControls[index][i].remove(0);

		if (value)
		{
			for (var j in arIB[value])
			{
				arControls[index][i].options[arControls[index][i].options.length] = new Option(arIB[value][j], j);
			}
		}
		else
		{
			arControls[index][i].options[0] = new Option('<?echo GetMessage('INTR_CHOOSE_IBTYPE')?>', '');
		}
	}
}

function BXChangeBySite(type, value)
{
	var obSiteSelector = document.forms.intr_opt_form[type + '_site'];
	if (obSiteSelector)
	{
		if (value)
			obSiteSelector.disabled = false;
		else
			obSiteSelector.disabled = true;
	}
}

function BXChangeSite(type, site)
{
<?foreach ($arSites as $SITE_ID => $SITE_NAME):?>document.getElementById(type + '_site_row_<?echo $SITE_ID?>').style.display = 'none';<?endforeach;?>
	document.getElementById(type + '_site_row_' + site).style.display = '';
}
</script>
<form method="post" name="intr_opt_form" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">
<? echo bitrix_sessid_post();?>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td>
<?
	$childTabControl->Begin();
	$childTabControl->BeginNextTab();
?>
<table width="75%" align="center">
	<tr class="heading">
		<td colspan="2"><?echo GetMessage('INTR_OPT_SECTION_IB_STRUCTURE')?></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_TYPE')?>:</td>
		<td valign="top" width="50%"><select name="IBLOCK_TYPE" onchange="change_iblock_list(this.value)">
			<option value=""><?echo GetMessage('INTR_OPTION_NOT_SET')?></option>
<?foreach ($arIBTypes as $ibtype_id => $ibtype_name):?><option value="<?echo $ibtype_id?>"<?echo $ibtype_id == $current_ibtype ? ' selected="selected"' : ''?>><?echo $ibtype_name?></option><?endforeach;?>
		</select></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_STRUCTURE')?>:</td>
		<td><select name="IBLOCK_STRUCTURE">
<?
if ($current_ib_structure || $current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $current_ib_structure ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_HONOUR')?>:</td>
		<td><select name="IBLOCK_HONOUR">
<?
if ($current_ib_honour || $current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $current_ib_honour ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_STATE_HISTORY')?>:</td>
		<td><select name="IBLOCK_STATE_HISTORY">
<?
if ($current_ib_state_history || $current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $current_ib_state_history ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?echo GetMessage('INTR_OPT_SECTION_IB_CALENDAR')?></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_TYPE')?>:</td>
		<td valign="top" width="50%"><select name="IBLOCK_TYPE_CALENDAR" onchange="change_iblock_list(this.value, 1)">
			<option value=""><?echo GetMessage('INTR_OPTION_NOT_SET')?></option>
<?foreach ($arIBTypes as $ibtype_id => $ibtype_name):?><option value="<?echo $ibtype_id?>"<?echo $ibtype_id == $current_ibtype_calendar ? ' selected="selected"' : ''?>><?echo $ibtype_name?></option><?endforeach;?>
		</select></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage('INTR_OPT_SECTION_IB_TASKS')?></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_TYPE')?>:</td>
		<td valign="top" width="50%"><select name="IBLOCK_TYPE_TASKS" onchange="change_iblock_list(this.value, 2)">
			<option value=""><?echo GetMessage('INTR_OPTION_NOT_SET')?></option>
<?foreach ($arIBTypes as $ibtype_id => $ibtype_name):?><option value="<?echo $ibtype_id?>"<?echo $ibtype_id == $current_ibtype_tasks ? ' selected="selected"' : ''?>><?echo $ibtype_name?></option><?endforeach;?>
		</select></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?php echo GetMessage("INTR_USE_TASKS_2_0")?></td>
		<td valign="top" width="50%"><input type="checkbox" name="USE_TASKS_2_0" value="Y" <?php echo ($use_tasks_2_0 == "Y" ? " checked" : "")?> /></td>
	</tr>
</table>
<?

foreach ($arSites as $SITE_ID => $SITE_NAME):
	$childTabControl->BeginNextTab();
?>
<table width="75%" align="center">

	<tr id="iblock_absence_site_row_<?=$SITE_ID?>">
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_ABSENCE')?>:</td>
		<td><select name="iblock_absence_<?echo $SITE_ID?>">
<?
if ($current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $iblock_absence[$SITE_ID] ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
	<tr id="iblock_calendar_site_row_<?=$SITE_ID?>">
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_CALENDAR')?>:</td>
		<td><select name="iblock_calendar_<?echo $SITE_ID?>">
<?
if ($current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype_calendar] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $iblock_calendar[$SITE_ID] ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
	<tr id="iblock_group_calendar_site_row_<?=$SITE_ID?>">
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_GROUP_CALENDAR')?>:</td>
		<td><select name="iblock_group_calendar_<?echo $SITE_ID?>">
<?
if ($current_ibtype):
?>
	<?foreach ($arIB[$current_ibtype_calendar] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $iblock_group_calendar[$SITE_ID] ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
	<tr id="iblock_tasks_site_row_<?=$SITE_ID?>">
		<td valign="top" width="50%"><?echo GetMessage('INTR_OPTION_IBLOCK_TASKS')?>:</td>
		<td><select name="iblock_tasks_<?echo $SITE_ID?>">
<?
if ($current_ibtype_tasks):
?>
	<?foreach ($arIB[$current_ibtype_tasks] as $iblock_id => $iblock):?><option value="<?echo $iblock_id?>"<?echo $iblock_id == $iblock_tasks[$SITE_ID] ? ' selected="selected"' : ''?>><?echo $iblock?></option><?endforeach;?>
<?
else:
?>
	<option value=""><?echo GetMessage('INTR_CHOOSE_IBTYPE')?></option>
<?
endif;
?></select></td>
	</tr>
</table>
<?
	endforeach;
?>
		</td>
	</tr>
<?
$childTabControl->End();

$tabControl->BeginNextTab();

// dirty hack! ;-)
$componentPath = '/bitrix/components/bitrix/intranet.users.import.1c';
__IncludeLang($_SERVER['DOCUMENT_ROOT'].'/'.$componentPath.'/lang/'.LANGUAGE_ID.'/.parameters.php');
$arComponentParameters = array();
require_once($_SERVER['DOCUMENT_ROOT'].'/'.$componentPath.'/.parameters.php');

$arParamsGroup = array('BASE' => array());
foreach ($arComponentParameters['PARAMETERS'] as $key => $arParamData)
{
	if (!$arParamData['PARENT'])
		$arParamData['PARENT'] = 'BASE';
	if (!is_array($arParamsGroup[$arParamData['PARENT']]))
		$arParamsGroup[$arParamData['PARENT']] = array();

	$arParamsGroup[$arParamData['PARENT']][$key] = $arParamData;
}

//$arParams = $arComponentParameters['PARAMETERS'];

$arExcludedParameters = array('IBLOCK_TYPE', 'DEPARTMENTS_IBLOCK_ID', 'ABSENCE_IBLOCK_ID', 'STATE_HISTORY_IBLOCK_ID');

foreach ($arParamsGroup as $GROUP => $arParams)
{
	foreach ($arExcludedParameters as $param)
		unset($arParams[$param]);

	if (!is_array($arParams) || count($arParams) <= 0)
		continue;
?>
<tr class="heading">
	<td colspan="2"><?echo is_array($arComponentParameters['GROUPS'][$GROUP]) ? $arComponentParameters['GROUPS'][$GROUP]['NAME'] : GetMessage('INTR_SETTINGS_COMPONENT_'.$GROUP)?></td>
</tr>
<?
	foreach ($arParams as $key => $arParam)
	{
		$value = COption::GetOptionString('intranet', 'import_'.$key, is_array($arParam['DEFAULT']) ? serialize($arParam['DEFAULT']) : $arParam['DEFAULT']);
?>
<tr>
	<td valign="top" width="50%"><?echo $arParam['NAME']?>: </td>
	<td>
<?
		switch($arParam['TYPE'])
		{
			case 'CHECKBOX':
				?><input type="hidden" name="IMPORT[<?=$key?>]" value="N" /><input type="checkbox" name="IMPORT[<?=$key?>]" value="Y"<?echo $value == 'Y' ? ' checked="checked"' : ''?> /><?
			break;

			case 'LIST':
				$bMultiple = $arParam['MULTIPLE'] == 'Y';
				if ($bMultiple && $value && !is_array($value)) $value = unserialize($value);
				?><select name="IMPORT[<?=$key?>]<?=$bMultiple ? '[]' : ''?>"<?=$bMultiple ? ' multiple="multiple" size="10"' : ''?>>
<?foreach ($arParam['VALUES'] as $val => $title):?><option value="<?=htmlspecialcharsbx($val)?>"<?=($bMultiple && in_array($val, $value) || (!$bMultiple && $val == $value)) ? ' selected="selected"' : ''?>><?=htmlspecialcharsbx($title)?></option><?endforeach;?>
				</select><?

			break;

			default:
				?><input type="text" name="IMPORT[<?=$key?>]" value="<?=htmlspecialcharsbx($value)?>" /><?
			break;
		}
?>
	</td>
</tr>
<?
	}
}
?>
<?
$tabControl->BeginNextTab();
$arTZRules = array(
	'standard' => array(
		'north' => '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>',
		'south' => '<transitionRule month="3" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>',
		'usa' => '<transitionRule month="11" day="su" weekdayOfMonth="first" /><transitionTime>2:0:0</transitionTime>',
		'southam' => '<transitionRule month="2" day="sa" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>',
		'other' => 'other',
	),
	'daylight' => array(
		'ru' => '<transitionRule month="3" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>',
		'usa' => '<transitionRule month="3" day="su" weekdayOfMonth="2" /><transitionTime>2:0:0</transitionTime>',
		'arab' => '<transitionRule month="4" day="fr" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>',
		'cuba' => '<transitionRule month="4" dayOfMonth="1" /><transitionTime>2:0:0</transitionTime>',
		'southam' => '<transitionRule month="10" day="su" weekdayOfMonth="first" /><transitionTime>2:0:0</transitionTime>',
		'pacific' => '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>',
		'other' => 'other',
	),
);

$arWeekDays = array(
	'mo', 'tu', 'we', 'th', 'fr', 'sa', 'su'
);

$arVariants = array_keys($arTZRules);
?>
<script type="text/javascript">
function check_other(obSelect, type)
{
	if (obSelect.value && obSelect.value != 'other')
	{
		obSelect.form['tz_transition_' + type].value = obSelect.value;
	}
	else if (obSelect.value == 'other')
	{
		document.getElementById('tz_edit_' + type).style.display = 'block';
	}
}
function disable_tz_controls(obCheckbox)
{
	var obForm = obCheckbox.form
	if (!obCheckbox.checked)
	{
		<?foreach ($arVariants as $var):?>
		obForm.tz_transition_<?echo $var;?>.disabled = true;
		obForm.tz_transition_<?echo $var;?>_tpl.disabled = true;
		<?endforeach?>
	}
	else
	{
		<?foreach ($arVariants as $var):?>
		obForm.tz_transition_<?echo $var;?>.disabled = false;
		obForm.tz_transition_<?echo $var;?>_tpl.disabled = false;
		<?endforeach?>
	}
}
</script>
	<tr>
		<td valign="top" width="45%"><?echo GetMessage('INTR_OPTION_GET_CONTACTS_IMAGES')?>: </td>
		<td width="55%">
			<input type="checkbox" name="ws_contacts_get_images" id="ws_contacts_get_images" value="Y" <?echo $ws_contacts_get_images == 'Y' ? 'checked="checked"' : ''?> /><label for="ws_contacts_get_images"><?echo GetMessage('INTR_OPTION_WS_CONTACTS_GET_IMAGES_YES')?></label>
		</td>
	</tr>
	<tr>
		<td valign="top" width="45%"><?echo GetMessage('INTR_OPTION_FIRSTDAY')?>: </td>
		<td width="55%"><select name="first_week_day">
<?
foreach ($arWeekDays as $day):
?>
			<option value="<?echo $day?>"<?echo $first_week_day == $day ? ' selected="selected"' : ''?>><?echo GetMessage('INTR_OPTION_FIRSTDAY_'.$day);?></option>
<?
endforeach;
?>
		</select></td>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage('INTR_OPTION_TZ')?></td>
	</tr>
	<tr>
		<td valign="top"><?echo GetMessage('INTR_OPTION_TZ_USE_DAYLIGHT_SAVING')?>: </td>
		<td><input type="checkbox" name="tz_transition" id="tz_transition" <?echo $tz_transition == 'N' ? '' : 'checked="checked"'?> value="Y" onclick="disable_tz_controls(this)" /><label for="tz_transition"><?echo GetMessage('INTR_OPTION_TZ_USE_DAYLIGHT_SAVING_YES')?></label></td>
	</tr>
<?
foreach ($arVariants as $var):
?>
	<tr>
		<td valign="top"><?echo GetMessage('INTR_OPTION_TZ_TO_'.strtoupper($var).'_DATE')?>: </td>
		<td>
			<select name="tz_transition_<?echo $var?>_tpl" onchange="check_other(this, '<?echo $var?>')" <?echo $tz_transition == 'N' ? 'disabled="disabled"' : ''?>>
				<option value=""><?echo GetMessage('INTR_OPTION_NOT_SET')?></option>
<?
$bFound = false;
$firstRule = '';
foreach ($arTZRules[$var] as $id => $rule):
	$bFound |= ($rule === ${'tz_transition_'.$var});
	if ($firstRule == '') $firstRule = $rule;
?>
	<option value="<?echo htmlspecialcharsbx($rule)?>" <?echo ($rule === ${'tz_transition_'.$var} || (!$bFound && $rule == 'other' && ${'tz_transition_'.$var} != '')) ? 'selected="selected"' : ''?>><?echo GetMessage('INTR_OPTION_TZ_TO_'.strtoupper($var).'_DATE_'.$id)?></option>
<?
endforeach;
?>
			</select><br />
			<div id="tz_edit_<?echo $var;?>" style="display: <?echo $bFound || ${'tz_transition_'.$var} == '' ? 'none' : 'block'?>">
				<textarea name="tz_transition_<?echo $var?>" cols="40" rows="5" <?echo $tz_transition == 'N' ? 'disabled="disabled"' : ''?>><?echo htmlspecialcharsbx(${'tz_transition_'.$var});?></textarea><br />
				<small><?echo GetMessage('INTR_OPTION_TZ_HINT');?></small>
			</div>
		</td>
	</tr>
<?
endforeach;
?>
	<tr>
		<td valign="top"><?echo GetMessage('INTR_OPTION_TZ_SERVER')?>: </td>
		<td>
			<?echo GetMessage('INTR_OPTION_TZ_SERVER_GMT_DIFF')?>: <?echo date('O')?> (<?echo date('Z')?>)<br />
			<?echo GetMessage('INTR_OPTION_TZ_SERVER_DAYLIGHT_SAVING')?>: <?echo date('I') ? GetMessage('INTR_OPTION_TZ_SERVER_DAYLIGHT_SAVING_1') : GetMessage('INTR_OPTION_TZ_SERVER_DAYLIGHT_SAVING_0');?><br />
			<?echo GetMessage('INTR_OPTION_TZ_SERVER_CURRENT_RFC_2822')?>: <?echo date('r');?>
		</td>
	</tr>

	<tr class="heading">
		<td colspan="2"><?echo GetMessage('INTR_OPTION_PATHS')?></td>
	</tr>
	<tr>
		<td colspan="2">
<?
$childTabControl_1->Begin();
foreach ($arSites as $SITE_ID => $SITE):
	$childTabControl_1->BeginNextTab();
?>
	<table width="75%" align="center">
	<tr>
		<td valign="top" align="right"><?echo GetMessage('INTR_OPTION_PATHS_USER')?>: </td>
		<td><input type="text" name="path_user_<?=$SITE_ID?>" value="<?echo htmlspecialcharsbx($path_user[$SITE_ID])?>" size="50" /></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?echo GetMessage('INTR_OPTION_PATHS_TASK_USER')?>: </td>
		<td><input type="text" name="path_task_user_<?=$SITE_ID?>" value="<?echo htmlspecialcharsbx($path_task_user[$SITE_ID])?>" size="50" /></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?echo GetMessage('INTR_OPTION_PATHS_TASK_USER_ENTRY')?>: </td>
		<td><input type="text" name="path_task_user_entry_<?=$SITE_ID?>" value="<?echo htmlspecialcharsbx($path_task_user_entry[$SITE_ID])?>" size="50" /></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?echo GetMessage('INTR_OPTION_PATHS_TASK_GROUP')?>: </td>
		<td><input type="text" name="path_task_group_<?=$SITE_ID?>" value="<?echo htmlspecialcharsbx($path_task_group[$SITE_ID])?>" size="50" /></td>
	</tr>
	<tr>
		<td valign="top" align="right"><?echo GetMessage('INTR_OPTION_PATHS_TASK_GROUP_ENTRY')?>: </td>
		<td><input type="text" name="path_task_group_entry_<?=$SITE_ID?>" value="<?echo htmlspecialcharsbx($path_task_group_entry[$SITE_ID])?>" size="50" /></td>
	</tr>
	</table>
<?
endforeach;
$childTabControl_1->End();
?>
		</td>
	</tr>

<?$tabControl->BeginNextTab();?>
<?/*	<tr>
		<td><?echo GetMessage("INTR_OPTION_SEARCH_USER_URL");?>:</td><td><input type="text" size="47" name="search_user_url" value="<?echo htmlspecialcharsbx(COption::GetOptionString("intranet", "search_user_url"))?>"></td>
	</tr>*/?>
	<tr class="heading">
		<td colspan="2"><?echo GetMessage("INTR_OPTION_SEARCH_CONVERTERS");?></td>
	</tr>
	<?if(!function_exists("exec")):?>
	<tr>
		<td colspan="2"><?echo GetMessage("INTR_OPTION_SEARCH_CONVERTERS_WARNING");?></td>
	</tr>
	<?endif?>
	<?if(!function_exists("zip_open")):?>
	<tr>
		<td colspan="2"><?echo GetMessage("INTR_OPTION_SEARCH_ZIP_EXTENTION_WARNING");?></td>
	</tr>
	<?endif?>
	<tr>
	<td colspan="2">
		<table border="0" cellspacing="0" cellpadding="0" class="internal" align="center">
		<tr class="heading">
			<td><?echo GetMessage("INTR_OPTION_SEARCH_CONVERTER_EXTENSION");?></td>
			<td><?echo GetMessage("INTR_OPTION_SEARCH_CONVERTER_PROGRAM");?></td>
			<td><?echo GetMessage("INTR_OPTION_SEARCH_CONVERTER_DIRECTORY");?></td>
		</tr>
		<?$arExt = explode(",", COption::GetOptionString("intranet", "search_file_extensions"));
		$i = 0;
		foreach($arExt as $ext):?>
			<tr>
				<td><input type="text" size="5" name="search_file_extension_<?echo $i?>" value="<?echo htmlspecialcharsbx($ext)?>"></td>
				<td><input type="text" size="50" name="search_file_extension_exe_<?echo $i?>" value="<?echo htmlspecialcharsbx(COption::GetOptionString("intranet", "search_file_extension_exe_".$ext))?>"></td>
				<td><input type="text" size="25" name="search_file_extension_cd_<?echo $i?>" value="<?echo htmlspecialcharsbx(COption::GetOptionString("intranet", "search_file_extension_cd_".$ext))?>"></td>
			</tr>
		<?
		$i++;
		endforeach;
		for($j = 0; $j < 5; $j++):?>
			<tr>
				<td><input type="text" size="5" name="search_file_extension_<?echo $i?>" value=""></td>
				<td><input type="text" size="50" name="search_file_extension_exe_<?echo $i?>" value=""></td>
				<td><input type="text" size="25" name="search_file_extension_cd_<?echo $i?>" value=""></td>
			</tr>
		<?
		$i++;
		endfor?>
		</table>
	</td>
	</tr>
<?$tabControl->Buttons();?>
	<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" />
	<input type="submit" name="Apply" value="<?=GetMessage("MAIN_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("INTR_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
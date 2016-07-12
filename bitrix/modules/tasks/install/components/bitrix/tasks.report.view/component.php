<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('tasks', 'intranet', 'socialnetwork');

foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(GetMessage("F_NO_MODULE"));
		return 0;
	}
}

if (strlen($arParams["NAME_TEMPLATE"]) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();

$arResult['IS_HEAD_OF_DEPT'] = false;
$IBlockID = COption::GetOptionInt('intranet', 'iblock_structure', 0);
$userID = is_object($USER) ? intval($USER->GetID()) : 0;
$rsSections = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $IBlockID, "UF_HEAD" => $userID, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "N"), false, array('UF_HEAD'));
while ($arSection = $rsSections->Fetch())
{
	$arResult['IS_HEAD_OF_DEPT'] = true;
	break;
}

if (!isset($_GET['select_my_tasks']) && !isset($_GET['select_depts_tasks']) && !isset($_GET['select_group_tasks']))
{
	// tasks owners filter by default
	if (isset($arParams['GROUP_ID']))
	{
		// group tasks
		$_GET['select_group_tasks'] = 1;
	}
	else
	{
		// own tasks
		$_GET['select_my_tasks'] = 1;

		// depts tasks if head of dept
		if ($arResult['IS_HEAD_OF_DEPT'])
		{
			$_GET['select_depts_tasks'] = 1;
		}
	}
}




$this->IncludeComponentTemplate();

?>
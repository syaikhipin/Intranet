<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$currentUserID = $arResult['USER_ID'] = $arParams['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$userPerms->IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;
$arResult['RUBRIC'] = array('ENABLED' => false);

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && strtoupper($_GET['PAGING']) === 'Y';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_GET['SEARCH']) && strtoupper($_GET['SEARCH']) === 'Y';
$arResult['SEARCH_VALUE'] = '';

if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$entityTypeID = $arResult['ENTITY_TYPE_ID'] = isset($_GET['entity_type_id']) ? intval($_GET['entity_type_id']) : 0;
$entityID = $arResult['ENTITY_ID'] = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : 0;

$arParams['ACTIVITY_SHOW_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_SHOW_URL_TEMPLATE']) ? $arParams['ACTIVITY_SHOW_URL_TEMPLATE'] : '';
$arParams['TASK_SHOW_URL_TEMPLATE'] =  isset($arParams['TASK_SHOW_URL_TEMPLATE']) ? $arParams['TASK_SHOW_URL_TEMPLATE'] : '';
//$arParams['CONTACT_SHOW_URL_TEMPLATE'] =  isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
//$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
//$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_activity_list';
}
$arResult['UID'] = $arParams['UID'];

$arResult['FILTER'] = array(
	array('id' => 'SUBJECT'),
	array('id' => 'COMPLETED'),
	array('id' => 'RESPONSIBLE_ID')
);

$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('M_CRM_ACTIVITY_LIST_PRESET_MY'), 'fields' => array('RESPONSIBLE_ID' => $currentUserID)),
		'filter_my_not_completed' => array('name' => GetMessage('M_CRM_ACTIVITY_LIST_PRESET_MY_NOT_COMPLETED'), 'fields' => array('COMPLETED' => 'N', 'RESPONSIBLE_ID' => $currentUserID)),
		'filter_my_completed' => array('name' => GetMessage('M_CRM_ACTIVITY_LIST_PRESET_MY_COMPLETED'), 'fields' => array('COMPLETED' => 'Y', 'RESPONSIBLE_ID' => $currentUserID)),
		'filter_not_completed' => array('name' => GetMessage('M_CRM_ACTIVITY_LIST_PRESET_NOT_COMPLETED'), 'fields' => array('COMPLETED' => 'N')),
		'filter_completed' => array('name' => GetMessage('M_CRM_ACTIVITY_LIST_PRESET_COMPLETED'), 'fields' => array('COMPLETED' => 'Y'))
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('END_TIME' => 'ASC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID', 'TYPE_ID', 'DIRECTION',
	'OWNER_ID', 'OWNER_TYPE_ID',
	'SUBJECT', 'PRIORITY',
	'START_TIME', 'END_TIME',
	'COMPLETED', 'SETTINGS',
	'ASSOCIATED_ENTITY_ID'
);

$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = false;

$filter = $CGridOptions->GetFilter($arResult['FILTER']);
$arResult['GRID_FILTER_APPLIED'] = isset($filter['GRID_FILTER_APPLIED']) && $filter['GRID_FILTER_APPLIED'];
if($arResult['GRID_FILTER_APPLIED'])
{
	$filterID = $arResult['GRID_FILTER_ID'] = isset($filter['GRID_FILTER_ID']) ? $filter['GRID_FILTER_ID'] : '';
	$arResult['GRID_FILTER_NAME'] = isset($arResult['FILTER_PRESETS'][$filterID]) ? $arResult['FILTER_PRESETS'][$filterID]['name'] : '';
}
else
{
	$arResult['GRID_FILTER_ID'] = '';
	$arResult['GRID_FILTER_NAME'] = '';
}

if(isset($filter['SUBJECT']))
{
	if($filter['SUBJECT'] !== '')
	{
		$filter['%SUBJECT'] = $arResult['SEARCH_VALUE'] = $filter['SUBJECT'];
	}
	unset($filter['SUBJECT']);
}

if($entityTypeID > 0 && $entityID > 0)
{
	$arResult['RUBRIC']['ENABLED'] = true;

	$filter['BINDINGS'] = array(
		array(
			'OWNER_TYPE_ID' => $entityTypeID,
			'OWNER_ID' => $entityID
		)
	);
	$arResult['RUBRIC']['TITLE'] = CCrmOwnerType::GetCaption($entityTypeID, $entityID);
	$arResult['RUBRIC']['FILTER_PRESETS'] = array('clear_filter', 'filter_not_completed', 'filter_completed');
}

$arResult['ITEMS'] = array();

$dbRes = CCrmActivity::GetList($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

while($item = $dbRes->Fetch())
{
	$itemID = intval($item['ID']);
	$ownerID = intval($item['OWNER_ID']);
	$ownerTypeID = intval($item['OWNER_TYPE_ID']);

	//TODO: integrate permission check to CCrmActivity::GetList
	if(!CCrmActivity::CheckReadPermission($ownerTypeID, $ownerID))
	{
		continue;
	}

	CCrmMobileHelper::PrepareActivityItem($item, $arParams);

	$arResult['ITEMS'][] = &$item;
	unset($item);
}

if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
else
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', $arResult['PAGER_PARAM'])
	);
}

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);
$arResult['SERVICE_URL'] = SITE_DIR.'bitrix/components/bitrix/mobile.crm.activity.list/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
$arResult['IS_FILTERED'] = !empty($filter);

$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);




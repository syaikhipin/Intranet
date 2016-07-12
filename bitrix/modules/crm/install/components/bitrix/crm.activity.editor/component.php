<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

$arResult['CONTAINER_ID'] = isset($arParams['~CONTAINER_ID']) ? $arParams['~CONTAINER_ID'] : '';
$arResult['PREFIX'] = isset($arParams['~PREFIX']) ? $arParams['~PREFIX'] : 'crm_default';
$arResult['EDITOR_ID'] = isset($arParams['~EDITOR_ID']) ? $arParams['~EDITOR_ID'] : $arResult['PREFIX'].'_activity_editor';
$arResult['EDITOR_TYPE'] = isset($arParams['~EDITOR_TYPE']) ? $arParams['~EDITOR_TYPE'] : 'MIXED';
$arResult['EDITOR_ITEMS'] = isset($arParams['~EDITOR_ITEMS']) ? $arParams['~EDITOR_ITEMS'] : array();
$arResult['OWNER_TYPE'] = isset($arParams['~OWNER_TYPE']) ? $arParams['~OWNER_TYPE'] : '';
$arResult['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($arResult['OWNER_TYPE']);
$arResult['OWNER_ID'] = isset($arParams['~OWNER_ID']) ? $arParams['~OWNER_ID'] : 0;
$arResult['READ_ONLY'] = isset($arParams['~READ_ONLY']) ? (bool)$arParams['~READ_ONLY'] : false;
$arResult['ENABLE_UI'] = isset($arParams['~ENABLE_UI']) ? (bool)$arParams['~ENABLE_UI'] : true;
$arResult['ENABLE_TOOLBAR'] = isset($arParams['~ENABLE_TOOLBAR']) ? (bool)$arParams['~ENABLE_TOOLBAR'] : true;
$arResult['TOOLBAR_ID'] = isset($arParams['~TOOLBAR_ID']) ? $arParams['~TOOLBAR_ID'] : '';
$arResult['BUTTON_ID'] = isset($arParams['~BUTTON_ID']) ? $arParams['~BUTTON_ID'] : '';
$arResult['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['ENABLE_TASK_ADD'] = isset($arParams['~ENABLE_TASK_ADD']) ? (bool)$arParams['~ENABLE_TASK_ADD'] : IsModuleInstalled('tasks');
$arResult['ENABLE_CALENDAR_EVENT_ADD'] = isset($arParams['~ENABLE_CALENDAR_EVENT_ADD']) ? (bool)$arParams['~ENABLE_CALENDAR_EVENT_ADD'] : IsModuleInstalled('calendar');
$arResult['ENABLE_EMAIL_ADD'] = isset($arParams['~ENABLE_EMAIL_ADD']) ? (bool)$arParams['~ENABLE_EMAIL_ADD'] : IsModuleInstalled('subscribe');

$arResult['ENABLE_WEBDAV'] = IsModuleInstalled('webdav');
if(!$arResult['ENABLE_WEBDAV'])
{
	$arResult['WEBDAV_SELECT_URL'] = $arResult['WEBDAV_UPLOAD_URL'] = $arResult['WEBDAV_SHOW_URL'] = '';
}
else
{
	$webDavPaths = CCrmWebDavHelper::GetPaths();
	$arResult['WEBDAV_SELECT_URL'] = isset($webDavPaths['PATH_TO_FILES'])
		? $webDavPaths['PATH_TO_FILES'] : '';
	$arResult['WEBDAV_UPLOAD_URL'] = isset($webDavPaths['ELEMENT_UPLOAD_URL'])
		? $webDavPaths['ELEMENT_UPLOAD_URL'] : '';
	$arResult['WEBDAV_SHOW_URL'] = isset($webDavPaths['ELEMENT_SHOW_INLINE_URL'])
		? $webDavPaths['ELEMENT_SHOW_INLINE_URL'] : '';
}

$this->IncludeComponentTemplate();
<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'LEAD_IMPORT',
		'ACTIVE_ITEM_ID' => 'LEAD',
		'PATH_TO_COMPANY_LIST' => isset($arResult['PATH_TO_COMPANY_LIST']) ? $arResult['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arResult['PATH_TO_COMPANY_EDIT']) ? $arResult['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arResult['PATH_TO_CONTACT_LIST']) ? $arResult['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arResult['PATH_TO_CONTACT_EDIT']) ? $arResult['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arResult['PATH_TO_DEAL_LIST']) ? $arResult['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arResult['PATH_TO_DEAL_EDIT']) ? $arResult['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arResult['PATH_TO_LEAD_EDIT']) ? $arResult['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arResult['PATH_TO_REPORT_LIST']) ? $arResult['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arResult['PATH_TO_DEAL_FUNNEL']) ? $arResult['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arResult['PATH_TO_EVENT_LIST']) ? $arResult['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arResult['PATH_TO_PRODUCT_LIST']) ? $arResult['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);
$APPLICATION->IncludeComponent(
	'bitrix:crm.lead.menu', 
	'', 
	array(
		'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'],
		'PATH_TO_LEAD_SHOW' => $arResult['PATH_TO_LEAD_SHOW'],
		'PATH_TO_LEAD_EDIT' => $arResult['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_IMPORT' => $arResult['PATH_TO_LEAD_IMPORT'],	
		'ELEMENT_ID' => $arResult['VARIABLES']['lead_id'],
		'TYPE' => 'import'
	),
	$component
);?>

<?
$APPLICATION->IncludeComponent(
	'bitrix:crm.lead.import', 
	'', 
	array(
		'PATH_TO_LEAD_LIST' => $arResult['PATH_TO_LEAD_LIST'],
		'PATH_TO_LEAD_CONVERT' => $arResult['PATH_TO_LEAD_IMPORT'],
		'ELEMENT_ID' => $arResult['VARIABLES']['lead_id']
	),
	$component
);?>
<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
// Looking for 'tab_1' (Only single tab is supported).
$mainTab = null;
foreach($arResult['TABS'] as $tab):
	if($tab['id'] !== 'tab_1')
		continue;

	$mainTab = $tab;
	break;
endforeach;

//Take first tab if tab_1 is not found
if(!$mainTab):
	foreach($arResult['TABS'] as $tab):
		$mainTab = $tab;
		break;
	endforeach;
endif;

if(!($mainTab && isset($mainTab['fields']) && is_array($mainTab['fields'])))
	return;

$arEmphasizedFields = array();
$arEmphasizedHeaders = isset($arParams['EMPHASIZED_HEADERS']) ? $arParams['EMPHASIZED_HEADERS'] : array();
if(!empty($arEmphasizedHeaders))
{
	foreach($mainTab['fields'] as $k => &$field):
		if(in_array($field['id'], $arEmphasizedHeaders)):
			$arEmphasizedFields[$k] = $field;
			unset($mainTab['fields'][$k]);

		endif;
	endforeach;
	unset($field);
}
$hasRequiredFields = false;

?><div class="bx-interface-form bx-crm-edit-form"><?
if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div class="bx-crm-edit-form-menu-wrapper">
	<a href="javascript:void(0)" onclick="bxForm_<?=$arParams["FORM_ID"]?>.menu.ShowMenu(this, bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu);" title="<?=htmlspecialcharsbx(GetMessage("interface_form_settings"))?>" class="bx-context-button bx-form-menu"><span></span></a>
</div><?
endif;
?><script type="text/javascript">
	var bxForm_<?=$arParams['FORM_ID']?> = null;
</script><?
if($arParams['SHOW_FORM_TAG']):
?><form name="form_<?=$arParams['FORM_ID']?>" id="form_<?=$arParams["FORM_ID"]?>" action="<?=POST_FORM_ACTION_URI?>" method="POST" enctype="multipart/form-data"><?
	echo bitrix_sessid_post();
	?><input type="hidden" id="<?=$arParams["FORM_ID"]?>_active_tab" name="<?=$arParams["FORM_ID"]?>_active_tab" value="<?=htmlspecialcharsbx($arResult["SELECTED_TAB"])?>"><?
endif;

$arUserSearchFields = array();

if(count($arEmphasizedFields) > 0):
?><div class="webform-round-corners webform-main-fields">
	<div class="webform-corners-top">
		<div class="webform-left-corner"></div>
		<div class="webform-right-corner"></div>
	</div><?
	foreach($arEmphasizedFields as &$field):
		$required = isset($field['required']) && $field['required'] === true;
		if($required && !$hasRequiredFields)
			$hasRequiredFields = true;

?><div class="webform-content">
	<div class="bx-crm-edit-title-label"><?if($required):?><span class="required">*</span><?endif; echo htmlspecialcharsEx($field['name'])?></div>
	<div class="bx-crm-edit-title-wrapper">
		<input type="text" class="bx-crm-edit-title" name="<?=htmlspecialcharsbx($field["id"])?>" value="<?=isset($field['value']) ? htmlspecialcharsbx($field['value']) : ''?>"/>
	</div>
</div><?
	endforeach;
	unset($field);
?></div><?
endif;

$arSections = array();
$sectionIndex = -1;
foreach($mainTab['fields'] as &$field):
	if(!is_array($field))
		continue;

	if($field['type'] === 'section'):
		$arSections[] = array(
			'SECTION_FIELD' => $field,
			'SECTION_ID' => $field['id'],
			'SECTION_NAME' => $field['name'],
			'FIELDS' => array()
		);
		$sectionIndex++;
		continue;
	endif;

	//Skip empty BP params
	if(strpos($field['id'], 'BP_PARAMETERS') === 0 && $field['value'] === '')
	{
		continue;
	}

	if($sectionIndex < 0):
		$arSections[] = array(
			'SECTION_FIELD' => null,
			'SECTION_ID' => '',
			'SECTION_NAME' => '',
			'FIELDS' => array()
		);
		$sectionIndex = 0;
	endif;

	$arSections[$sectionIndex]['FIELDS'][] = $field;
endforeach;
unset($field);

$fieldCount = 0;
foreach($arSections as &$arSection):
	if(empty($arSection['FIELDS']))
		continue;
	?><div class="bx-crm-edit-content-block">
		<div class="bx-crm-edit-content-block-title"><?=htmlspecialcharsbx($arSection['SECTION_NAME'])?></div><?

	foreach($arSection['FIELDS'] as &$field):
		//default attributes
		if(!is_array($field['params']))
			$field['params'] = array();

		if($field['type'] == '' || $field['type'] == 'text')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '30';
		}
		elseif($field['type'] == 'textarea')
		{
			if($field['params']['cols'] == '')
				$field['params']['cols'] = '40';

			if($field['params']['rows'] == '')
				$field['params']['rows'] = '3';
		}
		elseif($field['type'] == 'date')
		{
			if($field['params']['size'] == '')
				$field['params']['size'] = '10';
		}

		$params = '';
		if(is_array($field['params']) && $field['type'] <> 'file')
			foreach($field['params'] as $p=>$v)
				$params .= ' '.$p.'="'.$v.'"';

		$val = isset($field['value']) ? $field['value'] : $arParams['~DATA'][$field['id']];

		if($field['type'] === 'vertical_container'):
			if($fieldCount > 0):
				?><div class="bx-crm-edit-content-separator"></div><?
			endif;
			?><div class="bx-crm-edit-content-block-vertical-element">
			<div class="bx-crm-edit-content-block-vertical-element-name"><?if($required):?><span class="required">*</span><?endif; echo htmlspecialcharsEx($field['name'])?></div><?
			if(isset($field['value'])):
				?><div class="bx-crm-edit-content-block-vertical-element-wrapper"><?=$val?></div><?
			endif;
			?></div><?
			$fieldCount++;
			continue;
		elseif($field['type'] === 'vertical_checkbox'):
			if($fieldCount > 0):
				?><div class="bx-crm-edit-content-separator"></div><?
			endif;
			?><div class="bx-crm-edit-content-block-checkbox">
			<input type="hidden" name="<?=$field['id']?>" value="N" />
			<input type="checkbox" class="bx-crm-edit-content-checkbox" name="<?=$field['id']?>" value="Y" <?=(($val === true || $val === 'Y')? ' checked':'')?><?=$params?>/>
			<div class="bx-crm-edit-content-checkbox-label"><?if($required):?><span class="required">*</span><?endif; echo htmlspecialcharsEx($field['name'])?></div>
			<div class="bx-crm-edit-content-checkbox-description"><?= isset($field['title']) ? htmlspecialcharsEx($field['title']) : '' ?></div>
		</div><?
			$fieldCount++;
			continue;
		endif;

		$isWide = $field['colspan'] === true;

		?><div class="<?=$isWide ? 'bx-crm-edit-content-block-wide-element' : 'bx-crm-edit-content-block-element'?>"><?

		if(!$isWide):
			$required = isset($field['required']) && $field['required'] === true;
			if($required && !$hasRequiredFields)
				$hasRequiredFields = true;

			?><span class="bx-crm-edit-content-block-element-name"><?if($required):?><span class="required">*</span><?endif; echo htmlspecialcharsEx($field['name'])?>:</span><?
		endif;

		switch($field['type']):
			case 'label':
				echo '<div class="crm-fld-block-readonly">', $val, '</div>';
				break;
			case 'custom':
				$isUserField = strpos($field['id'], 'UF_') === 0;
				$wrap = isset($field['wrap']) && $field['wrap'] === true;
				if($isUserField):
					?><div class="bx-crm-edit-user-field"><?
				elseif($wrap):
					?><div class="bx-crm-edit-field"><?
				endif;

				echo $val;
				if($isUserField || $wrap):
					?></div><?
				endif;
				break;
			case 'checkbox':
				?><input type="hidden" name="<?=$field["id"]?>" value="N">
				<input type="checkbox" name="<?=$field["id"]?>" value="Y"<?=($val == "Y"? ' checked':'')?><?=$params?>><?
				break;
			case 'textarea':
				?><textarea class="bx-crm-edit-text-area" name="<?=$field["id"]?>"<?=$params?>><?=$val?></textarea><?
				break;
			case 'list':
				?><select class="bx-crm-edit-input" name="<?=$field["id"]?>"<?=$params?>><?
					if(is_array($field["items"])):
						if(!is_array($val))
							$val = array($val);
						foreach($field["items"] as $k=>$v):
							?><option value="<?=htmlspecialcharsbx($k)?>"<?=(in_array($k, $val)? ' selected':'')?>><?=htmlspecialcharsEx($v)?></option><?
						endforeach;
					endif;
					?></select><?
				break;
			case 'file':
				?><div class="bx-crm-edit-file-field"><?
					$arDefParams = array("iMaxW"=>150, "iMaxH"=>150, "sParams"=>"border=0", "strImageUrl"=>"", "bPopup"=>true, "sPopupTitle"=>false, "size"=>20);
					foreach($arDefParams as $k=>$v)
						if(!array_key_exists($k, $field["params"]))
							$field["params"][$k] = $v;

					echo CFile::InputFile($field["id"], $field["params"]["size"], $val);
					if($val <> '')
						echo '<br>'.CFile::ShowImage($val, $field["params"]["iMaxW"], $field["params"]["iMaxH"], $field["params"]["sParams"], $field["params"]["strImageUrl"], $field["params"]["bPopup"], $field["params"]["sPopupTitle"]);
					?></div><?
				break;
			case 'date':
				$APPLICATION->IncludeComponent(
					"bitrix:main.calendar",
					"",
					array(
						"SHOW_INPUT"=>"Y",
						"INPUT_NAME"=>$field["id"],
						"INPUT_VALUE"=>$val,
						"INPUT_ADDITIONAL_ATTR"=>$params,
						"SHOW_TIME" => 'Y'
					),
					$component,
					array("HIDE_ICONS"=>true)
				);
				break;
			case 'date_link':
				$dataID = "{$arParams['FORM_ID']}_{$field['id']}_DATA";
				$viewID = "{$arParams['FORM_ID']}_{$field['id']}_VIEW";
				?><span id="<?=htmlspecialcharsbx($viewID)?>" class="bx-crm-edit-datetime-link"><?=htmlspecialcharsEx($val)?></span>
				<input id="<?=htmlspecialcharsbx($dataID)?>" type="hidden" name="<?=htmlspecialcharsbx($field['id'])?>" value="<?=htmlspecialcharsbx($val)?>" <?=$params?>>
				<script type="text/javascript">BX.ready(function(){ BX.CrmDateLinkField.create(BX('<?=CUtil::addslashes($dataID)?>'), BX('<?=CUtil::addslashes($viewID)?>'), { showTime: false }); });</script><?
				break;
			case 'intranet_user_search':
				$params = isset($field['componentParams']) ? $field['componentParams'] : array();
				if(!empty($params)):
					$rsUser = CUser::GetByID($val);
					if($arUser = $rsUser->Fetch()):
						$params['USER'] = $arUser;
					endif;
					?><input type="text" class="bx-crm-edit-input" name="<?=htmlspecialcharsbx($params['SEARCH_INPUT_NAME'])?>">
				<input type="hidden" name="<?=htmlspecialcharsbx($params['INPUT_NAME'])?>" value="<?=htmlspecialcharsbx($val)?>"><?
					$arUserSearchFields[] = $params;
					$APPLICATION->IncludeComponent(
						'bitrix:intranet.user.selector.new',
						'',
						array(
							'MULTIPLE' => 'N',
							'NAME' => $params['NAME'],
							'INPUT_NAME' => $params['SEARCH_INPUT_NAME'],
							'POPUP' => 'Y',
							'SITE_ID' => SITE_ID,
							'NAME_TEMPLATE' => $params['NAME_TEMPLATE']
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
				endif;
				break;
			case 'crm_entity_selector':
				CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/crm.js');
				$params = isset($field['componentParams']) ? $field['componentParams'] : array();
				if(!empty($params)):
					$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
					$entityID = isset($params['INPUT_VALUE']) ? intval($params['INPUT_VALUE']) : 0;
					$editorID = "{$arParams['FORM_ID']}_{$field['id']}";
					$containerID = "{$arParams['FORM_ID']}_FIELD_CONTAINER_{$field['id']}";
					$selectorID = "{$arParams['FORM_ID']}_ENTITY_SELECTOR_{$field['id']}";
					$changeButtonID = "{$arParams['FORM_ID']}_CHANGE_BTN_{$field['id']}";
					$dataInputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : $field['id'];
					$dataInputID = "{$arParams['FORM_ID']}_DATA_INPUT_{$dataInputName}";
					$newDataInputName = isset($params['NEW_INPUT_NAME']) ? $params['NEW_INPUT_NAME'] : '';
					$newDataInputID = $newDataInputName !== '' ? "{$arParams['FORM_ID']}_NEW_DATA_INPUT_{$dataInputName}" : '';
					$entityInfo = CCrmEntitySelectorHelper::PrepareEntityInfo($entityType, $entityID);
					?><div id="<?=htmlspecialcharsbx($containerID)?>" class="bx-crm-edit-crm-entity-field">
					<div class="bx-crm-entity-info-wrapper"><?
						if($entityID > 0):
							?><a href="<?=htmlspecialcharsbx($entityInfo['URL'])?>" target="_blank" class="bx-crm-entity-info-link"><?=htmlspecialcharsEx($entityInfo['TITLE'])?></a><span class="crm-element-item-delete"></span><?
						endif;
						?></div>
					<input type="hidden" id="<?=htmlspecialcharsbx($dataInputID)?>" name="<?=htmlspecialcharsbx($dataInputName)?>" value="<?=htmlspecialcharsbx($entityID)?>" /><?
					if($newDataInputName !== ''):
					?><input type="hidden" id="<?=htmlspecialcharsbx($newDataInputID)?>" name="<?=htmlspecialcharsbx($newDataInputName)?>" value="" /><?
					endif;
					?><div class="bx-crm-entity-buttons-wrapper">
					<span id="<?=htmlspecialcharsbx($changeButtonID)?>" class="bx-crm-edit-crm-entity-change"><?= htmlspecialcharsbx(GetMessage('intarface_form_select'))?></span><?
					if($newDataInputName !== ''):
					?> <span class="bx-crm-edit-crm-entity-add"><?=htmlspecialcharsEx(GetMessage('interface_form_add_new_entity'))?></span><?
					endif;
				?></div>
				</div><?
					$serviceUrl = '';
					$actionName = '';
					$dialogSettings = array(
						'addButtonName' => GetMessage('interface_form_add_dialog_btn_add'),
						'cancelButtonName' => GetMessage('interface_form_cancel')
					);
					if($entityType === 'CONTACT')
					{
						$serviceUrl = '/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
						$actionName = 'SAVE_CONTACT';

						$dialogSettings['title'] = GetMessage('interface_form_add_contact_dlg_title');
						$dialogSettings['lastNameTitle'] = GetMessage('interface_form_add_contact_fld_last_name');
						$dialogSettings['nameTitle'] = GetMessage('interface_form_add_contact_fld_name');
						$dialogSettings['secondNameTitle'] = GetMessage('interface_form_add_contact_fld_second_name');
						$dialogSettings['emailTitle'] = GetMessage('interface_form_add_contact_fld_email');
						$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_contact_fld_phone');
					}
					elseif($entityType === 'COMPANY')
					{
						$serviceUrl = '/bitrix/components/bitrix/crm.company.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
						$actionName = 'SAVE_COMPANY';

						$dialogSettings['title'] = GetMessage('interface_form_add_company_dlg_title');
						$dialogSettings['titleTitle'] = GetMessage('interface_form_add_company_fld_title_name');
						$dialogSettings['companyTypeTitle'] = GetMessage('interface_form_add_conpany_fld_company_type');
						$dialogSettings['industryTitle'] = GetMessage('interface_form_add_company_fld_industry');
						$dialogSettings['emailTitle'] = GetMessage('interface_form_add_conpany_fld_email');
						$dialogSettings['phoneTitle'] = GetMessage('interface_form_add_company_fld_phone');
						$dialogSettings['addressLegalTitle'] = GetMessage('interface_form_add_company_fld_address_legal');
						$dialogSettings['companyTypeItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('COMPANY_TYPE'));
						$dialogSettings['industryItems'] = CCrmEntitySelectorHelper::PrepareListItems(CCrmStatus::GetStatusList('INDUSTRY'));
					}
					?><script type="text/javascript">
					BX.ready(
							function()
							{
								var entitySelectorId = CRM.Set(
										BX('<?=CUtil::JSEscape($changeButtonID) ?>'),
										'<?=CUtil::JSEscape($selectorID)?>',
										'',
									<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PreparePopupItems($entityType, false, isset($params['NAME_TEMPLATE']) ? $params['NAME_TEMPLATE'] : ''))?>,
										false,
										false,
										['<?=CUtil::JSEscape(strtolower($entityType))?>'],
									<?=CUtil::PhpToJsObject(CCrmEntitySelectorHelper::PrepareCommonMessages())?>,
										true
								);

								BX.CrmEntityEditor.messages =
								{
									'unknownError': '<?=GetMessageJS('interface_form_ajax_unknown_error')?>'
								};

								BX.CrmEntityEditor.create(
										'<?=CUtil::JSEscape($editorID)?>',
										{
											'typeName': '<?=CUtil::JSEscape($entityType)?>',
											'containerId': '<?=CUtil::JSEscape($containerID)?>',
											'dataInputId': '<?=CUtil::JSEscape($dataInputID)?>',
											'newDataInputId': '<?=CUtil::JSEscape($newDataInputID)?>',
											'entitySelectorId': entitySelectorId,
											'serviceUrl': '<?= CUtil::JSEscape($serviceUrl) ?>',
											'actionName': '<?= CUtil::JSEscape($actionName) ?>',
											'dialog': <?=CUtil::PhpToJSObject($dialogSettings)?>
										}
								);
							}
					);
				</script><?
				endif;
				break;
			default:
				?><input type="text" class="bx-crm-edit-input" name="<?=$field["id"]?>" value="<?=htmlspecialcharsbx($val)?>"<?=$params?>><?
				break;
		endswitch;
		$fieldCount++;
		?></div><?
	endforeach;
	unset($field);
	?></div><?
endforeach;
unset($arSection);

$arFieldSets = isset($arParams['FIELD_SETS']) ? $arParams['FIELD_SETS'] : array();
if(!empty($arFieldSets)):
	foreach($arFieldSets as &$arFieldSet):
		$html = isset($arFieldSet['HTML']) ? $arFieldSet['HTML'] : '';
		if($html === '')
			continue;
		?><div class="bx-crm-view-fieldset">
			<h2 class="bx-crm-view-fieldset-title"><?=isset($arFieldSet['NAME']) ? htmlspecialcharsbx($arFieldSet['NAME']) : ''?></h2>
			<div class="bx-crm-view-fieldset-content">
				<table class="bx-crm-view-fieldset-content-table">
					<tbody>
					<tr>
						<td class="bx-field-value"><?=$html?></td>
					</tr>
				</tbody>
			</table>
		</div>
		</div><?
	endforeach;
	unset($arFieldSet);
endif;

if(isset($arParams['~BUTTONS'])):
	if($arParams['~BUTTONS']['standard_buttons'] !== false):
		?><div class="webform-buttons ">
			<span class="webform-button webform-button-create">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndView" value="<?=htmlspecialcharsbx(GetMessage('interface_form_save_and_view'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_save_and_view_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		if(isset($arParams['IS_NEW']) && $arParams['IS_NEW'] === true):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="saveAndAdd" value="<?=htmlspecialcharsbx(GetMessage('interface_form_save_and_add'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_save_and_add_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		else:
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="submit" name="apply" value="<?=htmlspecialcharsbx(GetMessage('interface_form_apply'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_apply_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		if(isset($arParams['~BUTTONS']['back_url']) && $arParams['~BUTTONS']['back_url'] !== ''):
			?><span class="webform-button">
				<span class="webform-button-left"></span>
				<input class="webform-button-text" type="button" name="cancel" onclick="window.location='<?=CUtil::JSEscape($arParams['~BUTTONS']['back_url'])?>'" value="<?= htmlspecialcharsbx(GetMessage('interface_form_cancel'))?>" title="<?= htmlspecialcharsbx(GetMessage('interface_form_cancel_title'))?>" />
				<span class="webform-button-right"></span>
			</span><?
		endif;
		?></div><?
	endif;
	if(isset($arParams['~BUTTONS']['custom_html'])):
		echo $arParams['~BUTTONS']['custom_html'];
	endif;
endif;

if($arParams['SHOW_FORM_TAG']):
	?></form><?
endif;

if($GLOBALS['USER']->IsAuthorized() && $arParams["SHOW_SETTINGS"] == true):?>
<div style="display:none">

	<div id="form_settings_<?=$arParams["FORM_ID"]?>">
		<table width="100%">
			<tr class="section">
				<td colspan="2"><?echo GetMessage("interface_form_tabs")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<select style="min-width:150px;" name="tabs" size="10" ondblclick="this.form.tab_edit_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.OnSettingsChangeTab()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="tab_up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabEdit()"></div>
								<div style="margin-bottom:5px"><input type="button" name="tab_del_btn" value="<?echo GetMessage("intarface_form_del")?>" title="<?echo GetMessage("intarface_form_del_title")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.TabDelete()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr class="section">
				<td colspan="2"><?echo GetMessage("intarface_form_fields")?></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<table>
						<tr>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_available")?></div>
								<select style="min-width:150px;" name="all_fields" multiple size="12" ondblclick="this.form.add_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="add_btn" value="&gt;" title="<?echo GetMessage("intarface_form_add_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="del_btn" value="&lt;" title="<?echo GetMessage("intarface_form_del_field")?>" style="width:30px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsDelete()"></div>
							</td>
							<td style="background-image:none" nowrap>
								<div style="margin-bottom:5px"><?echo GetMessage("intarface_form_fields_on_tab")?></div>
								<select style="min-width:150px;" name="fields" multiple size="12" ondblclick="this.form.del_btn.onclick()" onchange="bxForm_<?=$arParams["FORM_ID"]?>.ProcessButtons()">
								</select>
							</td>
							<td style="background-image:none">
								<div style="margin-bottom:5px"><input type="button" name="up_btn" value="<?echo GetMessage("intarface_form_up")?>" title="<?echo GetMessage("intarface_form_up_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveUp()"></div>
								<div style="margin-bottom:5px"><input type="button" name="down_btn" value="<?echo GetMessage("intarface_form_up_down")?>" title="<?echo GetMessage("intarface_form_down_title")?>" style="width:80px;" disabled onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldsMoveDown()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_add_btn" value="<?echo GetMessage("intarface_form_add")?>" title="<?echo GetMessage("intarface_form_add_sect")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldAdd()"></div>
								<div style="margin-bottom:5px"><input type="button" name="field_edit_btn" value="<?echo GetMessage("intarface_form_edit")?>" title="<?echo GetMessage("intarface_form_edit_field")?>" style="width:80px;" onclick="bxForm_<?=$arParams["FORM_ID"]?>.FieldEdit()"></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>

</div><?
endif; //$GLOBALS['USER']->IsAuthorized()

$variables = array(
	"mess"=>array(
		"collapseTabs"=>GetMessage("interface_form_close_all"),
		"expandTabs"=>GetMessage("interface_form_show_all"),
		"settingsTitle"=>GetMessage("intarface_form_settings"),
		"settingsSave"=>GetMessage("interface_form_save"),
		"tabSettingsTitle"=>GetMessage("intarface_form_tab"),
		"tabSettingsSave"=>"OK",
		"tabSettingsName"=>GetMessage("intarface_form_tab_name"),
		"tabSettingsCaption"=>GetMessage("intarface_form_tab_title"),
		"fieldSettingsTitle"=>GetMessage("intarface_form_field"),
		"fieldSettingsName"=>GetMessage("intarface_form_field_name"),
		"sectSettingsTitle"=>GetMessage("intarface_form_sect"),
		"sectSettingsName"=>GetMessage("intarface_form_sect_name"),
	),
	"ajax"=>array(
		"AJAX_ID"=>$arParams["AJAX_ID"],
		"AJAX_OPTION_SHADOW"=>($arParams["AJAX_OPTION_SHADOW"] == "Y"),
	),
	"settingWndSize"=>CUtil::GetPopupSize("InterfaceFormSettingWnd"),
	"tabSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormTabSettingWnd", array('width'=>400, 'height'=>200)),
	"fieldSettingWndSize"=>CUtil::GetPopupSize("InterfaceFormFieldSettingWnd", array('width'=>400, 'height'=>150)),
	"component_path"=>$component->GetRelativePath(),
	"template_path"=>$this->GetFolder(),
	"sessid"=>bitrix_sessid(),
	"current_url"=>$APPLICATION->GetCurPageParam("", array("bxajaxid", "AJAX_CALL")),
	"GRID_ID"=>$arParams["THEME_GRID_ID"],
);

?><script type="text/javascript">
var formSettingsDialog<?=$arParams["FORM_ID"]?>;
bxForm_<?=$arParams["FORM_ID"]?> = new BxCrmInterfaceForm('<?=$arParams["FORM_ID"]?>', <?=CUtil::PhpToJsObject(array_keys($arResult["TABS"]))?>);
bxForm_<?=$arParams["FORM_ID"]?>.vars = <?=CUtil::PhpToJsObject($variables)?>;<?
	if($arParams["SHOW_SETTINGS"] == true):
		?>bxForm_<?=$arParams["FORM_ID"]?>.oTabsMeta = <?=CUtil::PhpToJsObject($arResult["TABS_META"])?>;
	bxForm_<?=$arParams["FORM_ID"]?>.oFields = <?=CUtil::PhpToJsObject($arResult["AVAILABLE_FIELDS"])?>;<?
	endif
	?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu = [];<?
	if($arParams["SHOW_SETTINGS"] == true):
		?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_settings"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_settings_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.ShowSettings()', 'DEFAULT':true, 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings'});<?
		if(!empty($arResult["OPTIONS"]["tabs"])):
			if($arResult["OPTIONS"]["settings_disabled"] == "Y"):
				?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_on"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_on_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.EnableSettings(true)', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings-on'});<?
			else:
				?>bxForm_<?=$arParams["FORM_ID"]?>.settingsMenu.push({'TEXT': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_off"))?>', 'TITLE': '<?=CUtil::JSEscape(GetMessage("intarface_form_mnu_off_title"))?>', 'ONCLICK': 'bxForm_<?=$arParams["FORM_ID"]?>.EnableSettings(false)', 'DISABLED':<?=($USER->IsAuthorized()? 'false':'true')?>, 'ICONCLASS':'form-settings-off'});<?
			endif;
		endif;
	endif;

	if($arResult["OPTIONS"]["expand_tabs"] == "Y"):
		?>BX.ready(function(){bxForm_<?=$arParams["FORM_ID"]?>.ToggleTabs(true);});<?
	endif;
?></script><?

?></div><!-- bx-interface-form --><?
if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;

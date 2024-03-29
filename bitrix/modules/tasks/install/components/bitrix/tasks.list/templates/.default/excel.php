<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/template.php');

$arExcelFields = array(
	"ID",
	"TITLE",
	"RESPONSIBLE_ID",
	"CREATED_BY",
	"CREATED_DATE",
	"REAL_STATUS",
	"PRIORITY",
	"START_DATE_PLAN",
	"END_DATE_PLAN",
	"DEADLINE",
	"DURATION_PLAN",
	"DURATION_FACT",
	"CLOSED_DATE",
	"MARK",
	"ADD_IN_REPORT",
	"GROUP_ID"
);

?>
<meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
<table border="1">
	<thead>
		<tr>
			<?php foreach($arExcelFields as $field):?>
				<th><?php echo GetMessage("TASKS_EXCEL_".$field)?></th>
			<?php endforeach?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($arResult["TASKS"] as $i => $arTask):?>
			<tr>
				<?php
					foreach ($arExcelFields as $field)
					{
						switch ($field)
						{
							case "RESPONSIBLE_ID":
							case "CREATED_BY":
								$rsUser = CUser::GetByID($arResult["TASKS"][$i][$field]);
								if ($arUser = $rsUser->GetNext())
								{
									$arResult["TASKS"][$i][$field] = tasksFormatNameShort($arUser["NAME"], $arUser["LAST_NAME"], $arUser["LOGIN"], $arUser["SECOND_NAME"], $arParams["NAME_TEMPLATE"]);
								}
								break;
							case "PRIORITY":
								$arResult["TASKS"][$i][$field] = GetMessage("TASKS_PRIORITY_".$arResult["TASKS"][$i][$field]);
								break;
							case "REAL_STATUS":
								$arResult["TASKS"][$i][$field] = GetMessage("TASKS_STATUS_".$arResult["TASKS"][$i][$field]);
								break;
							case "MARK":
								$arResult["TASKS"][$i][$field] = $arResult["TASKS"][$i][$field] ? GetMessage("TASKS_MARK_".$arResult["TASKS"][$i][$field]) : GetMessage("TASKS_MARK_NONE");
								break;
							case "ADD_IN_REPORT":
								$arResult["TASKS"][$i][$field] = $arResult["TASKS"][$i][$field] == "Y" ? GetMessage("TASKS_SIDEBAR_IN_REPORT_YES") : GetMessage("TASKS_SIDEBAR_IN_REPORT_NO");
								break;
							case "DURATION_PLAN":
								if ($arResult["TASKS"][$i][$field])
								{
									$arResult["TASKS"][$i][$field] = $arResult["TASKS"][$i][$field]." ".($arResult["TASKS"][$i]["DURATION_TYPE"] == "days" ? GetMessage("TASKS_DURATION_DAYS") : GetMessage("TASKS_DURATION_HOURS"));
								}
								else
								{
									$arResult["TASKS"][$i][$field] = "";
								}
								break;
							case "DURATION_FACT":
								if ($arResult["TASKS"][$i][$field])
								{
									$arResult["TASKS"][$i][$field] = floor($arResult["TASKS"][$i][$field] / 60).GetMessage("TASKS_ELAPSED_H")." ".($arResult["TASKS"][$i][$field] % 60).GetMessage("TASKS_ELAPSED_M");
								}
								else
								{
									$arResult["TASKS"][$i][$field] = "";
								}
								break;
							case "GROUP_ID":
								if ($arResult["TASKS"][$i][$field] && CSocNetGroup::CanUserViewGroup($USER->GetID(), $arResult["TASKS"][$i][$field]))
								{
									$arGroup = CSocNetGroup::GetByID($arResult["TASKS"][$i][$field]);
									if ($arGroup)
									{
										$arResult["TASKS"][$i][$field] = $arGroup["NAME"];
									}
								}
								if (!$arResult["TASKS"][$i][$field])
								{
									$arResult["TASKS"][$i][$field] = "";
								}
							default:
								;
							break;
						}
						echo '<td>'.$arResult["TASKS"][$i][$field].'</td>';
					}
				?>
			</tr>
		<?php endforeach;?>
	</tbody>
</table>
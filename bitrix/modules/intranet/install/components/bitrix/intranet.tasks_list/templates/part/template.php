<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
if(count($arResult["Tasks"]) <= 0)
	echo GetMessage("INTASK_LIST_EMPTY");
$bFirst = true;
if (Array_Key_Exists("Tasks", $arResult) && Is_Array($arResult["Tasks"]))
{
	foreach ($arResult["Tasks"] as $arTask)
	{
		if (!$bFirst)
		{
			?><div class="sonet-forum-line"></div><?
		}
		?>
		<span class="sonet-forum-post-date"><?
		if (StrLen($arTask["FIELDS"]["DATE_ACTIVE_FROM_PRINTABLE"]) > 0 && StrLen($arTask["FIELDS"]["DATE_ACTIVE_TO_PRINTABLE"]) > 0)
			echo $arTask["FIELDS"]["DATE_ACTIVE_FROM_PRINTABLE"]." - ".$arTask["FIELDS"]["DATE_ACTIVE_TO_PRINTABLE"];
		elseif (StrLen($arTask["FIELDS"]["DATE_ACTIVE_FROM_PRINTABLE"]) <= 0 && StrLen($arTask["FIELDS"]["DATE_ACTIVE_TO_PRINTABLE"]) > 0)
			echo Str_Replace("#DATE#", $arTask["FIELDS"]["DATE_ACTIVE_TO_PRINTABLE"], GetMessage("INTASK_TO_DATE_TLP"));
		elseif (StrLen($arTask["FIELDS"]["DATE_ACTIVE_FROM_PRINTABLE"]) > 0 && StrLen($arTask["FIELDS"]["DATE_ACTIVE_TO_PRINTABLE"]) <= 0)
			echo Str_Replace("#DATE#", $arTask["FIELDS"]["DATE_ACTIVE_FROM_PRINTABLE"], GetMessage("INTASK_FROM_DATE_TLP"));
		else
			echo GetMessage("INTASK_NO_DATE_TLP");
		?></span><br />
		<b><a href="<?=$arTask["VIEW_URL"]?>"><?echo $arTask["FIELDS"]["NAME_PRINTABLE"];?></a></b><br />

		<?if ($arParams["TASK_TYPE"] == "group"):?>
			<small><?=GetMessage("INTASK_TASKASSIGNEDTO")?>: <?=$arTask["FIELDS"]["TASKASSIGNEDTO_PRINTABLE"]?></small><br />
		<?endif;?>

		<span class="sonet-forum-post-info">
			<?=GetMessage("INTASK_TASKPRIORITY")?>:&nbsp;<?=$arTask["FIELDS"]["TASKPRIORITY_PRINTABLE"]?><br />
			<?=GetMessage("INTASK_TASKSTATUS")?>:&nbsp;<?=$arTask["FIELDS"]["TASKSTATUS_PRINTABLE"]?>
		</span>
		<?
		$bFirst = false;
	}
}
?>
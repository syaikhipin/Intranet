<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/translate/prolog.php");
$TRANS_RIGHT = $APPLICATION->GetGroupRight("translate");
if($TRANS_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/translate/include.php");
IncludeModuleLangFile(__FILE__);
define("HELP_FILE","translate_list.php");

/***************************************************************************
						GET | POST
***************************************************************************/
$strError = "";
$arDIFF = array();
$key_del = 0;

$file = Rel2Abs("/", $file);
if(strpos($file, "/bitrix/") !== 0 || strpos($file, "/lang/") === false || GetFileExtension($file) <> "php")
	$strError = GetMessage("trans_edit_err")."<br>";

if($strError == "")
{
	$aTabs = array(
		array("DIV" => "edit1", "TAB" => GetMessage("TRANS_TITLE"), "ICON" => "translate_edit", "TITLE" => GetMessage("TRANS_TITLE_TITLE")),
	);
	$tabControl = new CAdminTabControl("tabControl", $aTabs);

	if (strlen($show_error)>0) $ONLY_ERROR = "Y"; else $ONLY_ERROR = "N";

	// form a way to get back
	$chain = "";
	$arPath = array();
	$path_back = dirname($file);
	$arSlash = explode("/",$path_back);
	if (is_array($arSlash))
	{
		$arSlash_tmp = $arSlash;
		$lang_key = array_search("lang", $arSlash) + 1;
		unset($arSlash_tmp[$lang_key]);
		if ($lang_key==sizeof($arSlash)-1)
		{
			unset($arSlash[$lang_key]);
			$path_back = implode("/",$arSlash);
		}
		$i = 0;
		foreach($arSlash_tmp as $dir)
		{
			$i++;
			if ($i==1)
			{
				$chain .= "<a href=\"translate_list.php?lang=".LANGUAGE_ID."&path=/"."&".bitrix_sessid_get()."\" title=\"".GetMessage("TRANS_CHAIN_FOLDER_ROOT")."\">..</a> / ";
			}
			else
			{
				$arPath[] = htmlspecialcharsbx($dir);
				if ($i>2) $chain .= " / ";
				$chain .= "<a href=\"translate_list.php?lang=".LANGUAGE_ID."&path="."/".implode("/",$arPath)."/"."&".bitrix_sessid_get()."\" title=\"".GetMessage("TRANS_CHAIN_FOLDER")."\">".htmlspecialcharsbx($dir)."</a>";
			}
		}
	}

	$arTLangs = array();
	$arr = array();
	$arTLanguages = array();
	$ln = @CLanguage::GetList($o, $b, Array("ACTIVE"=>"Y"));
	while ($lnr = $ln->Fetch())
	{
		$arTLangs[] = $lnr["LID"];
		$arr["LID"] = $lnr["LID"];
		$arr["CHARSET"] = $lnr["CHARSET"];
		$arr["NAME"] = $lnr["NAME"];
		$arTLanguages[] = $arr;
	}

	$arLangFiles = array();
	$arFiles = array();
	foreach ($arTLangs as $lng)
	{
		if (strlen($file)>0)
		{
			$arSlash = explode("/",$file);
			if (is_array($arSlash))
			{
				$lang_key = array_search("lang", $arSlash) + 1;
				$arSlash[$lang_key] = $lng;
				$fn = implode("/",$arSlash);
				$arFiles[] = $fn;
				$arLangFiles[$lng] = $fn;
			}
		}
	}

	if(count($arFiles)>0)
	{
		// form the array for each file by language
		foreach ($arFiles as $fname)
		{
			$arKeys = array();
			$MESS_TRANS = array();
			$arSlash = explode("/",$fname);
			$lang_key = array_search("lang", $arSlash) + 1;
			$file_lang = $arSlash[$lang_key];
			
			if (in_array($file_lang, $arTLangs))
			{
				$MESS_tmp = $MESS;
				$MESS = array();
				if(file_exists($_SERVER["DOCUMENT_ROOT"].$fname))
					include($_SERVER["DOCUMENT_ROOT"].$fname);

				$file_name = str_replace("/".$file_lang."/", "/", $fname);
				//$file_name = str_replace(array("/ru/", "/de/", "/en/"), array("/", "/", "/"), $fname);

				$arFilesLng[$file_name][$file_lang] = array_keys($MESS);
				$arMESS[$file_lang] = $MESS;
				$MESS = $MESS_tmp;
			}
		}

		if (is_array($arFilesLng))
		{
			// calculate the sum and difference for file
			while (list($f, $arLns)=each($arFilesLng))
			{
				$arKEYS = array();

				while (list($ln, $arLn)=each($arLns))
				{
					foreach ($arLn as $lg)
						if (!in_array($lg, $arKEYS))
							$arKEYS[] = $lg;
				}

				$total = sizeof($arKEYS);
				// calculate the difference for each language
				reset($arLns);
				while (list($ln, $arLn)=each($arLns))
				{
					$arr = array();
					$diff_arr = array_diff($arKEYS, $arLn);
					$diff_arr_lang[$ln] = $diff_arr;
					$arr["TOTAL"] = $total;
					$diff = sizeof($diff_arr);
					$arr["DIFF"] = $diff;
					$arDIFF[$ln] = $arr;
				}
			}
		}
	}

	// gather in the array is that it is necessary to write to file
	if ($REQUEST_METHOD=="POST" && (strlen($save)>0 || strlen($apply)>0) && $TRANS_RIGHT=="W" && check_bitrix_sessid())
	{
		if (is_array($KEYS))
		{
			$arTEXT = array();
			foreach ($KEYS as $k)
			{
				$ms_key = $k;
				$ms_del = ${"DEL_".$k}=="Y" ? "Y" : "N";
				if (is_array($LANGS))
				{
					foreach ($LANGS as $lng)
					{
						$ms_lang = $lng;
						$ms_value = ${$k."_".$lng};
						$ms_value_prev = ${$k."_".$lng."_PREV"};
						if ($ms_del!="Y" && strlen($ms_value)>0)
						{
							$arTEXT[$arLangFiles[$ms_lang]][] = "\$MESS[\"".EscapePHPString($k)."\"] = \"".
								EscapePHPString(str_replace("\r", "", $ms_value))."\"";
						}
						elseif (strlen($ms_value_prev)>0)
						{
							$arTEXT[$arLangFiles[$ms_lang]][] = "";
						}
					}
				}
			}

			// collect all the variables and write to files
			while (list($fpath, $arM)=each($arTEXT))
			{
				$strContent = "";
				foreach ($arM as $M)
				{
					if (strlen($M)>0) $strContent .= "\n".$M.";";
				}
				if (!TR_BACKUP($fpath)) {
					$strError .= GetMessage("TR_CREATE_BACKUP_ERROR", array('%FILE%' => $fpath))."<br>\n";
				}
				else
				{
					if (strlen($strContent)>0)
					{
						RewriteFile($_SERVER["DOCUMENT_ROOT"].$fpath, "<?".$strContent."\n?".">");
					}
					else
					{
						if (file_exists($_SERVER["DOCUMENT_ROOT"].$fpath))
						{
							@chmod($_SERVER["DOCUMENT_ROOT"].$fpath, BX_FILE_PERMISSIONS);
							@unlink($_SERVER["DOCUMENT_ROOT"].$fpath);
						}
					}
				}
			}
			if (strlen($save)>0) LocalRedirect("translate_list.php?lang=".LANG."&path=".$path_back."&".bitrix_sessid_get());
			else LocalRedirect("translate_edit.php?lang=".LANG."&file=".urlencode($file)."&show_error=".$show_error);
		}
	}
}

$APPLICATION->SetTitle(GetMessage("TRANS_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($strError <> "")
{
	CAdminMessage::ShowMessage($strError);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}
/***************************************************************************
							HTML
****************************************************************************/
$aMenu = array();
$aMenu[] = Array(
	"TEXT"	=> GetMessage("TRANS_LIST"),
	"LINK"	=> "/bitrix/admin/translate_list.php?lang=".LANGUAGE_ID."&path=/".implode("/",$arPath)."/"."&".bitrix_sessid_get(),
	"TITLE"	=> GetMessage("TRANS_LIST_TITLE"),
	"ICON"	=> "btn_list"
	);

if ($ONLY_ERROR=="N"):
	$aMenu[] = array(
		"TEXT"	=> GetMessage("TRANS_SHOW_ONLY_ERROR"),
		"LINK"	=> "/bitrix/admin/translate_edit.php?file=".htmlspecialcharsbx($file)."&lang=".LANGUAGE_ID."&show_error=Y",
		"TITLE"	=> GetMessage("TRANS_SHOW_ONLY_ERROR_TITLE"),
		"ICON"	=> ""
		);
elseif ($ONLY_ERROR=="Y"):
	$aMenu[] = array(
		"TEXT"	=> GetMessage("TRANS_SHOW_ALL"),
		"LINK"	=> "/bitrix/admin/translate_edit.php?file=".htmlspecialcharsbx($file)."&lang=".LANGUAGE_ID,
		"TITLE"	=> GetMessage("TRANS_SHOW_ALL_TITLE"),
		"ICON"	=> ""
		);
endif;

$aMenu[] = array(
		"TEXT"	=> GetMessage("TR_FILE_SHOW"),
		"LINK"	=> "/bitrix/admin/translate_show_php.php?file=".htmlspecialcharsbx($file)."&lang=".LANGUAGE_ID,
		"TITLE"	=> GetMessage("TR_FILE_SHOW_TITLE"),
	);

$aMenu[] = array(
		"TEXT"	=> GetMessage("TR_FILE_EDIT"),
		"LINK"	=> "/bitrix/admin/translate_edit_php.php?file=".htmlspecialcharsbx($file)."&lang=".LANGUAGE_ID,
		"TITLE"	=> GetMessage("TR_FILE_EDIT_TITLE"),
	);

$context = new CAdminContextMenu($aMenu);
$context->Show();
?>
<p><?=$chain?></p>
<form name="form1" method="POST" action="<?=$APPLICATION->GetCurPage()?>?show_error=<?=htmlspecialcharsbx($show_error)?>&file=<?=htmlspecialcharsbx($file)?>&lang=<?=LANGUAGE_ID?>">
<?=bitrix_sessid_post()?>
<?
$tabControl->Begin();

$tabControl->BeginNextTab();
?>	<tr valign="top">
		<td width="100%" colspan="2">
			<table border="0" cellspacing="3" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="right" width="0%" nowrap colspan="2">
						<img src="/bitrix/images/1.gif" width="1" height="8"></td>
				</tr>
				<tr>
					<td valign="top" align="right" width="35%" nowrap><?echo GetMessage("TRANS_FILENAME")?></td>
					<td valign="top" align="left" width="65%" nowrap><b><?=htmlspecialcharsbx(basename($file))?></b></td>
				</tr>
				<tr>
					<td valign="top" align="right" nowrap>
						<?echo GetMessage("TRANS_TOTAL")?></td>
					<td valign="top" align="left" nowrap><?=$total?></td>
				<tr>
					<td valign="top" align="right" nowrap>
						<?echo GetMessage("TRANS_NOT_TRANS")?></td>
					<td valign="top" align="left" nowrap>
						<table border="0" cellspacing="0" cellpadding="0" width="0%" class="internal">
								<?
								$str1 = $str2 = "";
								if (is_array($arDIFF))
								{
									reset($arDIFF);
									while (list($ln, $arD)=each($arDIFF))
									{
										$str1 .= "<td width='".round(100/sizeof($arTLangs))."'% align='center'>".$ln."</td>";
										$str2 .= "<td align='right'>";
										$cl = (intval($arD["DIFF"])>0) ? "class='required'" : "";
										$str2 .= "&nbsp;<span ".$cl.">".$arD["DIFF"]."</span>&nbsp;</td>";
									}
								}
								?>
							<tr class="heading"><?=$str1?></tr>
							<tr><?=$str2?></tr>
						</table></td>
				</tr>
				<tr>
					<td colspan="2" valign="top" align="left" width="100%" nowrap>
						<table border="0" cellspacing="0" cellpadding="0" width="100%">
							<?
							reset($arTLanguages);
							while (list($j,$arLng)=each($arTLanguages)) :
								if (LANG_CHARSET == $arLng["CHARSET"] || $arLng["LID"]=="en") :
							?>
							<input type="hidden" name="LANGS[]" value="<?=$arLng["LID"]?>">
							<?
								endif;
							endwhile;
							if (is_array($arKEYS)) :
							while (list($i,$key)=each($arKEYS)) :
								$key_del++;
								$red = false;
								reset($diff_arr_lang);
								while (list($ln,$arDLang)=each($diff_arr_lang))
								{
									if (in_array($key, $arDLang))
									{
										reset($arTLanguages);
										while (list($j,$arLng)=each($arTLanguages))
										{
											if ($ln==$arLng["LID"])
											{
												if (LANG_CHARSET==$arLng["CHARSET"] || $arLng["LID"]=="en") $red = true;
											}
										}
									}
								}
							?><input type="hidden" name="KEYS[]" value="<?=$key?>"><?
							if (($ONLY_ERROR=="Y" && $red) || $ONLY_ERROR=="N") :
							?>
							<tr>
								<td colspan="3"><img src="/bitrix/images/1.gif" width="1" height="10"><hr><img src="/bitrix/images/1.gif" width="1" height="3"></td></tr>
							<tr>
								<td>ID:</td>
								<td><?
									if ($red) :
										?><span class="required"><b><?=$key?></b></span><?
									else :
										?><b><?=$key?></b><?
									endif;
									?><a name="<?=$key?>"></a></td>
								<td align="right"><?
								if ($TRANS_RIGHT<"W") $s = "disabled";
								?>&nbsp;<label for="DEL_<?=$key_del?>"><?=GetMessage("TRANS_DELETE")?></label><input type="checkbox" name="<?="DEL_".$key?>" value="Y" <?=$s?> id="<?='DEL_'.$key_del?>"></td>
							<tr>
								<td colspan="3"><img src="/bitrix/images/1.gif" width="1" height="5"></td></tr>
							<?
								reset($arTLanguages);

								$rows = "2";
								foreach($arTLanguages as $arLng)
									if(strpos($arMESS[$arLng["LID"]][$key], "\n")!==false)
										$rows = "10";

								reset($arTLanguages);
								while (list($j,$arLng)=each($arTLanguages)) :
									if (LANG_CHARSET==$arLng["CHARSET"] || $arLng["LID"]=="en") :

									if(isset(${$key."_".$arLng["LID"]}) && $key."_".$arLng["LID"] != $arMESS[$arLng["LID"]][$key])
										$valMsg = htmlspecialcharsbx(${$key."_".$arLng["LID"]});
									else
										$valMsg = htmlspecialcharsbx($arMESS[$arLng["LID"]][$key]);
							?>
							<tr>
								<td valign="top">[<?=$arLng["LID"]?>]&nbsp;<?=$arLng["NAME"]?>:&nbsp;</td>
								<td colspan="2">
									<input type="hidden" name="<?echo $key."_".$arLng["LID"]."_PREV"?>" value="<?=htmlspecialcharsbx($arMESS[$arLng["LID"]][$key])?>">
									<textarea cols="60" rows="3" rows="<?=$rows?>" name="<?echo $key."_".$arLng["LID"]?>" style="width:90%"><?=$valMsg?></textarea>
								</td>
							</tr>
							<?
									endif;
								endwhile;

							else : //if (($ONLY_ERROR=="Y" && $red) || $ONLY_ERROR=="N")

								reset($arTLanguages);
								while (list($j,$arLng)=each($arTLanguages)) :
									if (LANG_CHARSET==$arLng["CHARSET"] || $arLng["LID"]=="en") :
									?>
									<input type="hidden" name="<?echo $key."_".$arLng["LID"]."_PREV"?>" value="<?=htmlspecialcharsbx($arMESS[$arLng["LID"]][$key])?>">
									<input type="hidden" name="<?echo $key."_".$arLng["LID"]?>" value="<?=htmlspecialcharsbx($arMESS[$arLng["LID"]][$key])?>">
									<?
									endif;
								endwhile;

							endif; //if (($ONLY_ERROR=="Y" && $red) || $ONLY_ERROR=="N")

						endwhile;
						endif;
							?>

						</table></td>
					</td>
				</tr>
				<tr>
					<td valign="top" align="right" width="0%" nowrap colspan="2">
						<img src="/bitrix/images/1.gif" width="1" height="8"></td>
				</tr>
				<SCRIPT LANGUAGE="JavaScript">
				function SelectAllDelete(key)
				{
					var val = BX('all').checked;
					for(var i=1;i<=key;i++)
					{
						var ck = BX("DEL_"+i);
						if(ck && (ck.disabled != true))
							ck.checked = val;
					}
				}
				</SCRIPT>
				<tr>
					<td valign="top" align="right" width="0%" nowrap colspan="2"><b><label for="all"><?=GetMessage("TRANS_DELETE_ALL")?></label></b><input type="checkbox" name="all" id="all" value="" OnClick="SelectAllDelete('<?=$key_del?>');"<?if ($TRANS_RIGHT<"W") echo " disabled";?>></td>
				</tr>
			</table>
		</td>
	</tr>
<?$tabControl->Buttons(array("disabled" => ($TRANS_RIGHT<"W"), "back_url"=>"translate_list.php?lang=".LANGUAGE_ID."&path=".urlencode($path_back)."&".bitrix_sessid_get()));
$tabControl->End();
?>

</form>
<?require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>

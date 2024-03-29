<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) 
{
	echo("B_PROLOG_INCLUDED error!");
	die();
}

if(!CModule::IncludeModule("xdimport") || !CModule::IncludeModule("socialnetwork"))
{
	echo("Error loading modules!");
	return;
}

if (strlen($_POST["hash"]) > 0)
{
	$rsScheme = CXDILFScheme::GetList(
		array(), 
		array(
			"ACTIVE" => "Y", 
			"HASH" => $_POST["hash"]
		)
	);
	if ($arScheme = $rsScheme->Fetch())
	{
		if (
			strlen($_POST["title"]) > 0
			&& strlen($_POST["message"]) > 0
		)
		{
			if (XDI_DEBUG)
				CXDImport::WriteToLog("Successful POST request, scheme ID: ".$arScheme["ID"], "RXML");

			$arEventTmp = CSocNetLogTools::FindLogEventByID($arScheme["EVENT_ID"]);
			if (array_key_exists("REAL_EVENT_ID", $arEventTmp) && strlen($arEventTmp["REAL_EVENT_ID"]) > 0)
				$arScheme["EVENT_ID"] = $arEventTmp["REAL_EVENT_ID"];

			$strParams = CharsetConverter::ConvertCharset($_POST["params"], (CXDImport::DetectUTF8($_POST["params"]) ? "utf-8" : "windows-1251"), SITE_CHARSET);

			$arSonetFields = array(
				"SITE_ID" => $arScheme["LID"],
				"ENTITY_TYPE" => $arScheme["ENTITY_TYPE"],
				"ENTITY_ID" => $arScheme["ENTITY_ID"],
				"EVENT_ID" => $arScheme["EVENT_ID"],
				"ENABLE_COMMENTS" => $arScheme["ENABLE_COMMENTS"],
				"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"TITLE_TEMPLATE" => false,
				"TITLE" => CharsetConverter::ConvertCharset($_POST["title"], (CXDImport::DetectUTF8($_POST["title"]) ? "utf-8" : "windows-1251"), SITE_CHARSET),
				"MESSAGE" => CharsetConverter::ConvertCharset($_POST["message"], (CXDImport::DetectUTF8($_POST["message"]) ? "utf-8" : "windows-1251"), SITE_CHARSET),
				"TEXT_MESSAGE" => CharsetConverter::ConvertCharset($_POST["text_message"], (CXDImport::DetectUTF8($_POST["text_message"]) ? "utf-8" : "windows-1251"), SITE_CHARSET),
				"URL" => CharsetConverter::ConvertCharset($_POST["url"], (CXDImport::DetectUTF8($_POST["url"]) ? "utf-8" : "windows-1251"), SITE_CHARSET),
				"PARAMS" => (is_array($strParams) ? serialize($strParams) : $strParams),
				"MODULE_ID" => false,
				"CALLBACK_FUNC" => false
			);

			$logID = CSocNetLog::Add($arSonetFields, false);
			if (intval($logID) > 0)
			{
				$arUpdateFields = array(
					"TMP_ID" => $logID,
					"RATING_TYPE_ID" => "LOG_ENTRY",
					"RATING_ENTITY_ID" => $logID
				);
				CSocNetLog::Update($logID, $arUpdateFields);
				CXDILFScheme::SetSonetLogRights($logID, $arSonetFields["ENTITY_TYPE"], $arScheme["ENTITY_ID"], $arScheme["EVENT_ID"]);
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
			}
		}
	}
	else
	{	
		CXDImport::WriteToLog("ERROR: Incorrect hash: ".$_POST["hash"], "RPOST");
		echo("Incorrect hash!");
	}
}
else
	echo("Incorrect hash length!");
?>
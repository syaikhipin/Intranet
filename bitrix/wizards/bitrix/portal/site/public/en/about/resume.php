<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Online Resume Form");
?>

<?$APPLICATION->IncludeComponent("bitrix:form.result.new", "requests", Array(
	"WEB_FORM_ID"	=>	"#RESUME_FORM_ID#",
	"IGNORE_CUSTOM_TEMPLATE"	=>	"N",
	"USE_EXTENDED_ERRORS"	=>	"N",
	"SEF_MODE"	=>	"N",
	"SEF_FOLDER"	=>	"#SITE_DIR#about/",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"3600",
	"LIST_URL"	=>	"resume.php",
	"EDIT_URL"	=>	"resume.php",
	"SUCCESS_URL"	=>	"",
	"CHAIN_ITEM_TEXT"	=>	"",
	"CHAIN_ITEM_LINK"	=>	"",
	"VARIABLE_ALIASES"	=>	array(
		"WEB_FORM_ID"	=>	"WEB_FORM_ID",
		"RESULT_ID"	=>	"RESULT_ID",
	)
	)
);?>

<p><a href="career.php">Return to vacancies</a></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
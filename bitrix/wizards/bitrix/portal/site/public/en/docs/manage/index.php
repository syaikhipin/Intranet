<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Top Management's documents");
?>

<?$APPLICATION->IncludeComponent("bitrix:webdav", ".default", Array(
	"IBLOCK_TYPE"	=>	"library",
	"IBLOCK_ID"	=>	"#DIRECTORS_FILES_IBLOCK_ID#",
	"BASE_URL"	=>	"#SITE_DIR#docs/manage/",
	"NAME_FILE_PROPERTY"	=>	"FILE",
	"USE_AUTH"	=>	"Y",
	"SEF_MODE"	=>	"Y",
	"SEF_FOLDER"	=>	"#SITE_DIR#docs/manage/",
	"CACHE_TYPE"	=>	"A",
	"CACHE_TIME"	=>	"3600",
	"COLUMNS"	=>	array(
		0	=>	"NAME",
		1	=>	"TIMESTAMP_X",
		2	=>	"USER_NAME",
		3	=>	"FILE_SIZE",
		4	=>	"WF_STATUS_ID"
	),
	"PAGE_ELEMENTS"	=>	"50",
	"PAGE_NAVIGATION_TEMPLATE"	=>	"",
	"STR_TITLE"	=>	"Top Management documents",
	"UPLOAD_MAX_FILESIZE"	=>	"1024",
	"UPLOAD_MAX_FILE"	=>	"4",
	"SET_TITLE"	=>	"Y",
	"DISPLAY_PANEL"	=>	"N",
	"USE_COMMENTS" => "Y", 
	"FORUM_ID" => "#DIRECTORS_FILES_FORUM_ID#", 
	"PATH_TO_SMILE" => "/bitrix/images/forum/smile/", 
	"SEF_URL_TEMPLATES"	=>	array(
		"user_view"	=>	"#SITE_DIR#company/personal/user/#USER_ID#/",
		"sections"	=>	"#PATH#",
		"section_edit"	=>	"folder/edit/#SECTION_ID#/#ACTION#/",
		"element"	=>	"element/view/#ELEMENT_ID#/",
		"element_edit"	=>	"element/edit/#ACTION#/#ELEMENT_ID#/",
		"element_history"	=>	"element/history/#ELEMENT_ID#/",
		"element_history_get"	=>	"element/historyget/#ELEMENT_ID#/#ELEMENT_NAME#",
		"element_upload"	=>	"element/upload/#SECTION_ID#/",
		"help"	=>	"help"
	)
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
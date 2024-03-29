<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/points.php");

/**********************************************************************/
/************** POINTS ************************************************/
/**********************************************************************/
class CForumPoints extends CAllForumPoints
{
	function Add($arFields)
	{
		global $DB;

		if (!CForumPoints::CheckFields("ADD", $arFields))
			return false;

		$ID = IntVal($DB->NextID("SQ_B_FORUM_POINTS"));
		$arInsert = $DB->PrepareInsert("b_forum_points", $arFields);
		$strSql = "INSERT INTO b_forum_points(ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach ($arFields["LANG"] as $i => $val)
		{
			$arInsert = $DB->PrepareInsert("b_forum_points_lang", $arFields["LANG"][$i]);
			$strSql = "INSERT INTO b_forum_points_lang(POINTS_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $ID;
	}
}

/**********************************************************************/
/************** POINTS2POST *******************************************/
/**********************************************************************/
class CForumPoints2Post extends CAllForumPoints2Post
{
	// Recount points don`t maked
	function Add($arFields)
	{
		global $DB;

		if (!CForumPoints2Post::CheckFields("ADD", $arFields))
			return false;

		$ID = intVal($DB->NextID("SQ_B_FORUM_POINTS2POST"));
		$arInsert = $DB->PrepareInsert("b_forum_points2post", $arFields);
		$strSql = "INSERT INTO b_forum_points2post(ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}

/**********************************************************************/
/************** FORUM USER POINTS *************************************/
/**********************************************************************/
class CForumUserPoints extends CAllForumUserPoints
{
	function Add($arFields)
	{
		global $DB;

		if (!CForumUserPoints::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_forum_user_points", $arFields);

		$strDatePostField = "";
		$strDatePostValue = "";
		if (!is_set($arFields, "DATE_UPDATE"))
		{
			$strDatePostField .= ", DATE_UPDATE";
			$strDatePostValue .= ", ".$DB->GetNowFunction()."";
		}

		$strSql = "INSERT INTO b_forum_user_points(".$arInsert[0].$strDatePostField.") VALUES(".$arInsert[1].$strDatePostValue.")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		// Recount user points 
		if (intVal($arFields["TO_USER_ID"])>0)
		{
			$arUserFields = array(
				"POINTS" => CForumUser::CountUserPoints($arFields["TO_USER_ID"]));

			$arUser = CForumUser::GetByUSER_ID($arFields["TO_USER_ID"]);
			if ($arUser)
			{
				CForumUser::Update(intVal($arUser["ID"]), $arUserFields);
			}
			else
			{
				$arUserFields["USER_ID"] = $arFields["TO_USER_ID"];
				$ID_tmp = CForumUser::Add($arUserFields);
			}
		}
		return true;
	}
}
?>
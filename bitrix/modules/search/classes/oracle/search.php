<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/search/classes/general/search.php");

class CSearch extends CAllSearch
{
	function MakeSQL($query, $strSqlWhere, $strSort, $bIncSites, $bStem)
	{
		global $USER;
		$DB = CDatabase::GetModuleConnection('search');

		$bDistinct = false;
		$arSelect = array(
			"ID" => "sc.ID",
			"MODULE_ID" => "sc.MODULE_ID",
			"ITEM_ID" => "sc.ITEM_ID",
			"TITLE" => "sc.TITLE",
			"TAGS" => "sc.TAGS",
			"BODY" => "sc.BODY",
			"PARAM1" => "sc.PARAM1",
			"PARAM2" => "sc.PARAM2",
			"UPD" => "sc.UPD",
			"DATE_FROM" => "sc.DATE_FROM",
			"DATE_TO" => "sc.DATE_TO",
			"URL" => "sc.URL",
			"CUSTOM_RANK" => "sc.CUSTOM_RANK",
			"FULL_DATE_CHANGE" => $DB->DateToCharFunction("sc.DATE_CHANGE")." as FULL_DATE_CHANGE",
			"DATE_CHANGE" => $DB->DateToCharFunction("sc.DATE_CHANGE", "SHORT")." as DATE_CHANGE",
		);
		if(BX_SEARCH_VERSION > 1)
		{
			if($this->Query->bText)
				$arSelect["SEARCHABLE_CONTENT"] = "sct.SEARCHABLE_CONTENT";
			$arSelect["USER_ID"] = "sc.USER_ID";
		}
		else
		{
			$arSelect["LID"] = "sc.LID";
			$arSelect["SEARCHABLE_CONTENT"] = "sc.SEARCHABLE_CONTENT";
		}

		if(strpos($strSort, "TITLE_RANK") !== false)
		{
			if($bStem)
			{
				$strSelect = "";
				foreach($this->Query->m_stemmed_words as $stem)
				{
					if(strlen($strSelect) > 0)
						$strSelect .= " + ";
					$strSelect .= "decode(instr(upper(sc.TITLE), '".$stem."'), 0, 0, 1)";
				}
				$arSelect["TITLE_RANK"] = $strSelect." as TITLE_RANK";
			}
			else
			{
				$strSelect = "";
				foreach($this->Query->m_words as $word)
				{
					if(strlen($strSelect) > 0)
						$strSelect .= " + ";
					$strSelect .= "decode(instr(upper(sc.TITLE), '".$DB->ForSql(ToUpper($word))."'), 0, 0, 1)";
				}
				$arSelect["TITLE_RANK"] = $strSelect." as TITLE_RANK";
			}
		}

		if($bStem)
		{
			if(BX_SEARCH_VERSION > 1)
				$strStemList = implode(", ", $this->Query->m_stemmed_words_id);
			else
				$strStemList = "'".implode("' ,'", $this->Query->m_stemmed_words)."'";
		}

		//This will hint Oracle not to use index on that table
		$strSqlWhere = str_replace("scsite.SITE_ID", "scsite.SITE_ID||''", $strSqlWhere);
		if($bIncSites && $bStem)
		{
			$arSelect["SITE_URL"] = "scsite.URL as SITE_URL";
			$arSelect["SITE_ID"] = "scsite.SITE_ID";
			$arSelect["RANK"] = "st.RANK as RANK";

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
				".(count($this->Query->m_stemmed_words) > 1?
					",(
						select search_content_id, max(st0.TF) TF, ".($bWordPos? "if(STDDEV(st.PS)-".$this->normdev(count($this->Query->m_stemmed_words))." between -0.000001 and 1, 1/STDDEV(st.PS), 0) + ": "")."sum(st0.TF/sf0.FREQ) as RANK
						from b_search_content_stem st0, b_search_content_freq sf0
						where st0.language_id = '".$this->Query->m_lang."'
						and st0.stem = sf0.stem
						and sf0.language_id = st0.language_id
						and st0.stem in (".$strStemList.")
						".($this->tf_hwm > 0? "and st0.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."
						".(strlen($this->tf_hwm_site_id) > 0? "and sf0.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'": "and sf0.SITE_ID IS NULL")."
						group by st0.search_content_id) st"
				:
					",(
						select st0.search_content_id, st0.TF, st0.TF RANK
						from b_search_content_stem st0
						where st0.language_id = '".$this->Query->m_lang."'
						and st0.stem in (".$strStemList.")
						".($this->tf_hwm > 0? "and st0.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."
					) st"
				)."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				AND st.search_content_id = sc.id
				".$strSqlWhere."
			";
		}
		elseif($bIncSites && !$bStem)
		{
			$arSelect["SITE_URL"] = "scsite.URL as SITE_URL";
			$arSelect["SITE_ID"] = "scsite.SITE_ID";
			$arSelect["RANK"] = "1 as RANK";

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
			";
		}
		elseif(!$bIncSites && $bStem)
		{
			if(BX_SEARCH_VERSION <= 1)
				$arSelect["SITE_ID"] = "sc.LID as SITE_ID";
			$arSelect["RANK"] = "st.RANK as RANK";

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				".(count($this->Query->m_stemmed_words) > 1?
					",(
						select search_content_id, max(st0.TF) TF, ".($bWordPos? "if(STDDEV(st.PS)-".$this->normdev(count($this->Query->m_stemmed_words))." between -0.000001 and 1, 1/STDDEV(st.PS), 0) + ": "")."sum(st0.TF/sf0.FREQ) as RANK
						from b_search_content_stem st0, b_search_content_freq sf0
						where st0.language_id = '".$this->Query->m_lang."'
						and st0.stem = sf0.stem
						and sf0.language_id = st0.language_id
						and st0.stem in (".$strStemList.")
						".($this->tf_hwm > 0? "and st0.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."
						".(strlen($this->tf_hwm_site_id) > 0? "and sf0.SITE_ID = '".$DB->ForSQL($this->tf_hwm_site_id, 2)."'": "and sf0.SITE_ID IS NULL")."
						group by st0.search_content_id) st"
				:
					",(
						select st0.search_content_id, st0.TF, st0.TF RANK
						from b_search_content_stem st0
						where st0.language_id = '".$this->Query->m_lang."'
						and st0.stem in (".$strStemList.")
						".($this->tf_hwm > 0? "and st0.TF >= ".number_format($this->tf_hwm, 2, ".", ""): "")."
					) st"
				)."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				AND st.search_content_id = sc.id
				".$strSqlWhere."
			";
		}
		else //if(!$bIncSites && !$bStem)
		{
			if(BX_SEARCH_VERSION <= 1)
				$arSelect["SITE_ID"] = "sc.LID as SITE_ID";
			$arSelect["RANK"] = "1 as RANK";

			$strSql = "
			FROM b_search_content sc
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
			";
		}

		$limit = COption::GetOptionInt("search", "max_result_size");
		if($limit < 1)
			$limit = 500;

		$strSelect = "SELECT ".($bDistinct? "DISTINCT": "")."\n".implode("\n,", $arSelect);

		$strRatingJoin = "";
		if(
			($this->flagsUseRatingSort & 0x01)
			&& COption::GetOptionString("search", "use_social_rating") == "Y"
			&& BX_SEARCH_VERSION == 2
			&& COption::GetOptionString("search", "dbnode_id") <= 0
		)
		{
			$rsMinMax = $DB->Query("select max(TOTAL_VALUE) RATING_MAX, min(TOTAL_VALUE) RATING_MIN from b_rating_voting");
			$arMinMax = $rsMinMax->Fetch();
			if($arMinMax)
			{
				$RATING_MAX = doubleval($arMinMax["RATING_MAX"]);
				if($RATING_MAX < 0)
					$RATING_MAX = 0;

				$RATING_MIN = doubleval($arMinMax["RATING_MIN"]);
				if($RATING_MIN > 0)
					$RATING_MIN = 0;
			}

			if($RATING_MAX != 0 || $RATING_MIN != 0)
				return "
					SELECT sc.*, sc.RANK + case
						when rv.TOTAL_VALUE > 0 then ".($RATING_MAX > 0? "rv.TOTAL_VALUE/".$RATING_MAX: "0")."
						when rv.TOTAL_VALUE < 0 then ".($RATING_MIN < 0? "rv.TOTAL_VALUE/".abs($RATING_MIN): "0")."
						else 0
					end SRANK
					,".$DB->IsNull('rvv.VALUE', '0')." RATING_USER_VOTE_VALUE
					,sc.ENTITY_TYPE_ID RATING_TYPE_ID
					,sc.ENTITY_ID RATING_ENTITY_ID
					,rv.TOTAL_VOTES RATING_TOTAL_VOTES
					,rv.TOTAL_POSITIVE_VOTES RATING_TOTAL_POSITIVE_VOTES
					,rv.TOTAL_NEGATIVE_VOTES RATING_TOTAL_NEGATIVE_VOTES
					,rv.TOTAL_VALUE RATING_TOTAL_VALUE
					FROM (
					SELECT * FROM (".$strSelect."
					,sc.ENTITY_TYPE_ID, sc.ENTITY_ID
					".$strSql.$strSort.") WHERE ROWNUM <= ".$limit."
					) sc
					LEFT JOIN b_rating_voting rv ON rv.ENTITY_TYPE_ID = sc.ENTITY_TYPE_ID AND rv.ENTITY_ID = sc.ENTITY_ID
					LEFT JOIN b_rating_vote rvv ON rvv.ENTITY_TYPE_ID = sc.ENTITY_TYPE_ID AND rvv.ENTITY_ID = sc.ENTITY_ID AND rvv.USER_ID = ".intval($USER->GetID())."
				".str_replace(" RANK", " SRANK", $strSort);
		}

		return "SELECT * FROM (".$strSelect.$strSql.$strSort.") WHERE ROWNUM <= ".$limit;
	}

	function tagsMakeSQL($query, $strSqlWhere, $strSort, $bIncSites, $bStem, $limit = 100)
	{
		global $USER;
		$DB = CDatabase::GetModuleConnection('search');
		$limit = intVal($limit);
		if($bIncSites && $bStem)
			$strSql = "
			SELECT
				stags.NAME
				,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
				,MAX(sc.DATE_CHANGE) DC_TMP
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
			FROM b_search_tags stags
				INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
				AND stags.SITE_ID = scsite.SITE_ID
			GROUP BY
				stags.NAME
			".$strSort."
			";
		elseif($bIncSites && !$bStem)
			$strSql = "
			SELECT
				stags.NAME
				,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
				,MAX(sc.DATE_CHANGE) DC_TMP
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
			FROM b_search_tags stags
				INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
				INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
				AND stags.SITE_ID = scsite.SITE_ID
			GROUP BY
				stags.NAME
			".$strSort."
			";
		elseif(!$bIncSites && $bStem)
			$strSql = "
			SELECT
				stags.NAME
				,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
				,MAX(sc.DATE_CHANGE) DC_TMP
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
			FROM b_search_tags stags
				INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
			GROUP BY
				stags.NAME
			".$strSort."
			";
		else //if(!$bIncSites && !$bStem)
			$strSql = "
			SELECT
				stags.NAME
				,COUNT(DISTINCT stags.SEARCH_CONTENT_ID) as CNT
				,MAX(sc.DATE_CHANGE) DC_TMP
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)")." as FULL_DATE_CHANGE
				,".$DB->DateToCharFunction("MAX(sc.DATE_CHANGE)", "SHORT")." as DATE_CHANGE
			FROM b_search_tags stags
				INNER JOIN b_search_content sc ON (stags.SEARCH_CONTENT_ID=sc.ID)
				".($this->Query->bText? "INNER JOIN b_search_content_text sct ON sct.SEARCH_CONTENT_ID = sc.ID": "")."
			WHERE
				".CSearch::CheckPermissions("sc.ID")."
				AND ".$query."
				".$strSqlWhere."
			GROUP BY
				stags.NAME
			".$strSort."
			";

		if($limit < 1)
			$limit = 150;

		return "SELECT * FROM (".$strSql.") WHERE ROWNUM <= ".$limit;
	}

	function ReindexLock()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("LOCK TABLE b_search_content IN SHARE MODE", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function DeleteOld($SESS_ID, $MODULE_ID="", $SITE_ID="")
	{
		$DB = CDatabase::GetModuleConnection('search');

		$strFilter = "";
		if($MODULE_ID!="")
			$strFilter.=" AND MODULE_ID = '".$DB->ForSql($MODULE_ID)."' ";

		$strJoin = "";
		if($SITE_ID!="")
		{
			$strFilter.=" AND scsite.SITE_ID = '".$DB->ForSql($SITE_ID)."' ";
			$strJoin.=" INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID ";
		}

		if(!is_array($SESS_ID))
			$SESS_ID = array($SESS_ID);

		$strSql = "
			SELECT ID
			FROM b_search_content sc
			".$strJoin."
			WHERE (UPD not in ('".implode("', '", $SESS_ID)."') OR UPD IS NULL)
			".$strFilter."
		";

		foreach(GetModuleEvents("search", "OnBeforeIndexDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID IN (".$strSql.")"));

		$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(BX_SEARCH_VERSION > 1)
			$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		CSearchTags::CleanCache();

		$DB->Query("DELETE FROM b_search_content sc WHERE ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function DeleteForReindex($MODULE_ID)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$MODULE_ID = $DB->ForSql($MODULE_ID);
		$strSql = "SELECT ID FROM b_search_content WHERE MODULE_ID = '".$MODULE_ID."'";

		foreach(GetModuleEvents("search", "OnBeforeIndexDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID in (".$strSql.")"));

		$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(BX_SEARCH_VERSION > 1)
			$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		CSearchTags::CleanCache();

		$DB->Query("DELETE FROM b_search_content WHERE MODULE_ID = '".$MODULE_ID."'", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	function OnLangDelete($lang)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("
			DELETE FROM b_search_content_site
			WHERE SITE_ID='".$DB->ForSql($lang)."'
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		CSearchTags::CleanCache();
	}

	function ChangePermission($MODULE_ID, $arGroups, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false, $PARAMS=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arNewGroups = array();
		foreach($arGroups as $group_id)
		{
			if(is_numeric($group_id))
				$arNewGroups[$group_id] = "G".intval($group_id);
			else
				$arNewGroups[$group_id] = $group_id;
		}

		$strSqlWhere = CSearch::__PrepareFilter(array(
			"MODULE_ID"=>$MODULE_ID,
			"ITEM_ID"=>$ITEM_ID,
			"PARAM1"=>$PARAM1,
			"PARAM2"=>$PARAM2,
			"SITE_ID"=>$SITE_ID,
			"PARAMS"=>$PARAMS,
		), $bIncSites);

		$DB->Query("
			DELETE FROM b_search_content_right
			".($strSqlWhere?
				"WHERE SEARCH_CONTENT_ID IN (
					SELECT ID FROM b_search_content sc WHERE ".$strSqlWhere."
				)":
				""
			)."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		foreach($arNewGroups as $group_code)
		{
			$DB->Query("
				INSERT INTO b_search_content_right (SEARCH_CONTENT_ID, GROUP_CODE)
				SELECT ID, '".$DB->ForSQL($group_code, 100)."'
				FROM b_search_content sc
				".($strSqlWhere?
					"WHERE ".$strSqlWhere:
					""
				)."
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	function ChangeSite($MODULE_ID, $arSite, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
			".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
			WHERE
			".$strSqlWhere."
		";

		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arR = $r->Fetch())
		{
			$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID = ".$arR["ID"], false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$bFirst = true;
			foreach($arSite as $site => $url)
			{
				if($bFirst)
				{
					if(BX_SEARCH_VERSION > 1)
						$strSql = "
							UPDATE b_search_content
							SET URL = '".$DB->ForSql($url, 2000)."'
							WHERE ID = ".$arR["ID"]."
						";
					else
						$strSql = "
							UPDATE b_search_content
							SET LID = '".$DB->ForSql($site, 2)."',
							URL = '".$DB->ForSql($url, 2000)."'
							WHERE ID = ".$arR["ID"]."
						";
					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$bFirst = false;
				}
				$strSql = "
					INSERT INTO b_search_content_site(SEARCH_CONTENT_ID, SITE_ID, URL)
					VALUES(".$arR["ID"].", '".$DB->ForSql($site, 2)."', '".$DB->ForSql($url, 2000)."')";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}

	function DeleteIndex($MODULE_ID, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if($PARAM1 !== false && $PARAM2 !== false)
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, array("=PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2), "SITE_ID"=>$SITE_ID), $bIncSites);
		else
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);

		$strSql = "
			SELECT sc.ID
			FROM b_search_content sc
				".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID" :"")."
			WHERE
			".$strSqlWhere."
		";

		foreach(GetModuleEvents("search", "OnBeforeIndexDelete", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array("SEARCH_CONTENT_ID in (".$strSql.")"));

		$DB->Query("DELETE FROM b_search_content_param WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_right WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_site WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_title WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_content_stem WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(BX_SEARCH_VERSION > 1)
			$DB->Query("DELETE FROM b_search_content_text WHERE SEARCH_CONTENT_ID IN (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query("DELETE FROM b_search_tags WHERE SEARCH_CONTENT_ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$DB->Query("DELETE FROM b_search_content WHERE ID in (".$strSql.")", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		CSearchTags::CleanCache();
	}

	function FormatDateString($strField)
	{
		return "TO_CHAR(".$strField.", 'DD.MM.YYYY HH24:MI:SS')";
	}

	function FormatLimit($strSql, $limit)
	{
		return "SELECT * FROM (".str_replace("/*TOP*/", "", $strSql).") WHERE ROWNUM <= ".intval($limit);
	}

	function Update($ID, $arFields)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("~DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["~DATE_CHANGE"];
			unset($arFields["~DATE_CHANGE"]);
		}
		elseif(array_key_exists("LAST_MODIFIED", $arFields))
		{
			$arFields["DATE_CHANGE"] = $arFields["LAST_MODIFIED"];
			unset($arFields["LAST_MODIFIED"]);
		}
		elseif(array_key_exists("DATE_CHANGE", $arFields))
		{
			$arFields["DATE_CHANGE"] = $DB->FormatDate($arFields["DATE_CHANGE"], "DD.MM.YYYY HH.MI.SS", CLang::GetDateFormat());
		}

		if(BX_SEARCH_VERSION > 1)
			unset($arFields["SEARCHABLE_CONTENT"]);

		$strUpdate = $DB->PrepareUpdate("b_search_content", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$arBinds=Array();
			if(is_set($arFields, "BODY"))
				$arBinds["BODY"] = $arFields["BODY"];
			if(is_set($arFields, "SEARCHABLE_CONTENT"))
				$arBinds["SEARCHABLE_CONTENT"] = $arFields["SEARCHABLE_CONTENT"];
			if(is_set($arFields, "TAGS"))
				$arBinds["TAGS"] = $arFields["TAGS"];
			$DB->QueryBind("UPDATE b_search_content SET ".$strUpdate." WHERE ID=".intval($ID), $arBinds);
		}
	}

	function CleanFreqCache($ID)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$DB->Query("
			UPDATE b_search_content_freq
			SET TF = null
			WHERE
			TF is not null
			AND (LANGUAGE_ID, STEM) in (
				SELECT LANGUAGE_ID,STEM FROM b_search_content_stem WHERE SEARCH_CONTENT_ID = ".intval($ID)."
			)
		");
	}

	function IndexTitle($arLID, $ID, $sTitle)
	{
		$DB = CDatabase::GetModuleConnection('search');
		static $CACHE_SITE_LANGS = array();
		$ID = intval($ID);

		$arLang=array();
		if(!is_array($arLID))
			$arLID = Array();
		foreach($arLID as $site=>$url)
		{
			$sql_site = $DB->ForSql($site);

			if(!array_key_exists($site, $CACHE_SITE_LANGS))
			{
				$db_site_tmp = CSite::GetByID($site);
				if ($ar_site_tmp = $db_site_tmp->Fetch())
					$CACHE_SITE_LANGS[$site] = array(
						"LANGUAGE_ID" => $ar_site_tmp["LANGUAGE_ID"],
						"CHARSET" => $ar_site_tmp["CHARSET"],
						"SERVER_NAME" => $ar_site_tmp["SERVER_NAME"]
					);
				else
					$CACHE_SITE_LANGS[$site] = false;
			}

			if(is_array($CACHE_SITE_LANGS[$site]))
			{
				$lang = $CACHE_SITE_LANGS[$site]["LANGUAGE_ID"];

				$arTitle = stemming_split($sTitle, $lang);
				if(!empty($arTitle))
				{
					$maxValuesLen = 0;
					$strSqlPrefix = "
							insert into b_search_content_title
							(SEARCH_CONTENT_ID, SITE_ID, WORD, POS)
							values(
					";
					$strSqlValues = "";
					$strSqlSuffix = ")";
					foreach($arTitle as $word => $pos)
					{
						$strSqlValues .= $ID.", '".$sql_site."', '".$DB->ForSQL($word)."', ".$pos;
						if(strlen($strSqlValues) > $maxValuesLen)
						{
							$DB->Query($strSqlPrefix.$strSqlValues.$strSqlSuffix, false, "File: ".__FILE__."<br>Line: ".__LINE__);
							$strSqlValues = "";
						}
					}
					if(strlen($strSqlValues) > 0)
					{
						$DB->Query($strSqlPrefix.$strSqlValues.$strSqlSuffix, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$strSqlValues = "";
					}
				}
			}
		}
	}

	function RegisterStem($stem)
	{
		global $DB;
		static $cache = array();

		if(is_array($stem)) //This is batch check of the already exist stems
		{
			ksort($stem);

			$strSqlPrefix = "select * from b_search_stem where stem in (";
			$maxValuesLen = 4096;
			$maxValuesCnt = 1500;
			$strSqlValues = "";
			$i = 0;
			foreach($stem as $word => $count)
			{
				$strSqlValues .= ",'".$DB->ForSQL($word)."'";
				$i++;

				if(strlen($strSqlValues) > $maxValuesLen || $i > $maxValuesCnt)
				{
					$rs = $DB->Query($strSqlPrefix.substr($strSqlValues, 1).")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
					while($ar = $rs->Fetch())
						$cache[$ar["STEM"]] = $ar["ID"];

					$strSqlValues = "";
					$i = 0;
				}
			}

			if(strlen($strSqlValues) > 0)
			{
				$rs = $DB->Query($strSqlPrefix.substr($strSqlValues, 1).")", false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while($ar = $rs->Fetch())
					$cache[$ar["STEM"]] = $ar["ID"];
			}

			return;
		}

		if(!isset($cache[$stem]))
		{
			$rs = $DB->Query("insert into b_search_stem (STEM) values ('".$DB->ForSQL($stem)."')", true);
			$rs = $DB->Query("select ID from b_search_stem WHERE STEM = '".$DB->ForSQL($stem)."'");
			$ar = $rs->Fetch();
			$cache[$stem] = intval($ar["ID"]);
		}

		return $cache[$stem];
	}

	function StemIndex($arLID, $ID, $sContent)
	{
		$DB = CDatabase::GetModuleConnection('search');
		static $CACHE_SITE_LANGS = array();
		$ID = intval($ID);

		$arLang=array();
		if(!is_array($arLID))
			$arLID = Array();
		foreach($arLID as $site=>$url)
		{
			if(!array_key_exists($site, $CACHE_SITE_LANGS))
			{
				$db_site_tmp = CSite::GetByID($site);
				if ($ar_site_tmp = $db_site_tmp->Fetch())
					$CACHE_SITE_LANGS[$site] = array(
						"LANGUAGE_ID" => $ar_site_tmp["LANGUAGE_ID"],
						"CHARSET" => $ar_site_tmp["CHARSET"],
						"SERVER_NAME" => $ar_site_tmp["SERVER_NAME"]
					);
				else
					$CACHE_SITE_LANGS[$site] = false;
			}
			if(is_array($CACHE_SITE_LANGS[$site]))
				$arLang[$CACHE_SITE_LANGS[$site]["LANGUAGE_ID"]]++;
		}
		foreach($arLang as $lang=>$value)
		{
			$sql_lang = $DB->ForSql($lang);

			$arDoc = stemming($sContent, $lang);
			$docLength = array_sum($arDoc);

			if(BX_SEARCH_VERSION > 1)
			{
				$arPos = stemming($sContent, $lang, /*$bIgnoreStopWords*/false, /*$bReturnPositions*/true);
				CSearch::RegisterStem($arDoc);
			}

			if($docLength>0)
			{
				$doc = "";
				$logDocLength = log($docLength<20?20:$docLength);
				$maxValuesLen = 1024;

				$strSqlPrefix = "
						insert into b_search_content_stem
						(SEARCH_CONTENT_ID, LANGUAGE_ID, STEM, TF".(BX_SEARCH_VERSION > 1? ",PS": "").")
						SELECT ".$ID.", '".$sql_lang."', T.STEM, T.TF".(BX_SEARCH_VERSION > 1? ",T.PS": "")."
						FROM table(cast(f_stem('";
				$strSqlValues = "";
				$strSqlSuffix = "') as tt_stem)) t";

				foreach($arDoc as $word => $count)
				{
					if(BX_SEARCH_VERSION > 1)
						$strSqlValues .= " ".CSearch::RegisterStem($word).";".number_format(log($count+1)/$docLength, 4, ".", "").";".number_format($arPos[$word]/$count, 4, ".", "");
					else
						$strSqlValues .= " ".$word.";".number_format(log($count+1)/$docLength, 4, ".", "");

					if(strlen($strSqlValues) > $maxValuesLen)
					{
						$strSqlValues = $DB->ForSQL(substr($strSqlValues, 1));
						$DB->Query($strSqlPrefix.$strSqlValues.$strSqlSuffix, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$strSqlValues = "";
					}
				}
				if(strlen($strSqlValues) > 0)
				{
					$strSqlValues = $DB->ForSQL(substr($strSqlValues, 1));
					$DB->Query($strSqlPrefix.$strSqlValues.$strSqlSuffix, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$strSqlValues = "";
				}
			}
		}
	}

	function TagsIndex($arLID, $ID, $sContent)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$ID = intval($ID);

		if(!is_array($arLID))
			$arLID = Array();
		$sContent = str_replace("\x00", "", $sContent);

		foreach($arLID as $site_id => $url)
		{
			$sql_site_id  = $DB->ForSQL($site_id);

			$arTags = tags_prepare($sContent, $site_id);
			if(!empty($arTags))
			{
				$strSqlPrefix = "
						insert into b_search_tags
						(SEARCH_CONTENT_ID, SITE_ID, NAME)
						values
				";
				$maxValuesLen = 0;
				$strSqlValues = "";
				CSearchTags::CleanCache($arTags);
				foreach($arTags as $tag)
				{
					$strSqlValues .= ",\n(".$ID.", '".$sql_site_id."', '".$DB->ForSql($tag, 255)."')";
					if(strlen($strSqlValues) > $maxValuesLen)
					{
						$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
						$strSqlValues = "";
					}
				}
				if(strlen($strSqlValues) > 0)
				{
					$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, "File: ".__FILE__."<br>Line: ".__LINE__);
					$strSqlValues = "";
				}
			}
		}
	}

	function ChangeIndex($MODULE_ID, $arFields, $ITEM_ID=false, $PARAM1=false, $PARAM2=false, $SITE_ID=false)
	{
		$DB = CDatabase::GetModuleConnection('search');

		if(array_key_exists("TITLE", $arFields))
			$arFields["TITLE"] = Trim($arFields["TITLE"]);

		if(array_key_exists("BODY", $arFields))
			$arFields["BODY"] = Trim($arFields["BODY"]);

		if(array_key_exists("PERMISSIONS", $arFields) && is_array($arFields["PERMISSIONS"]))
			CSearch::ChangePermission($MODULE_ID, $arFields["PERMISSIONS"], $ITEM_ID, $PARAM1, $PARAM2, $SITE_ID);

		if(array_key_exists("SITE_ID", $arFields) && is_array($arFields["SITE_ID"]))
			CSearch::ChangeSite($MODULE_ID, $arFields["SITE_ID"], $ITEM_ID, $PARAM1, $PARAM2, $SITE_ID);

		$strUpdate = $DB->PrepareUpdate("b_search_content", $arFields);
		if(strlen($strUpdate) > 0)
		{
			$strSqlWhere = CSearch::__PrepareFilter(Array("MODULE_ID"=>$MODULE_ID, "ITEM_ID"=>$ITEM_ID, "PARAM1"=>$PARAM1, "PARAM2"=>$PARAM2, "SITE_ID"=>$SITE_ID), $bIncSites);
			$strSql = "
				SELECT sc.ID
				FROM b_search_content sc
				".($bIncSites? "INNER JOIN b_search_content_site scsite ON sc.ID=scsite.SEARCH_CONTENT_ID": "")."
				".(strlen($strSqlWhere)>0? "WHERE ".$strSqlWhere: "")."
			";
			$rs = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($ar = $rs->Fetch())
			{
				$strSql = "UPDATE b_search_content SET ".$strUpdate." WHERE ID=".$ar["ID"];

				$arBinds=Array();
				if(is_set($arFields, "BODY"))
					$arBinds["BODY"] = $arFields["BODY"];
				if(is_set($arFields, "SEARCHABLE_CONTENT"))
					$arBinds["SEARCHABLE_CONTENT"] = $arFields["SEARCHABLE_CONTENT"];
				if(is_set($arFields, "TAGS"))
					$arBinds["TAGS"] = $arFields["TAGS"];
				$DB->QueryBind($strSql, $arBinds);

				if(array_key_exists("PARAMS", $arFields))
					CSearch::SetContentItemParams($ar["ID"], $arFields["PARAMS"]);
			}
		}
	}
}

class CSearchQuery extends CAllSearchQuery
{
	var $cnt = 0;
	function BuildWhereClause($word)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$this->cnt++;
		if($this->cnt>10)
			return "1=1";

		if(isset($this->m_kav[$word]))
		{
			$word = $this->m_kav[$word];
			$bInQuotes = true;
		}
		else
		{
			$bInQuotes = false;
		}
		$this->m_words[] = $word;
		$word = $DB->ForSql($word, 100);

		if($this->bTagsSearch)
		{
			if(strpos($word, "%")===false)
			{
				//We can optimize query by doing range scan
				if(is_array($this->m_tags_words))
					$this->m_tags_words[] = $word;
				$op = "=";
			}
			else
			{
				//Optimization is not possible
				$this->m_tags_words = false;
				$op = "like";
			}
			return " exists (
					select * from b_search_tags st
					where st.name ".$op." '".$word."'
					and st.search_content_id = sc.id)\n";
		}
		elseif($this->bStemming && !$bInQuotes)
		{
			$word = ToUpper($word);
			$this->m_stemmed_words[] = $word;
			if(BX_SEARCH_VERSION > 1)
			{
				$rs = $DB->Query("select ID from b_search_stem where STEM='".$DB->ForSQL($word)."'");
				$ar = $rs->Fetch();
				$this->m_stemmed_words_id[] = intval($ar["ID"]);

				return "
				sc.id in (
					select search_content_id
					from b_search_content_stem
					where language_id = '".$this->m_lang."'
					and stem=  ".intval($ar["ID"])."
				)\n";
			}
			else
			{
				return "
				sc.id in (
					select search_content_id
					from b_search_content_stem
					where language_id = '".$this->m_lang."'
					and stem=  '".$word."'
				)\n";
			}
		}
		else
		{
			if(BX_SEARCH_VERSION > 1)
				$this->bText = true;

			return "(DBMS_LOB.INSTR(".$this->m_fields[0].", '".ToUpper($word)."')>0)";
		}
	}
}
?>

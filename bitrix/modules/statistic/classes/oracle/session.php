<?
class CSession
{
	function GetAttentiveness($DATE_STAT, $SITE_ID=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if ($SITE_ID!==false) $str = " and S.FIRST_SITE_ID = '".$DB->ForSql($SITE_ID,2)."' ";
		$strSql = "
			SELECT
				round(sum((S.DATE_LAST-S.DATE_FIRST)*86400)/count(S.ID), 2)								AM_AVERAGE_TIME,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 < 60 then 1 else 0 end)					AM_1,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 60 and 179 then 1 else 0 end)	AM_1_3,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 180 and 359 then 1 else 0 end)	AM_3_6,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 360 and 539 then 1 else 0 end)	AM_6_9,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 540 and 719 then 1 else 0 end)	AM_9_12,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 720 and 899 then 1 else 0 end)	AM_12_15,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 900 and 1079 then 1 else 0 end)	AM_15_18,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 1080 and 1259 then 1 else 0 end)	AM_18_21,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 between 1260 and 1439 then 1 else 0 end)	AM_21_24,
				sum(case when (S.DATE_LAST-S.DATE_FIRST)*86400 >= 1440 then 1 else 0 end)				AM_24,

				round(sum(S.HITS)/count(S.ID),2)							AH_AVERAGE_HITS,
				sum(case when S.HITS<=1 then 1 else 0 end)					AH_1,
				sum(case when S.HITS>=2 and S.HITS<=5 then 1 else 0 end)	AH_2_5,
				sum(case when S.HITS>=6 and S.HITS<=9 then 1 else 0 end)	AH_6_9,
				sum(case when S.HITS>=10 and S.HITS<=13 then 1 else 0 end)	AH_10_13,
				sum(case when S.HITS>=14 and S.HITS<=17 then 1 else 0 end)	AH_14_17,
				sum(case when S.HITS>=18 and S.HITS<=21 then 1 else 0 end)	AH_18_21,
				sum(case when S.HITS>=22 and S.HITS<=25 then 1 else 0 end)	AH_22_25,
				sum(case when S.HITS>=26 and S.HITS<=29 then 1 else 0 end)	AH_26_29,
				sum(case when S.HITS>=30 and S.HITS<=33 then 1 else 0 end)	AH_30_33,
				sum(case when S.HITS>=34 then 1 else 0 end)					AH_34
			FROM
				b_stat_session S
			WHERE
				S.DATE_STAT = ".$DB->CharToDateFunction($DATE_STAT, "SHORT")."
			$str
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		$ar = $rs->Fetch();
		$arKeys = array_keys($ar);
		foreach($arKeys as $key)
		{
			if ($key=="AM_AVERAGE_TIME" || $key=="AH_AVERAGE_HITS")
			{
				$ar[$key] = (float) $ar[$key];
				$ar[$key] = round($ar[$key],2);
			}
			else
			{
				$ar[$key] = intval($ar[$key]);
			}
		}
		return $ar;
	}

	function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
					case "GUEST_ID":
					case "ADV_ID":
					case "STOP_LIST_ID":
					case "USER_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match);
						break;
					case "COUNTRY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.COUNTRY_ID",$val,$match);
						break;
					case "CITY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.CITY_ID",$val,$match);
						break;
					case "DATE_START_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_FIRST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_START_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_FIRST<".$DB->CharToDateFunction($val, "SHORT")."+1";
						break;
					case "DATE_END_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_LAST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_END_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_LAST<".$DB->CharToDateFunction($val, "SHORT")."+1";
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.IP_LAST",$val,$match,array("."));
						break;
					case "REGISTERED":
						$arSqlSearch[] = ($val=="Y") ? "S.USER_ID>0" : "(S.USER_ID<=0 or S.USER_ID is null)";
						break;
					case "EVENTS1":
						$arSqlSearch[] = "S.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS2":
						$arSqlSearch[] = "S.C_EVENTS<='".intval($val)."'";
						break;
					case "HITS1":
						$arSqlSearch[] = "S.HITS>='".intval($val)."'";
						break;
					case "HITS2":
						$arSqlSearch[] = "S.HITS<='".intval($val)."'";
						break;
					case "ADV":
						if ($val=="Y")
							$arSqlSearch[] = "(S.ADV_ID>0 and S.ADV_ID is not null)";
						elseif ($val=="N")
							$arSqlSearch[] = "(S.ADV_ID<=0 or S.ADV_ID is null)";
						break;
					case "REFERER1":
					case "REFERER2":
					case "REFERER3":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
					case "USER_AGENT":
						$val = preg_replace("/[\n\r]+/", " ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.USER_AGENT", $val, $match);
						break;
					case "STOP":
						$arSqlSearch[] = ($val=="Y") ? "S.STOP_LIST_ID>0" : "(S.STOP_LIST_ID<=0 or S.STOP_LIST_ID is null)";
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						$from2 = "INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)";
						break;
					case "REGION":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.REGION", $val, $match);
						break;
					case "CITY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("CITY.NAME", $val, $match);
						break;
					case "URL_TO":
					case "URL_LAST":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "ADV_BACK":
					case "NEW_GUEST":
					case "FAVORITES":
					case "URL_LAST_404":
					case "URL_TO_404":
					case "USER_AUTH":
						$arSqlSearch[] = ($val=="Y") ? "S.".$key."='Y'" : "S.".$key."='N'";
						break;
					case "USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = "nvl(S.USER_ID,0)>0";
						$arSqlSearch[] = GetFilterQuery("S.USER_ID,A.LOGIN,A.LAST_NAME,A.NAME", $val, $match);
						$from1 = "LEFT JOIN b_user A ON (A.ID = S.USER_ID)";
						$select = " , A.LOGIN, nvl(A.NAME,'')||' '||nvl(A.LAST_NAME,'') USER_NAME";
						break;
					case "LAST_SITE_ID":
					case "FIRST_SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
				}
			}
		}

		if ($by == "s_id")					$strSqlOrder = "ORDER BY S.ID";
		elseif ($by == "s_last_site_id")	$strSqlOrder = "ORDER BY S.LAST_SITE_ID";
		elseif ($by == "s_first_site_id")	$strSqlOrder = "ORDER BY S.FIRST_SITE_ID";
		elseif ($by == "s_date_first")		$strSqlOrder = "ORDER BY S.DATE_FIRST";
		elseif ($by == "s_date_last")		$strSqlOrder = "ORDER BY S.DATE_LAST";
		elseif ($by == "s_user_id")			$strSqlOrder = "ORDER BY S.USER_ID";
		elseif ($by == "s_guest_id")		$strSqlOrder = "ORDER BY S.GUEST_ID";
		elseif ($by == "s_ip")				$strSqlOrder = "ORDER BY S.IP_LAST";
		elseif ($by == "s_hits")			$strSqlOrder = "ORDER BY S.HITS ";
		elseif ($by == "s_events")			$strSqlOrder = "ORDER BY S.C_EVENTS ";
		elseif ($by == "s_adv_id")			$strSqlOrder = "ORDER BY S.ADV_ID ";
		elseif ($by == "s_country_id")		$strSqlOrder = "ORDER BY S.COUNTRY_ID ";
		elseif ($by == "s_region_name")		$strSqlOrder = "ORDER BY CITY.REGION ";
		elseif ($by == "s_city_id")		$strSqlOrder = "ORDER BY S.CITY_ID ";
		elseif ($by == "s_url_last")		$strSqlOrder = "ORDER BY S.URL_LAST ";
		elseif ($by == "s_url_to")			$strSqlOrder = "ORDER BY S.URL_TO ";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY S.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				S.ID,
				S.GUEST_ID,
				S.NEW_GUEST,
				S.USER_ID,
				S.USER_AUTH,
				S.C_EVENTS,
				S.HITS,
				S.FAVORITES,
				S.URL_FROM,
				S.URL_TO,
				S.URL_TO_404,
				S.URL_LAST,
				S.URL_LAST_404,
				S.USER_AGENT,
				S.IP_FIRST,
				S.IP_LAST,
				S.FIRST_HIT_ID,
				S.LAST_HIT_ID,
				S.PHPSESSID,
				S.ADV_ID,
				S.ADV_BACK,
				S.REFERER1,
				S.REFERER2,
				S.REFERER3,
				S.STOP_LIST_ID,
				S.COUNTRY_ID,
				CITY.REGION REGION_NAME,
				S.CITY_ID,
				CITY.NAME CITY_NAME,
				S.FIRST_SITE_ID,
				S.LAST_SITE_ID,
				ROUND(86400*(S.DATE_LAST-S.DATE_FIRST)) SESSION_TIME,
				".$DB->DateToCharFunction("S.DATE_FIRST")." DATE_FIRST,
				".$DB->DateToCharFunction("S.DATE_LAST")." DATE_LAST
				$select
			FROM
				b_stat_session S
			$from1
			$from2
				LEFT JOIN b_stat_city CITY ON (CITY.ID = S.CITY_ID)
			WHERE
			$strSqlSearch
			$strSqlOrder
			";

		$strSql = "SELECT * FROM ($strSql) WHERE ROWNUM<=".COption::GetOptionString("statistic","RECORDS_LIMIT");
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	function GetByID($ID)
	{
		global $DB;
		$statDB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);

		$res = $statDB->Query("
			SELECT
				S.*,
				ROUND(86400*(S.DATE_LAST-S.DATE_FIRST)) SESSION_TIME,
				".$statDB->DateToCharFunction("S.DATE_FIRST")." DATE_FIRST,
				".$statDB->DateToCharFunction("S.DATE_LAST")." DATE_LAST,
				C.NAME COUNTRY_NAME,
				CITY.REGION REGION_NAME,
				CITY.NAME CITY_NAME
			FROM
				b_stat_session S
				INNER JOIN b_stat_country C ON (C.ID = S.COUNTRY_ID)
				LEFT JOIN b_stat_city CITY ON (CITY.ID = S.CITY_ID)
			WHERE
				S.ID = ".$ID."
		");

		$res = new CStatResult($res);
		return $res;
	}
}
?>

<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/traffic.php");
class CTraffic extends CAllTraffic
{
	function GetSumList($DATA_TYPE, $arFilter=Array())
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$site_filtered = false;
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
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "DATE_STAT<".$DB->CharToDateFunction($val, "SHORT")." + 1";
						break;
					case "SITE_ID":
						$site_filtered = true;
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$site_filtered = true;
						$arSqlSearch[] = GetFilterQuery("SITE_ID", $val, $match);
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$table_name = ($site_filtered) ? "b_stat_day_site" : "b_stat_day";
		$arSelect = $arSelect1 = $arSelect2 = array();

		switch ($DATA_TYPE)
		{
			case "hour":
				$arSelect1 = array(
					"HOUR_HOST_0", "HOUR_HOST_1", "HOUR_HOST_2", "HOUR_HOST_3", "HOUR_HOST_4", "HOUR_HOST_5", "HOUR_HOST_6", "HOUR_HOST_7",	"HOUR_HOST_8", "HOUR_HOST_9", "HOUR_HOST_10", "HOUR_HOST_11", "HOUR_HOST_12", "HOUR_HOST_13", "HOUR_HOST_14", "HOUR_HOST_15", "HOUR_HOST_16", "HOUR_HOST_17", "HOUR_HOST_18", "HOUR_HOST_19", "HOUR_HOST_20", "HOUR_HOST_21", "HOUR_HOST_22", "HOUR_HOST_23",

					"HOUR_SESSION_0", "HOUR_SESSION_1", "HOUR_SESSION_2", "HOUR_SESSION_3", "HOUR_SESSION_4", "HOUR_SESSION_5", "HOUR_SESSION_6", "HOUR_SESSION_7", "HOUR_SESSION_8", "HOUR_SESSION_9", "HOUR_SESSION_10", "HOUR_SESSION_11", "HOUR_SESSION_12", "HOUR_SESSION_13", "HOUR_SESSION_14", "HOUR_SESSION_15", "HOUR_SESSION_16", "HOUR_SESSION_17", "HOUR_SESSION_18", "HOUR_SESSION_19", "HOUR_SESSION_20", "HOUR_SESSION_21", "HOUR_SESSION_22", "HOUR_SESSION_23",

					"HOUR_HIT_0", "HOUR_HIT_1", "HOUR_HIT_2", "HOUR_HIT_3", "HOUR_HIT_4", "HOUR_HIT_5", "HOUR_HIT_6", "HOUR_HIT_7", "HOUR_HIT_8", "HOUR_HIT_9", "HOUR_HIT_10", "HOUR_HIT_11", "HOUR_HIT_12", "HOUR_HIT_13", "HOUR_HIT_14", "HOUR_HIT_15", "HOUR_HIT_16", "HOUR_HIT_17", "HOUR_HIT_18", "HOUR_HIT_19", "HOUR_HIT_20", "HOUR_HIT_21", "HOUR_HIT_22", "HOUR_HIT_23",

					"HOUR_EVENT_0", "HOUR_EVENT_1", "HOUR_EVENT_2", "HOUR_EVENT_3", "HOUR_EVENT_4", "HOUR_EVENT_5", "HOUR_EVENT_6", "HOUR_EVENT_7", "HOUR_EVENT_8", "HOUR_EVENT_9", "HOUR_EVENT_10", "HOUR_EVENT_11", "HOUR_EVENT_12", "HOUR_EVENT_13", "HOUR_EVENT_14", "HOUR_EVENT_15", "HOUR_EVENT_16", "HOUR_EVENT_17", "HOUR_EVENT_18", "HOUR_EVENT_19", "HOUR_EVENT_20", "HOUR_EVENT_21", "HOUR_EVENT_22", "HOUR_EVENT_23"
					);
				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"HOUR_GUEST_0", "HOUR_GUEST_1", "HOUR_GUEST_2", "HOUR_GUEST_3", "HOUR_GUEST_4", "HOUR_GUEST_5", "HOUR_GUEST_6", "HOUR_GUEST_7", "HOUR_GUEST_8", "HOUR_GUEST_9", "HOUR_GUEST_10", "HOUR_GUEST_11", "HOUR_GUEST_12", "HOUR_GUEST_13", "HOUR_GUEST_14", "HOUR_GUEST_15", "HOUR_GUEST_16", "HOUR_GUEST_17", "HOUR_GUEST_18", "HOUR_GUEST_19", "HOUR_GUEST_20", "HOUR_GUEST_21", "HOUR_GUEST_22", "HOUR_GUEST_23",

						"HOUR_NEW_GUEST_0", "HOUR_NEW_GUEST_1", "HOUR_NEW_GUEST_2", "HOUR_NEW_GUEST_3", "HOUR_NEW_GUEST_4", "HOUR_NEW_GUEST_5", "HOUR_NEW_GUEST_6", "HOUR_NEW_GUEST_7", "HOUR_NEW_GUEST_8", "HOUR_NEW_GUEST_9", "HOUR_NEW_GUEST_10", "HOUR_NEW_GUEST_11", "HOUR_NEW_GUEST_12", "HOUR_NEW_GUEST_13", "HOUR_NEW_GUEST_14", "HOUR_NEW_GUEST_15", "HOUR_NEW_GUEST_16", "HOUR_NEW_GUEST_17", "HOUR_NEW_GUEST_18", "HOUR_NEW_GUEST_19", "HOUR_NEW_GUEST_20", "HOUR_NEW_GUEST_21", "HOUR_NEW_GUEST_22", "HOUR_NEW_GUEST_23",

						"HOUR_FAVORITE_0", "HOUR_FAVORITE_1", "HOUR_FAVORITE_2", "HOUR_FAVORITE_3", "HOUR_FAVORITE_4", "HOUR_FAVORITE_5", "HOUR_FAVORITE_6", "HOUR_FAVORITE_7", "HOUR_FAVORITE_8", "HOUR_FAVORITE_9", "HOUR_FAVORITE_10", "HOUR_FAVORITE_11", "HOUR_FAVORITE_12", "HOUR_FAVORITE_13", "HOUR_FAVORITE_14", "HOUR_FAVORITE_15", "HOUR_FAVORITE_16", "HOUR_FAVORITE_17", "HOUR_FAVORITE_18", "HOUR_FAVORITE_19", "HOUR_FAVORITE_20", "HOUR_FAVORITE_21", "HOUR_FAVORITE_22", "HOUR_FAVORITE_23"
						);
				}
				break;
			case "weekday":
				$arSelect1 = array(
					"WEEKDAY_HOST_0", "WEEKDAY_HOST_1", "WEEKDAY_HOST_2", "WEEKDAY_HOST_3", "WEEKDAY_HOST_4", "WEEKDAY_HOST_5", "WEEKDAY_HOST_6",

					"WEEKDAY_SESSION_0", "WEEKDAY_SESSION_1", "WEEKDAY_SESSION_2", "WEEKDAY_SESSION_3", "WEEKDAY_SESSION_4", "WEEKDAY_SESSION_5", "WEEKDAY_SESSION_6",

					"WEEKDAY_HIT_0", "WEEKDAY_HIT_1", "WEEKDAY_HIT_2", "WEEKDAY_HIT_3", "WEEKDAY_HIT_4", "WEEKDAY_HIT_5", "WEEKDAY_HIT_6",

					"WEEKDAY_EVENT_0", "WEEKDAY_EVENT_1", "WEEKDAY_EVENT_2", "WEEKDAY_EVENT_3", "WEEKDAY_EVENT_4", "WEEKDAY_EVENT_5", "WEEKDAY_EVENT_6"
					);

				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"WEEKDAY_GUEST_0", "WEEKDAY_GUEST_1", "WEEKDAY_GUEST_2", "WEEKDAY_GUEST_3", "WEEKDAY_GUEST_4", "WEEKDAY_GUEST_5", "WEEKDAY_GUEST_6",

						"WEEKDAY_NEW_GUEST_0", "WEEKDAY_NEW_GUEST_1", "WEEKDAY_NEW_GUEST_2", "WEEKDAY_NEW_GUEST_3", "WEEKDAY_NEW_GUEST_4", "WEEKDAY_NEW_GUEST_5", "WEEKDAY_NEW_GUEST_6",

						"WEEKDAY_FAVORITE_0", "WEEKDAY_FAVORITE_1", "WEEKDAY_FAVORITE_2", "WEEKDAY_FAVORITE_3", "WEEKDAY_FAVORITE_4", "WEEKDAY_FAVORITE_5", "WEEKDAY_FAVORITE_6"
					);
				}
				break;
			case "month":
				$arSelect1 = array(
					"MONTH_HOST_1", "MONTH_HOST_2", "MONTH_HOST_3", "MONTH_HOST_4", "MONTH_HOST_5", "MONTH_HOST_6", "MONTH_HOST_7", "MONTH_HOST_8", "MONTH_HOST_9", "MONTH_HOST_10", "MONTH_HOST_11", "MONTH_HOST_12",

					"MONTH_SESSION_1", "MONTH_SESSION_2", "MONTH_SESSION_3", "MONTH_SESSION_4", "MONTH_SESSION_5", "MONTH_SESSION_6", "MONTH_SESSION_7", "MONTH_SESSION_8", "MONTH_SESSION_9", "MONTH_SESSION_10", "MONTH_SESSION_11", "MONTH_SESSION_12",

					"MONTH_HIT_1", "MONTH_HIT_2", "MONTH_HIT_3", "MONTH_HIT_4", "MONTH_HIT_5", "MONTH_HIT_6", "MONTH_HIT_7", "MONTH_HIT_8", "MONTH_HIT_9", "MONTH_HIT_10", "MONTH_HIT_11", "MONTH_HIT_12",

					"MONTH_EVENT_1", "MONTH_EVENT_2", "MONTH_EVENT_3", "MONTH_EVENT_4", "MONTH_EVENT_5", "MONTH_EVENT_6", "MONTH_EVENT_7", "MONTH_EVENT_8", "MONTH_EVENT_9", "MONTH_EVENT_10", "MONTH_EVENT_11", "MONTH_EVENT_12"
					);

				if ($table_name=="b_stat_day")
				{
					$arSelect2 = array(
						"MONTH_GUEST_1", "MONTH_GUEST_2", "MONTH_GUEST_3", "MONTH_GUEST_4", "MONTH_GUEST_5", "MONTH_GUEST_6", "MONTH_GUEST_7", "MONTH_GUEST_8", "MONTH_GUEST_9", "MONTH_GUEST_10", "MONTH_GUEST_11", "MONTH_GUEST_12",

						"MONTH_NEW_GUEST_1", "MONTH_NEW_GUEST_2", "MONTH_NEW_GUEST_3", "MONTH_NEW_GUEST_4", "MONTH_NEW_GUEST_5", "MONTH_NEW_GUEST_6", "MONTH_NEW_GUEST_7", "MONTH_NEW_GUEST_8", "MONTH_NEW_GUEST_9", "MONTH_NEW_GUEST_10", "MONTH_NEW_GUEST_11", "MONTH_NEW_GUEST_12",

						"MONTH_FAVORITE_1", "MONTH_FAVORITE_2", "MONTH_FAVORITE_3", "MONTH_FAVORITE_4", "MONTH_FAVORITE_5", "MONTH_FAVORITE_6", "MONTH_FAVORITE_7", "MONTH_FAVORITE_8", "MONTH_FAVORITE_9", "MONTH_FAVORITE_10", "MONTH_FAVORITE_11", "MONTH_FAVORITE_12"
						);
				}
				break;
		}
		$arSelect = array_merge($arSelect1, $arSelect2);
		foreach($arSelect as $name) $sql .= "sum($name) $name,\n";
		$strSql = "
			SELECT $sql 1
			FROM
				$table_name
			WHERE
				$strSqlSearch
			";

		$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $rs;
	}

	function GetDailyList(&$by, &$order, &$arMaxMin, $arFilter=Array(), &$is_filtered, $get_maxmin="Y")
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$site_filtered = false;
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
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.ID",$val,$match);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "D.DATE_STAT<dateadd(day, 1, ".$DB->CharToDateFunction($val, "SHORT").")";
						break;
					case "HITS_1":
						$arSqlSearch[] = "D.HITS>='".intval($val)."'";
						break;
					case "HITS_2":
						$arSqlSearch[] = "D.HITS<='".intval($val)."'";
						break;
					case "SESSIONS_1":
						$arSqlSearch[] = "D.SESSIONS>='".intval($val)."'";
						break;
					case "SESSIONS_2":
						$arSqlSearch[] = "D.SESSIONS<='".intval($val)."'";
						break;
					case "NEW_GUESTS_1":
						$arSqlSearch[] = "D.NEW_GUESTS>='".intval($val)."'";
						break;
					case "NEW_GUESTS_2":
						$arSqlSearch[] = "D.NEW_GUESTS<='".intval($val)."'";
						break;
					case "FAVORITES_1":
						$arSqlSearch[] = "D.FAVORITES>='".intval($val)."'";
						break;
					case "FAVORITES_2":
						$arSqlSearch[] = "D.FAVORITES<='".intval($val)."'";
						break;
					case "GUESTS_1":
						$arSqlSearch[] = "D.GUESTS>='".intval($val)."'";
						break;
					case "GUESTS_2":
						$arSqlSearch[] = "D.GUESTS<='".intval($val)."'";
						break;
					case "HOSTS_1":
						$arSqlSearch[] = "D.C_HOSTS>='".intval($val)."'";
						break;
					case "HOSTS_2":
						$arSqlSearch[] = "D.C_HOSTS<='".intval($val)."'";
						break;
					case "EVENTS_1":
						$arSqlSearch[] = "D.C_EVENTS>='".intval($val)."'";
						break;
					case "EVENTS_2":
						$arSqlSearch[] = "D.C_EVENTS<='".intval($val)."'";
						break;
					case "SITE_ID":
						$site_filtered = true;
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("D.SITE_ID", $val, $match);
						break;
				}
			}
		}
		for($i=0;$i<count($arSqlSearch); $i++) $strSqlSearch .= " and (".$arSqlSearch[$i].") ";
		if ($by == "s_id")				$strSqlOrder = "ORDER BY D.ID";
		elseif ($by == "s_date")		$strSqlOrder = "ORDER BY D.DATE_STAT";
		elseif ($by == "s_hits")		$strSqlOrder = "ORDER BY D.HITS";
		elseif ($by == "s_hosts")		$strSqlOrder = "ORDER BY D.C_HOSTS";
		elseif ($by == "s_sessions")	$strSqlOrder = "ORDER BY D.SESSIONS";
		elseif ($by == "s_events")		$strSqlOrder = "ORDER BY D.C_EVENTS";
		elseif ($by == "s_guests")		$strSqlOrder = "ORDER BY D.GUESTS";
		elseif ($by == "s_new_guests")	$strSqlOrder = "ORDER BY D.NEW_GUESTS";
		elseif ($by == "s_favorites")	$strSqlOrder = "ORDER BY D.FAVORITES";
		else
		{
			$by = "s_date";
			$strSqlOrder = "ORDER BY D.DATE_STAT";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$table_name = ($site_filtered) ? "b_stat_day_site" : "b_stat_day";
		$strSql = "
			SELECT
				D.ID,
				D.HITS,
				D.C_HOSTS,
				D.SESSIONS,
				D.C_EVENTS,
				D.GUESTS,
				D.NEW_GUESTS,
				D.FAVORITES,
				D.TOTAL_HOSTS,
				D.AM_AVERAGE_TIME,
				D.AM_1, D.AM_1_3, D.AM_3_6, D.AM_6_9, D.AM_9_12, D.AM_12_15, D.AM_15_18, D.AM_18_21, D.AM_21_24, D.AM_24,
				D.AH_AVERAGE_HITS,
				D.AH_1, D.AH_2_5, D.AH_6_9, D.AH_10_13, D.AH_14_17, D.AH_18_21, D.AH_22_25, D.AH_26_29, D.AH_30_33, D.AH_34,
				D.DATE_STAT, ".$DB->DateToCharFunction("D.DATE_STAT","SHORT")." DATE_STAT,
				datepart(day, D.DATE_STAT) DAY,
				datepart(month, D.DATE_STAT) MONTH,
				datepart(year, D.DATE_STAT) YEAR,
				case datepart(weekday, D.DATE_STAT)
					when 1 then 6
					when 2 then 0
					when 3 then 1
					when 4 then 2
					when 5 then 3
					when 6 then 4
					when 7 then 5
					else 0
				end WDAY
			FROM
				".$table_name." D
			WHERE
				1=1
				".$strSqlSearch."
			$strSqlOrder
		";

		//echo "<pre>",htmlspecialcharsbx($strSql),"</pre>";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));

		if ($get_maxmin=="Y")
		{
			$strSql = "
				SELECT
					max(D.DATE_STAT) DATE_LAST,
					min(D.DATE_STAT) DATE_FIRST,
					datepart(day, max(D.DATE_STAT)) MAX_DAY,
					datepart(month, max(D.DATE_STAT)) MAX_MONTH,
					datepart(year, max(D.DATE_STAT)) MAX_YEAR,
					datepart(day, min(D.DATE_STAT)) MIN_DAY,
					datepart(month, min(D.DATE_STAT)) MIN_MONTH,
					datepart(year, min(D.DATE_STAT)) MIN_YEAR
				FROM
					".$table_name." D
				WHERE
					1=1
					".$strSqlSearch."
				";

			$a = $DB->Query($strSql, false, $err_mess.__LINE__);
			$ar = $a->Fetch();
			if (!is_array($arMaxMin)) $arMaxMin = array();
			$arMaxMin["MAX_DAY"]	= $ar["MAX_DAY"];
			$arMaxMin["MAX_MONTH"]	= $ar["MAX_MONTH"];
			$arMaxMin["MAX_YEAR"]	= $ar["MAX_YEAR"];
			$arMaxMin["MIN_DAY"]	= $ar["MIN_DAY"];
			$arMaxMin["MIN_MONTH"]	= $ar["MIN_MONTH"];
			$arMaxMin["MIN_YEAR"]	= $ar["MIN_YEAR"];
		}
		return $res;
	}

	function GetCommonValues($arFilter=Array(), $bIgnoreErrors=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$site_id = $arFilter["SITE_ID"];
		if(strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$site_filter = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}
		else
		{
			$site_filter = false;
			$strSqlSearch = "";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		if(strlen($date1)>0 && CheckDateTime($date1))
		{
			$is_filtered = true;
			$date_from = $DB->CharToDateFunction($date1, "SHORT");
			if(strlen($date2)>0 && CheckDateTime($date2))
			{
				$date_to = "dateadd(second, 86400-1, ".$DB->CharToDateFunction($date2, "SHORT").")";
				$strSqlPeriod = "sum(case when DATE_STAT not between $date_from and $date_to then 0 else ";
				$strT=" end)";
			}
			else
			{
				$strSqlPeriod = "sum(case when DATE_STAT < $date_from then 0 else ";
				$strT=" end)";
			}
		}
		elseif(strlen($date2)>0 && CheckDateTime($date2))
		{
			$is_filtered = true;
			$date_to = "dateadd(second, 86400-1, ".$DB->CharToDateFunction($date2, "SHORT").")";
			$strSqlPeriod = "sum(case when DATE_STAT > $date_to then 0 else ";
			$strT=" end)";
		}

		$strSql = "
			SELECT
				sum(HITS)									TOTAL_HITS,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then HITS else 0 end)	TODAY_HITS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then HITS else 0 end)	YESTERDAY_HITS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then HITS else 0 end)	B_YESTERDAY_HITS,

				sum(SESSIONS)									TOTAL_SESSIONS,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then SESSIONS else 0 end)	TODAY_SESSIONS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then SESSIONS else 0 end)	YESTERDAY_SESSIONS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then SESSIONS else 0 end)	B_YESTERDAY_SESSIONS,

				sum(C_EVENTS)									TOTAL_EVENTS,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then C_EVENTS else 0 end)	TODAY_EVENTS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then C_EVENTS else 0 end)	YESTERDAY_EVENTS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then C_EVENTS else 0 end)	B_YESTERDAY_EVENTS,

				sum(C_HOSTS)									TOTAL_HOSTS,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then C_HOSTS else 0 end)	TODAY_HOSTS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then C_HOSTS else 0 end)	YESTERDAY_HOSTS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then C_HOSTS else 0 end)	B_YESTERDAY_HOSTS,

				sum(NEW_GUESTS)									TOTAL_GUESTS,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then GUESTS else 0 end)	TODAY_GUESTS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then GUESTS else 0 end)	YESTERDAY_GUESTS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then GUESTS else 0 end)	B_YESTERDAY_GUESTS,

				sum(case when datediff(day, DATE_STAT, getdate())=0 then NEW_GUESTS else 0 end)	TODAY_NEW_GUESTS,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then NEW_GUESTS else 0 end)	YESTERDAY_NEW_GUESTS,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then NEW_GUESTS else 0 end)	B_YESTERDAY_NEW_GUESTS,

				sum(FAVORITES)									TOTAL_FAVORITES,
				sum(case when datediff(day, DATE_STAT, getdate())=0 then FAVORITES else 0 end)	TODAY_FAVORITES,
				sum(case when datediff(day, DATE_STAT, getdate())=1 then FAVORITES else 0 end)	YESTERDAY_FAVORITES,
				sum(case when datediff(day, DATE_STAT, getdate())=2 then FAVORITES else 0 end)	B_YESTERDAY_FAVORITES
				".
				($is_filtered ? ','.
					$strSqlPeriod.'HITS'.$strT.' PERIOD_HITS, '.
					$strSqlPeriod.'SESSIONS'.$strT.' PERIOD_SESSIONS, '.
					$strSqlPeriod.'C_EVENTS'.$strT.' PERIOD_EVENTS, '.
					$strSqlPeriod.'FAVORITES'.$strT.' PERIOD_FAVORITES, '.
					$strSqlPeriod.'NEW_GUESTS'.$strT.' PERIOD_NEW_GUESTS '
				: '')
				."
			FROM
				".($site_filter ? "b_stat_day_site" : "b_stat_day")."
			WHERE
				1=1
				".$strSqlSearch."
		";

		$result = false;
		$rs = $DB->Query($strSql, $bIgnoreErrors, $err_mess.__LINE__);
		if($rs)
		{
			if($result = $rs->Fetch())
			{
				foreach($result as $key=>$value)
					$result[$key] = intval($value);
				if(!$site_filter)
					$result["ONLINE_GUESTS"] = CUserOnline::GetGuestCount();
			}
		}
		return $result;
	}


	function GetRefererList(&$by, &$order, $arFilter=Array(), &$is_filtered, $limit=10)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$date_filtered = false;

		$site_id = $arFilter["SITE_ID"];
		if (strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$is_filtered = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		$date_from = $DB->CharToDateFunction($date1, "SHORT");
		$date_to = "dateadd(second, 86400-1, ".$DB->CharToDateFunction($date2, "SHORT").")";
		if (strlen($date1)>0)
		{
			$date_filtered = $is_filtered = true;
			if (strlen($date2)>0)
				$strSqlPeriod = " sum(case when DATE_HIT between $date_from and $date_to then 1 else 0 end)";
			else
				$strSqlPeriod = " sum(case when DATE_HIT >= $date_from then 1 else 0 end)";
		}
		elseif (strlen($date2)>0)
		{
			$date_filtered = $is_filtered = true;
			$strSqlPeriod = " sum(case when DATE_HIT <= $date_to then 1 else 0 end)";
		}

		$strSqlOrder = "";
		if ($by == "ref_server") $strSqlOrder = " ORDER BY SITE_NAME ";
		elseif($by == "ref_today") $strSqlOrder = " ORDER BY TODAY_REFERERS ";
		elseif($by == "ref_yesterday") $strSqlOrder = " ORDER BY YESTERDAY_REFERERS ";
		elseif($by == "ref_bef_yesterday") $strSqlOrder = " ORDER BY B_YESTERDAY_REFERERS ";
		elseif($by == "ref_total") $strSqlOrder = " ORDER BY TOTAL_REFERERS ";
		elseif($by == "ref_period" && $date_filtered) $strSqlOrder = " ORDER BY PERIOD_REFERERS";
		else
		{
			$by = "ref_today";
			$strSqlOrder = "ORDER BY TODAY_REFERERS desc, YESTERDAY_REFERERS desc, B_YESTERDAY_REFERERS desc, TOTAL_REFERERS ";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		$strSql = "
			SELECT /*TOP*/
				SITE_NAME,
				count('x') TOTAL_REFERERS,
				sum(case when datediff(day, DATE_HIT, getdate())=0 then 1 else 0 end) TODAY_REFERERS,
				sum(case when datediff(day, DATE_HIT, getdate())=1 then 1 else 0 end) YESTERDAY_REFERERS,
				sum(case when datediff(day, DATE_HIT, getdate())=2 then 1 else 0 end) B_YESTERDAY_REFERERS
				".
				($date_filtered ? ','.$strSqlPeriod.' as PERIOD_REFERERS ' : '')
				."
			FROM
				b_stat_referer_list
			WHERE
				1=1
				".$strSqlSearch."
			GROUP BY
				SITE_NAME
			".$strSqlOrder."
		";
		if(intval($limit)>0)
		{
			$strSql = str_replace("/*TOP*/", "TOP ".intval($limit), $strSql);
		}
		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}

	function GetPhraseList(&$s_by, &$s_order, $arFilter=Array(), &$is_filtered, $limit=10)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$date_filtered = false;

		$site_id = $arFilter["SITE_ID"];
		if (strlen($site_id)>0 && $site_id!="NOT_REF")
		{
			$is_filtered = true;
			$strSqlSearch = " and SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
		}

		$date1 = $arFilter["DATE1"];
		$date2 = $arFilter["DATE2"];
		$date_from = $DB->CharToDateFunction($date1, "SHORT");
		$date_to = "dateadd(second, 86400-1, ".$DB->CharToDateFunction($date2, "SHORT").")";
		if (strlen($date1)>0)
		{
			$date_filtered = $is_filtered = true;
			if (strlen($date2)>0)
				$strSqlPeriod = " sum(case when DATE_HIT between $date_from and $date_to then 1 else 0 end)";
			else
				$strSqlPeriod = " sum(case when DATE_HIT >= $date_from then 1 else 0 end)";
		}
		elseif (strlen($date2)>0)
		{
			$date_filtered = $is_filtered = true;
			$strSqlPeriod = " sum(case when DATE_HIT <= $date_to then 1 else 0 end)";
		}

		$strSqlOrder = "";
		if ($s_by == "s_phrase") $strSqlOrder = " ORDER BY PHRASE ";
		elseif ($s_by == "s_today") $strSqlOrder = " ORDER BY TODAY_PHRASES ";
		elseif ($s_by == "s_yesterday") $strSqlOrder = " ORDER BY YESTERDAY_PHRASES ";
		elseif ($s_by == "s_bef_yesterday") $strSqlOrder = " ORDER BY B_YESTERDAY_PHRASES ";
		elseif ($s_by == "s_total") $strSqlOrder = " ORDER BY TOTAL_PHRASES ";
		elseif($by == "s_period" && $date_filtered) $strSqlOrder = " ORDER BY PERIOD_PHRASES ";
		else
		{
			$s_by = "s_today";
			$strSqlOrder = " ORDER BY TODAY_PHRASES desc, YESTERDAY_PHRASES desc, B_YESTERDAY_PHRASES desc, TOTAL_PHRASES ";
		}
		if ($s_order != "asc")
		{
			$strSqlOrder .= " desc ";
			$s_order="desc";
		}

		$strSql = "
			SELECT /*TOP*/
				PHRASE,
				count('x') TOTAL_PHRASES,
				sum(case when datediff(day, DATE_HIT, getdate())=0 then 1 else 0 end) TODAY_PHRASES,
				sum(case when datediff(day, DATE_HIT, getdate())=1 then 1 else 0 end) YESTERDAY_PHRASES,
				sum(case when datediff(day, DATE_HIT, getdate())=2 then 1 else 0 end) B_YESTERDAY_PHRASES
				".
				($date_filtered ? ','.$strSqlPeriod.' PERIOD_PHRASES ' : '')
				."
			FROM
				b_stat_phrase_list
			WHERE
				1=1
				".$strSqlSearch."
			GROUP BY
				PHRASE
			".$strSqlOrder."
		";
		if(intval($limit)>0)
		{
			$strSql = str_replace("/*TOP*/", "TOP ".intval($limit), $strSql);
		}
		return $DB->Query($strSql, false, $err_mess.__LINE__);
	}
}
?>

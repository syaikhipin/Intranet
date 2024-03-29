<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/statistic.php");

class CStatistics extends CAllStatistics
{
	function CleanUpTableByDate($cleanup_date, $table_name, $date_name)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if (strlen($cleanup_date)>0)
		{
			$stmp = MkDateTime(ConvertDateTime($cleanup_date,"D.M.Y"),"d.m.Y");
			if ($stmp)
			{
				$strSql = "DELETE FROM $table_name WHERE $date_name<TO_DATE('".ConvertDateTime($cleanup_date, "D.M.Y")."','dd.mm.yyyy')";
				$DB->Query($strSql, false, $err_mess.__LINE__);
			}
		}
	}

	function GetSessionDataByMD5($GUEST_MD5)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$php_session_time = intval(ini_get("session.gc_maxlifetime"));
		$strSql = "
			SELECT
				ID,
				SESSION_DATA
			FROM
				b_stat_session_data
			WHERE
				GUEST_MD5 = '".$DB->ForSql($GUEST_MD5)."'
			and	DATE_LAST > SYSDATE - $php_session_time/86400
			and ROWNUM<=1
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	function CleanUpPathDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "PATH_DAYS"));
		$STEPS = intval(COption::GetOptionString("statistic", "MAX_PATH_STEPS"));
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_path WHERE
				(
					TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS or
					DATE_STAT is null or
					STEPS>$STEPS
				)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "
				DELETE FROM b_stat_path_adv WHERE
				(
					TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS or
					DATE_STAT is null or
					STEPS>$STEPS
				)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpPathCache()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			set_time_limit(0);
			ignore_user_abort(true);
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');
			$php_session_time = intval(ini_get("session.gc_maxlifetime"));
			$strSql = "DELETE FROM b_stat_path_cache WHERE DATE_HIT < SYSDATE - $php_session_time/86400 or DATE_HIT is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		return "CStatistics::CleanUpPathCache();";
	}

	function CleanUpSessionData()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			set_time_limit(0);
			ignore_user_abort(true);
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');
			$php_session_time = intval(ini_get("session.gc_maxlifetime"));
			$strSql = "DELETE FROM b_stat_session_data WHERE DATE_LAST < SYSDATE - $php_session_time/86400 or DATE_LAST is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		return "CStatistics::CleanUpSessionData();";
	}

	function CleanUpSearcherDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "SEARCHER_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				SELECT
					ID,
					nvl(DYNAMIC_KEEP_DAYS,'$DAYS') as DYNAMIC_KEEP_DAYS
				FROM
					b_stat_searcher
				";
			$w = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($wr = $w->Fetch())
			{
				$SDAYS = intval($wr["DYNAMIC_KEEP_DAYS"]);
				$SID = intval($wr["ID"]);
				$strSql = "
					SELECT
						ID,
						TOTAL_HITS
					FROM
						b_stat_searcher_day
					WHERE
						SEARCHER_ID = $SID
					and	TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$SDAYS
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr=$z->Fetch())
				{
					$ID = $zr["ID"];
					if (intval($zr["TOTAL_HITS"])>0)
					{
						$arFields = Array(
							"DATE_CLEANUP"	=> $DB->GetNowFunction(),
							"TOTAL_HITS"	=> "TOTAL_HITS + ".intval($zr["TOTAL_HITS"]),
							);
						$DB->Update("b_stat_searcher",$arFields,"WHERE ID='$SID'",$err_mess.__LINE__);
					}
					$strSql = "DELETE FROM b_stat_searcher_day WHERE ID='$ID'";
					$DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
		}
	}

	function CleanUpEventDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "EVENT_DYNAMIC_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "
				SELECT
					ID,
					nvl(DYNAMIC_KEEP_DAYS,'$DAYS') as DYNAMIC_KEEP_DAYS
				FROM
					b_stat_event
				";
			$w = $DB->Query($strSql, false, $err_mess.__LINE__);
			while ($wr = $w->Fetch())
			{
				$EDAYS = intval($wr["DYNAMIC_KEEP_DAYS"]);
				$EID = intval($wr["ID"]);
				$strSql = "
					SELECT
						ID,
						COUNTER,
						MONEY
					FROM
						b_stat_event_day
					WHERE
						EVENT_ID = $EID
					and	TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$EDAYS
				";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($zr=$z->Fetch())
				{
					$ID = $zr["ID"];
					if (intval($zr["COUNTER"])>0)
					{
						$arFields = Array(
							"DATE_CLEANUP"	=> $DB->GetNowFunction(),
							"COUNTER"		=> "COUNTER + ".intval($zr["COUNTER"]),
							"MONEY"			=> "MONEY + ".roundDB($zr["MONEY"])
							);
						$DB->Update("b_stat_event",$arFields,"WHERE ID='$EID'",$err_mess.__LINE__);
					}
					$strSql = "DELETE FROM b_stat_event_day WHERE ID='$ID'";
					$DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
		}
	}

	function CleanUpAdvDynamic()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "ADV_DAYS"));
		if ($DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_adv_day WHERE TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS or DATE_STAT is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "DELETE FROM b_stat_adv_event_day WHERE TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS or DATE_STAT is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpPhrases()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$PHRASE_DAYS = COption::GetOptionString("statistic", "PHRASES_DAYS");
		$PHRASE_DAYS = intval($PHRASE_DAYS);
		if ($PHRASE_DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_phrase_list WHERE TRUNC(DATE_HIT)<=TRUNC(SYSDATE)-$PHRASE_DAYS or DATE_HIT is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpRefererList()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$REFERER_DAYS = COption::GetOptionString("statistic", "REFERER_LIST_DAYS");
		$REFERER_DAYS = intval($REFERER_DAYS);
		if ($REFERER_DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_referer_list WHERE TRUNC(DATE_HIT)<=TRUNC(SYSDATE)-$REFERER_DAYS or DATE_HIT is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpReferer()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "REFERER_DAYS"));
		$TOP = intval(COption::GetOptionString("statistic", "REFERER_TOP"));
		$DAYS = intval($DAYS);
		if ($DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_referer R
				WHERE
					(TRUNC(R.DATE_LAST)<=TRUNC(SYSDATE)-$DAYS or R.DATE_LAST is null)
				and R.ID not in (SELECT ID FROM (SELECT RT.ID FROM b_stat_referer RT ORDER BY RT.SESSIONS desc) WHERE ROWNUM<=$TOP)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpVisits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$VISIT_DAYS = COption::GetOptionString("statistic", "VISIT_DAYS");
		$VISIT_DAYS = intval($VISIT_DAYS);
		if ($VISIT_DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_page WHERE
				(
					TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$VISIT_DAYS or
					DATE_STAT is null
				)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "
				DELETE FROM b_stat_page_adv WHERE
				(
					TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$VISIT_DAYS or
					DATE_STAT is null
				)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpCities()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = COption::GetOptionInt("statistic", "CITY_DAYS");
		if($DAYS >= 0)
		{
			$strSql = "DELETE FROM b_stat_city_day WHERE TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpCountries()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = COption::GetOptionInt("statistic", "COUNTRY_DAYS");
		if($DAYS >= 0)
		{
			$strSql = "DELETE FROM b_stat_country_day WHERE TRUNC(DATE_STAT)<=TRUNC(SYSDATE)-$DAYS";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpGuests()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$GUEST_DAYS = COption::GetOptionString("statistic", "GUEST_DAYS");
		$GUEST_DAYS = intval($GUEST_DAYS);
		if ($GUEST_DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_guest WHERE TRUNC(LAST_DATE)<=TRUNC(SYSDATE)-$GUEST_DAYS or LAST_DATE is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpSessions()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$SESSION_DAYS = COption::GetOptionString("statistic", "SESSION_DAYS");
		$SESSION_DAYS = intval($SESSION_DAYS);
		if ($SESSION_DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_session WHERE TRUNC(DATE_LAST)<=TRUNC(SYSDATE)-$SESSION_DAYS or DATE_LAST is null";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpHits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$HIT_DAYS = COption::GetOptionString("statistic", "HIT_DAYS");
		$HIT_DAYS = intval($HIT_DAYS);
		if ($HIT_DAYS>=0)
		{
			$strSql = "DELETE FROM b_stat_hit WHERE DATE_HIT<=(TRUNC(SYSDATE-1)-$HIT_DAYS)";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpSearcherHits()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "SEARCHER_HIT_DAYS"));
		$strSql = "
			DELETE FROM b_stat_searcher_hit WHERE
				TRUNC(DATE_HIT)<=TRUNC(SYSDATE)-nvl(HIT_KEEP_DAYS,$DAYS) or
				DATE_HIT is null
			";
		$DB->Query($strSql, false, $err_mess.__LINE__);
	}

	function CleanUpAdvGuests()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ADV_GUEST_DAYS = COption::GetOptionString("statistic", "ADV_GUEST_DAYS");
		$ADV_GUEST_DAYS = intval($ADV_GUEST_DAYS);
		if ($ADV_GUEST_DAYS>=0)
		{
			$strSql = "
				DELETE FROM b_stat_adv_guest WHERE
				(
					TRUNC(DATE_GUEST_HIT)<=TRUNC(SYSDATE)-$ADV_GUEST_DAYS or
					DATE_GUEST_HIT is null
				)
				and
				(
					TRUNC(DATE_HOST_HIT)<=TRUNC(SYSDATE)-$ADV_GUEST_DAYS or
					DATE_HOST_HIT is null
				)
				";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
	}

	function CleanUpEvents()
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$DAYS = intval(COption::GetOptionString("statistic", "EVENTS_DAYS"));
		$strSql = "
			DELETE FROM b_stat_event_list WHERE
				TRUNC(DATE_ENTER)<=TRUNC(SYSDATE)-nvl(KEEP_DAYS,$DAYS) or
				DATE_ENTER is null
			";
		$DB->Query($strSql, false, $err_mess.__LINE__);
	}

	function SetNewDayForSite($SITE_ID=false, $HOSTS=0, $TOTAL_HOSTS=0, $SESSIONS=0, $HITS=0)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		if ($SITE_ID===false)
		{
			$SITE_ID = "";
			if (!(defined("ADMIN_SECTION") && ADMIN_SECTION===true) && defined("SITE_ID"))
			{
				$SITE_ID = SITE_ID;
			}
		}
		if (strlen($SITE_ID)>0)
		{
			$strSql = "
				INSERT INTO b_stat_day_site(
					ID,
					SITE_ID,
					DATE_STAT,
					TOTAL_HOSTS,
					C_HOSTS,
					SESSIONS,
					HITS
					)
				SELECT
					SQ_B_STAT_DAY_SITE.NEXTVAL,
					'".$DB->ForSql($SITE_ID, 2)."',
					trunc(SYSDATE),
					nvl(PREV.MAX_TOTAL_HOSTS,0) + ".intval($TOTAL_HOSTS).",
					".intval($HOSTS).",
					".intval($SESSIONS).",
					".intval($HITS)."
				FROM
					(SELECT	max(TOTAL_HOSTS) AS MAX_TOTAL_HOSTS	FROM b_stat_day_site WHERE SITE_ID = '".$DB->ForSql($SITE_ID, 2)."') PREV
				";
			if ($DB->Query($strSql, true, $err_mess.__LINE__))
			{
				$strSql = "
					SELECT * FROM (
						SELECT
							D.ID,
							".$DB->DateToCharFunction("D.DATE_STAT","SHORT")."		DATE_STAT
						FROM
							b_stat_day_site D
						WHERE
							D.DATE_STAT <> trunc(SYSDATE)
						and D.SITE_ID = '".$DB->ForSql($SITE_ID, 2)."'
						ORDER BY
							D.DATE_STAT desc
						)
					WHERE ROWNUM<=1
					";
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				if ($ar = $rs->Fetch())
				{
					$arF = CSession::GetAttentiveness($ar["DATE_STAT"], $SITE_ID);
					if (is_array($arF)) $DB->Update("b_stat_day_site",$arF,"WHERE ID='".$ar["ID"]."'",$err_mess.__LINE__);
				}
			}
		}
	}

	function SetNewDay($HOSTS=0, $TOTAL_HOSTS=0, $SESSIONS=0, $HITS=0, $NEW_GUESTS=0, $GUESTS=0, $FAVORITES=0)
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			$err_mess = "File: ".__FILE__."<br>Line: ";
			$DB = CDatabase::GetModuleConnection('statistic');
			$strSql = "
				INSERT INTO b_stat_day(
					ID,
					DATE_STAT,
					TOTAL_HOSTS,
					C_HOSTS,
					SESSIONS,
					GUESTS,
					FAVORITES,
					HITS,
					NEW_GUESTS
					)
				SELECT
					SQ_B_STAT_DAY.NEXTVAL,
					trunc(SYSDATE),
					nvl(PREV.MAX_TOTAL_HOSTS,0) + ".intval($TOTAL_HOSTS).",
					".intval($HOSTS).",
					".intval($SESSIONS).",
					".intval($GUESTS).",
					".intval($FAVORITES).",
					".intval($HITS).",
					".intval($NEW_GUESTS)."
				FROM
					(SELECT	max(TOTAL_HOSTS) AS MAX_TOTAL_HOSTS	FROM b_stat_day) PREV
				";
			if ($DB->Query($strSql, true, $err_mess.__LINE__))
			{
				$strSql = "
					SELECT * FROM (
						SELECT
							D.ID,
							".$DB->DateToCharFunction("D.DATE_STAT","SHORT")."		DATE_STAT
						FROM
							b_stat_day D
						WHERE
							D.DATE_STAT <> trunc(SYSDATE)
						ORDER BY
							D.DATE_STAT desc
						)
					WHERE ROWNUM<=1
					";
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				if ($ar = $rs->Fetch())
				{
					$arF = CSession::GetAttentiveness($ar["DATE_STAT"]);
					if (is_array($arF)) $DB->Update("b_stat_day",$arF,"WHERE ID='".$ar["ID"]."'",$err_mess.__LINE__);
				}
			}
		}
		return "CStatistics::SetNewDay();";
	}

	function DBDateAdd($date, $days=1)
	{
		return $date." + ".$days;
	}

	function DBTopSql($strSql, $nTopCount=false)
	{
		if($nTopCount===false)
			$nTopCount = intval(COption::GetOptionString("statistic","RECORDS_LIMIT"));
		else
			$nTopCount = intval($nTopCount);
		if($nTopCount>0)
			return "SELECT * FROM (".str_replace("/*TOP*/", "", $strSql).") WHERE ROWNUM<=".$nTopCount;
		else
			return str_replace("/*TOP*/", "", $strSql);
	}

	function DBFirstDate($strSql)
	{
		return "nvl(".$strSql.",to_date('01.01.1980','DD.MM.YYYY'))";
	}

	function DBDateDiff($date1, $date2)
	{
		return "86400*(".$date1."-".$date2.")";
	}
}
?>
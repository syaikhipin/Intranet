<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/statevent.php");
class CStatEvent extends CAllStatEvent
{
	function GetListByGuest($GUEST_ID, $EVENT_ID=false, $EVENT3=false, $SEC=false)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$strSqlSearch = "";
		if ($EVENT_ID!==false)
			$strSqlSearch .= " and E.EVENT_ID='".intval($EVENT_ID)."' ";
		if ($EVENT3!==false)
			$strSqlSearch .= " and E.EVENT3='".$DB->ForSql($EVENT3,255)."' ";
		if ($SEC!==false)
			$strSqlSearch .= " and E.DATE_ENTER > SYSDATE - ".intval($SEC)."/86400 ";

		$strSql = "
			SELECT
				E.ID
			FROM
				b_stat_event_list E
			WHERE
				E.GUEST_ID = ".intval($GUEST_ID)."
			$strSqlSearch
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	function Add($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAM, $MONEY="", $CURRENCY="", $CHARGEBACK="N")
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');

		$EVENT_ID = intval($EVENT_ID);
		$strSql = "SELECT KEEP_DAYS FROM b_stat_event WHERE ID = $EVENT_ID";
		$rsEvent = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($arEvent = $rsEvent->Fetch())
		{
			$MONEY = doubleval($MONEY);

			// ���� ������� ������ �� ������������
			if (strlen(trim($CURRENCY))>0)
			{
				$base_currency = GetStatisticBaseCurrency();
				if (strlen($base_currency)>0)
				{
					if ($CURRENCY!=$base_currency)
					{
						if (CModule::IncludeModule("currency"))
						{
							$rate = CCurrencyRates::GetConvertFactor($CURRENCY, $base_currency);
							if ($rate>0 && $rate!=1) $MONEY = $MONEY * $rate;
						}
					}
				}
			}
			$MONEY = round($MONEY,2);

			$arr = CStatEvent::DecodeGid($PARAM);
			$SESSION_ID		= intval($arr["SESSION_ID"]);
			$GUEST_ID		= intval($arr["GUEST_ID"]);
			$COUNTRY_ID		= $arr["COUNTRY_ID"];
			$ADV_ID			= intval($arr["ADV_ID"]);
			$ADV_BACK		= ($arr["ADV_BACK"]=="Y") ? "Y" : "N";
			$CHARGEBACK		= ($CHARGEBACK=="Y") ? "Y" : "N";
			$SITE_ID		= $arr["SITE_ID"];

			$DATE_ENTER = strlen(trim($DATE_ENTER))>0 ? $DATE_ENTER : GetTime(time(),"FULL");
			$TIME_ENTER_TMSTMP = MakeTimeStamp($DATE_ENTER);
			if (!$TIME_ENTER_TMSTMP)
			{
				$DATE_ENTER = GetTime(time(),"FULL");
				$TIME_ENTER_TMSTMP = MakeTimeStamp($DATE_ENTER);
			}
			$TIME_ENTER_SQL = "TO_DATE('".ConvertDateTime($DATE_ENTER, "DD.MM.YYYY HH:MI:SS")."','DD.MM.YYYY HH24:MI:SS')";
			$DAY_ENTER_SQL = "TO_DATE('".ConvertDateTime($DATE_ENTER, "DD.MM.YYYY")."','DD.MM.YYYY')";

			$DB->StartTransaction();

			$arFields = array(
				"EVENT_ID"		=> $EVENT_ID,
				"EVENT3"		=> "'".$DB->ForSql($EVENT3,255)."'",
				"MONEY"			=> roundDB($MONEY),
				"DATE_ENTER"	=> $TIME_ENTER_SQL,
				"SESSION_ID"	=> (intval($SESSION_ID)>0) ? intval($SESSION_ID) : "null",
				"GUEST_ID"		=> (intval($GUEST_ID)>0) ? intval($GUEST_ID) : "null",
				"ADV_ID"		=> (intval($ADV_ID)>0) ? intval($ADV_ID) : "null",
				"ADV_BACK"		=> ($ADV_BACK=="Y") ? "'Y'" : "'N'",
				"COUNTRY_ID"	=> (strlen($COUNTRY_ID)>0) ? "'".$DB->ForSql($COUNTRY_ID,2)."'" : "null",
				"KEEP_DAYS"		=> (intval($arEvent["KEEP_DAYS"])>0) ? intval($arEvent["KEEP_DAYS"]) : "null",
				"CHARGEBACK"	=> "'".$CHARGEBACK."'",
				"SITE_ID"		=> (strlen($SITE_ID)>0) ? "'".$DB->ForSql($SITE_ID,2)."'" : "null"
				);
			$EVENT_LIST_ID = $DB->Insert("b_stat_event_list",$arFields, $err_mess.__LINE__);

			// ����������� ������� ��� ������
			if (strlen($COUNTRY_ID)>0)
				CStatistics::UpdateCountry($COUNTRY_ID, Array("C_EVENTS" => 1));

			// ���� ����� ��������� ���� ������� ������� ��� ������� ���� �������
			$arFields = Array("DATE_ENTER" => $DB->GetNowFunction());
			$DB->Update("b_stat_event",$arFields,"WHERE ID='".$EVENT_ID."' and DATE_ENTER is null",$err_mess.__LINE__);
			// ��������� ������� �� ���� ��� ������� ���� �������
			$arFields = Array(
					"DATE_LAST"	=> $DB->GetNowFunction(),
					"COUNTER"	=> "COUNTER + 1",
					"MONEY"		=> "MONEY + ".roundDB($MONEY)
					);
			$rows = $DB->Update("b_stat_event_day",$arFields,"WHERE EVENT_ID='".$EVENT_ID."' and DATE_STAT = trunc(".$TIME_ENTER_SQL.")",$err_mess.__LINE__);
			// ���� ������� �� ���� ��� ��
			if (intval($rows)<=0)
			{
				// ��������� ���
				$arFields_i = Array(
					"DATE_STAT"	=> $DAY_ENTER_SQL,
					"DATE_LAST"	=> $TIME_ENTER_SQL,
					"EVENT_ID"	=> $EVENT_ID,
					"COUNTER"	=> 1,
					"MONEY"		=> roundDB($MONEY)
					);
				$DB->Insert("b_stat_event_day",$arFields_i, $err_mess.__LINE__);
			}
			elseif (intval($rows)>1) // ���� �������� ����� ������ ��� ��
			{
				// ������ ������
				$strSql = "SELECT ID FROM b_stat_event_day WHERE EVENT_ID='".$EVENT_ID."' and DATE_STAT = trunc(".$TIME_ENTER_SQL.") ORDER BY ID";
				$i=0;
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch())
				{
					$i++;
					if ($i>1)
					{
						$strSql = "DELETE FROM b_stat_event_day WHERE ID = ".$ar["ID"];
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
				}
			}

			// ��������� ������ � �����
			$arFields = Array("C_EVENTS" => "C_EVENTS+1");
			$DB->Update("b_stat_session",$arFields,"WHERE ID=".$SESSION_ID, $err_mess.__LINE__);
			$DB->Update("b_stat_guest",$arFields,"WHERE ID=".$GUEST_ID, $err_mess.__LINE__);

			// ��������� ������� �������
			$arFields = Array("C_EVENTS" => "C_EVENTS + 1");
			$DB->Update("b_stat_day",$arFields,"WHERE DATE_STAT = trunc(".$DAY_ENTER_SQL.")",$err_mess.__LINE__);

			// ����������� ������� ��������
			CTraffic::IncParam(array("EVENT" => 1), array(), false, $DATE_ENTER);

			// ���� ���� ��������� ��
			if (strlen($SITE_ID)>0)
			{
				// ��������� ������� �������
				$arFields = Array("C_EVENTS" => "C_EVENTS+1");
				$DB->Update("b_stat_day_site", $arFields, "WHERE SITE_ID='".$DB->ForSql($SITE_ID,2)."' and DATE_STAT = trunc(".$DAY_ENTER_SQL.")", $err_mess.__LINE__);

				// ����������� ������� ��������
				CTraffic::IncParam(array(), array("EVENT" => 1), $SITE_ID, $DATE_ENTER);
			}

			if ($ADV_ID>0)
			{
				$a = $DB->Query("SELECT 'x' FROM b_stat_adv WHERE ID='".$ADV_ID."'", false, $err_mess.__LINE__);
				// ���� ���� ����� ��������� �������� ��
				if ($ar = $a->Fetch())
				{
					// ����������� ����� ��������� ��������
					if ($MONEY!=0)
					{
						$sign = ($CHARGEBACK=="Y") ? "-" : "+";
						$arFields = array("REVENUE" => "REVENUE ".$sign." ".$MONEY);
						$DB->Update("b_stat_adv",$arFields,"WHERE ID='$ADV_ID'",$err_mess.__LINE__);
					}
					// ��������� ������� ������ ��������� �������� � ���� �������
					if ($ADV_BACK=="Y")
					{
						$arFields = array(
							"COUNTER_BACK"	=> "COUNTER_BACK + 1",
							"MONEY_BACK"	=> "MONEY_BACK + ".roundDB($MONEY)
							);
					}
					else
					{
						$arFields = array(
							"COUNTER"	=> "COUNTER + 1",
							"MONEY"		=> "MONEY + ".roundDB($MONEY)
							);
					}
					$rows = $DB->Update("b_stat_adv_event",$arFields,"WHERE ADV_ID='$ADV_ID' and EVENT_ID='$EVENT_ID'",$err_mess.__LINE__);
					// ���� ������ ��� ��
					if (intval($rows)<=0 && intval($ADV_ID)>0 && intval($EVENT_ID)>0)
					{
						// ��������� ������
						$arFields = Array(
							"ADV_ID"	=> "'".intval($ADV_ID)."'",
							"EVENT_ID"	=> "'".intval($EVENT_ID)."'"
							);
						if ($ADV_BACK=="Y")
						{
							$arFields["COUNTER_BACK"] = 1;
							$arFields["MONEY_BACK"] = roundDB($MONEY);
						}
						else
						{
							$arFields["COUNTER"] = 1;
							$arFields["MONEY"] = roundDB($MONEY);
						}
						$DB->Insert("b_stat_adv_event", $arFields, $err_mess.__LINE__);
					}

					// ��������� ������� ������ �� ����
					if ($ADV_BACK=="Y")
					{
						$arFields = array(
							"COUNTER_BACK"	=> "COUNTER_BACK + 1",
							"MONEY_BACK"	=> "MONEY_BACK + ".roundDB($MONEY)
							);
					}
					else
					{
						$arFields = array(
							"COUNTER"	=> "COUNTER + 1",
							"MONEY"		=> "MONEY + ".roundDB($MONEY)
							);
					}
					$rows = $DB->Update("b_stat_adv_event_day",$arFields,"WHERE ADV_ID='$ADV_ID' and EVENT_ID='$EVENT_ID' and DATE_STAT = trunc(".$DAY_ENTER_SQL.")", $err_mess.__LINE__);
					// ���� ��� ����� ������ ��
					if (intval($rows)<=0 && intval($ADV_ID)>0 && intval($EVENT_ID)>0)
					{
						// ��������� ��
						$arFields = Array(
							"DATE_STAT"	=> $DAY_ENTER_SQL,
							"ADV_ID"	=> "'".$ADV_ID."'",
							"EVENT_ID"	=> "'".$EVENT_ID."'"
							);
						if ($ADV_BACK=="Y")
						{
							$arFields["COUNTER_BACK"] = 1;
							$arFields["MONEY_BACK"] = roundDB($MONEY);
						}
						else
						{
							$arFields["COUNTER"] = 1;
							$arFields["MONEY"] = roundDB($MONEY);
						}
						$DB->Insert("b_stat_adv_event_day", $arFields, $err_mess.__LINE__);
					}
				}
			}
			$DB->Commit();
		}
		return intval($EVENT_LIST_ID);
	}

	function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			while (list($key, $value) = each($arFilter))
			{
				if ($key=="CURRENCY") $CURRENCY = $value;
			}
			reset($arFilter);
		}
		$rate = 1;
		$base_currency = GetStatisticBaseCurrency();
		$view_currency = $base_currency;
		if (strlen($base_currency)>0)
		{
			if (CModule::IncludeModule("currency"))
			{
				if ($CURRENCY!=$base_currency && strlen($CURRENCY)>0)
				{
					$rate = CCurrencyRates::GetConvertFactor($base_currency, $CURRENCY);
					$view_currency = $CURRENCY;
				}
			}
		}
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
					case "EVENT_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "EVENT_NAME":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("V.NAME",$val, $match);
						break;
					case "EVENT1":
					case "EVENT2":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("V.".$key,$val, $match);
						break;
					case "EVENT3":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.EVENT3",$val, $match);
						break;
					case "DATE":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER=".$DB->CharToDateFunction($val);
						break;
					case "DATE1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "E.DATE_ENTER<".$DB->CharToDateFunction($val, "SHORT")."+1";
						break;
					case "REDIRECT_URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.REDIRECT_URL",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "MONEY":
						$arSqlSearch_h[] = "round((E.MONEY*$rate),2)='".roundDB($val)."'";
						break;
					case "MONEY1":
						$arSqlSearch_h[] = "round((E.MONEY*$rate),2)>='".roundDB($val)."'";
						break;
					case "MONEY2":
						$arSqlSearch_h[] = "round((E.MONEY*$rate),2)<='".roundDB($val)."'";
						break;
					case "SESSION_ID":
					case "GUEST_ID":
					case "ADV_ID":
					case "HIT_ID":
					case "COUNTRY_ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match);
						break;
					case "ADV_BACK":
						$arSqlSearch[] = ($val=="Y") ? "E.ADV_BACK='Y'" : "E.ADV_BACK='N'";
						break;
					case "REFERER_URL":
					case "URL":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.".$key,$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "COUNTRY":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("C.NAME", $val, $match);
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.SITE_ID", $val, $match);
						break;
					case "REFERER_SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.REFERER_SITE_ID", $val, $match);
						break;
				}
			}
		}
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		for($i=0;$i<count($arSqlSearch_h); $i++) $strSqlSearch_h .= " and (".$arSqlSearch_h[$i].") ";
		if ($by == "s_id")					$strSqlOrder = "ORDER BY E.ID";
		elseif ($by == "s_site_id")			$strSqlOrder = "ORDER BY E.SITE_ID";
		elseif ($by == "s_event_id" || $by == "s_type_id")		$strSqlOrder = "ORDER BY E.EVENT_ID";
		elseif ($by == "s_date_enter")		$strSqlOrder = "ORDER BY E.DATE_ENTER";
		elseif ($by == "s_adv_id")			$strSqlOrder = "ORDER BY E.ADV_ID";
		elseif ($by == "s_adv_back")		$strSqlOrder = "ORDER BY E.ADV_BACK";
		elseif ($by == "s_session_id")		$strSqlOrder = "ORDER BY E.SESSION_ID";
		elseif ($by == "s_guest_id")		$strSqlOrder = "ORDER BY E.GUEST_ID";
		elseif ($by == "s_hit_id")			$strSqlOrder = "ORDER BY E.HIT_ID";
		elseif ($by == "s_url")				$strSqlOrder = "ORDER BY E.URL";
		elseif ($by == "s_referer_url")		$strSqlOrder = "ORDER BY E.REFERER_URL";
		elseif ($by == "s_redirect_url")	$strSqlOrder = "ORDER BY E.REDIRECT_URL";
		elseif ($by == "s_country_id")		$strSqlOrder = "ORDER BY E.COUNTRY_ID";
		elseif ($by == "s_money")			$strSqlOrder = "ORDER BY E.MONEY";
		else
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY E.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}
		if($arFilter["GROUP"]=="total")
		{
			$strSql =	"
				SELECT
					COUNT(*)							COUNTER,
					round(sum(decode(E.CHARGEBACK,'Y',-E.MONEY,E.MONEY)*$rate),2)	MONEY,
					'$view_currency'						CURRENCY
				FROM
					b_stat_event_list E
				INNER JOIN b_stat_event V ON (V.ID=E.EVENT_ID)
				LEFT JOIN b_stat_country C ON (C.ID=E.COUNTRY_ID)
				WHERE
				$strSqlSearch
				HAVING
					1=1
					$strSqlSearch_h
				";
		}
		else
		{
			$strSql =	"
				SELECT
					E.ID, E.EVENT_ID, E.ADV_ID, E.ADV_BACK, E.COUNTRY_ID, E.SESSION_ID, E.GUEST_ID, E.HIT_ID, E.REFERER_URL, E.URL, E.REDIRECT_URL, E.CHARGEBACK, E.SITE_ID,  E.REFERER_SITE_ID,
					round((E.MONEY*$rate),2)												MONEY,
					'$view_currency'														CURRENCY,
					".$DB->DateToCharFunction("E.DATE_ENTER")."								DATE_ENTER,
					V.ID																	TYPE_ID,
					V.DESCRIPTION, V.NAME, V.EVENT1, V.EVENT2, E.EVENT3,
					C.NAME																	COUNTRY_NAME,
					decode(V.NAME, null, nvl(V.EVENT1,'')||' / '||nvl(V.EVENT2,''), V.NAME)	EVENT
				FROM
					b_stat_event_list E
				INNER JOIN b_stat_event V ON (V.ID=E.EVENT_ID)
				LEFT JOIN b_stat_country C ON (C.ID=E.COUNTRY_ID)
				WHERE
				$strSqlSearch
				GROUP BY
					E.ID, E.EVENT_ID, E.SITE_ID, E.MONEY, E.ADV_ID, E.ADV_BACK, E.COUNTRY_ID, E.SESSION_ID, E.GUEST_ID, E.HIT_ID, E.REFERER_URL, E.URL, E.REDIRECT_URL, E.DATE_ENTER, V.ID, V.DESCRIPTION, V.NAME, V.EVENT1, V.EVENT2, E.EVENT3, C.NAME, E.CHARGEBACK, E.SITE_ID,  E.REFERER_SITE_ID
				HAVING
					1=1
					$strSqlSearch_h
				$strSqlOrder
				";
				$strSql = "SELECT * FROM ($strSql) WHERE ROWNUM<=".COption::GetOptionString("statistic","RECORDS_LIMIT");
		}

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch) || strlen($strSqlSearch_h)>0);
		return $res;
	}

	function Delete($ID)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		$strSql = "
			SELECT
				L.EVENT_ID,
				L.MONEY,
				L.SESSION_ID,
				L.GUEST_ID,
				L.ADV_ID,
				L.ADV_BACK,
				L.COUNTRY_ID,
				L.CHARGEBACK,
				L.SITE_ID,
				".$DB->DateToCharFunction("L.DATE_ENTER","SHORT")."	DATE_ENTER,
				".$DB->DateToCharFunction("L.DATE_ENTER","FULL")."	DATE_ENTER_FULL
			FROM
				b_stat_event_list L,
				b_stat_event E
			WHERE
				L.ID = '$ID'
			and E.ID = L.EVENT_ID
			";
		$a = $DB->Query($strSql, false, $err_mess.__LINE__);
		if ($ar = $a->Fetch())
		{
			$DB->StartTransaction();

			// ��������� ������� � ������
			CStatistics::UpdateCountry($ar["COUNTRY_ID"], Array("C_EVENTS" => 1), $ar["DATE_ENTER"], "SHORT", "-");

			// ��������� ������� �� ����
			$arFields = Array(
				"COUNTER"	=> "COUNTER-1",
				"MONEY"		=> "MONEY - ".roundDB($ar["MONEY"])
				);
			$rows = $DB->Update("b_stat_event_day",$arFields,"WHERE EVENT_ID='".intval($ar["EVENT_ID"])."' and DATE_STAT = trunc(TO_DATE('".ConvertDateTime($ar["DATE_ENTER"], "D.M.Y")."','dd.mm.yyyy'))",$err_mess.__LINE__);
			// ���� ��� ���� ������� ��
			if (intval($rows)<=0)
			{
				// �������� ������� �� ���� �������
				$arFields = Array(
					"COUNTER"	=> "COUNTER-1",
					"MONEY"		=> "MONEY - ".roundDB($ar["MONEY"])
					);
				$DB->Update("b_stat_event",$arFields,"WHERE ID='".intval($ar["EVENT_ID"])."'",$err_mess.__LINE__);
			}
			// ���� � ������ ���� ������� �������� �� �� ����� �������
			$strSql = "DELETE FROM b_stat_event_day WHERE COUNTER=0";
			$DB->Query($strSql,false,$err_mess.__LINE__);

			// ������ ������
			$arFields = Array("C_EVENTS" => "C_EVENTS-1");
			$DB->Update("b_stat_session",$arFields,"WHERE ID='".intval($ar["SESSION_ID"])."'",$err_mess.__LINE__);

			// ������ �����
			$DB->Update("b_stat_guest",$arFields,"WHERE ID='".intval($ar["GUEST_ID"])."'",$err_mess.__LINE__);

			if (intval($ar["ADV_ID"])>0)
			{
				// ��������� ����� ��������� ��������
				if (doubleval($ar["MONEY"])!=0)
				{
					$sign = ($ar["CHARGEBACK"]=="Y") ? "+" : "-";
					$arFields = array("REVENUE" => "REVENUE ".$sign." ".roundDB($ar["MONEY"]));
					$DB->Update("b_stat_adv",$arFields,"WHERE ID='".intval($ar["ADV_ID"])."'", $err_mess.__LINE__);
				}

				// ������ ������ � ��������� ���������
				if ($ar["ADV_BACK"]=="Y")
				{
					$arFields = array(
						"COUNTER_BACK"	=> "COUNTER_BACK - 1",
						"MONEY_BACK"	=> "MONEY_BACK - ".roundDB($ar["MONEY"]),
						);
				}
				else
				{
					$arFields = array(
						"COUNTER"	=> "COUNTER - 1",
						"MONEY"		=> "MONEY - ".roundDB($ar["MONEY"]),
						);
				}
				$DB->Update("b_stat_adv_event",$arFields,"WHERE ADV_ID='".intval($ar["ADV_ID"])."' and EVENT_ID='".$ar["EVENT_ID"]."'",$err_mess.__LINE__);

				// ������ ������ � ��������� ��������� �� ����
				if ($ar["ADV_BACK"]=="Y")
				{
					$arFields = array(
						"COUNTER_BACK"	=> "COUNTER_BACK - 1",
						"MONEY_BACK"	=> "MONEY_BACK - ".roundDB($ar["MONEY"]),
						);
				}
				else
				{
					$arFields = array(
						"COUNTER"	=> "COUNTER - 1",
						"MONEY"		=> "MONEY - ".roundDB($ar["MONEY"]),
						);
				}
				$DB->Update("b_stat_adv_event_day",$arFields,"WHERE ADV_ID='".intval($ar["ADV_ID"])."' and EVENT_ID='".$ar["EVENT_ID"]."' and DATE_STAT = trunc(TO_DATE('".ConvertDateTime($ar["DATE_ENTER"], "D.M.Y")."','dd.mm.yyyy'))",$err_mess.__LINE__);
			}

			// ���� � ������� �������� ������� �������� �� �� ����� �������
			$strSql = "DELETE FROM b_stat_adv_event WHERE COUNTER<=0 and COUNTER_BACK<=0";
			$DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql = "DELETE FROM b_stat_adv_event_day WHERE COUNTER<=0 and COUNTER_BACK<=0";
			$DB->Query($strSql, false, $err_mess.__LINE__);

			// ��������� ������� �� ����
			$arFields = Array("C_EVENTS" => "C_EVENTS-1");
			$DB->Update("b_stat_day",$arFields,"WHERE DATE_STAT = trunc(TO_DATE('".ConvertDateTime($ar["DATE_ENTER"], "D.M.Y")."','dd.mm.yyyy'))", $err_mess.__LINE__);

			// ��������� ������� ��������
			CTraffic::DecParam(array("EVENT" => 1), array(), false, $ar["DATE_ENTER_FULL"]);

			if (strlen($ar["SITE_ID"])>0)
			{
				$arFields = Array("C_EVENTS" => "C_EVENTS-1");
				$DB->Update("b_stat_day_site",$arFields,"WHERE SITE_ID = '".$DB->ForSql($ar["SITE_ID"], 2)."' and DATE_STAT = trunc(TO_DATE('".ConvertDateTime($ar["DATE_ENTER"], "D.M.Y")."','dd.mm.yyyy'))", $err_mess.__LINE__);

				// ��������� ������� ��������
				CTraffic::DecParam(array(), array("EVENT" => 1), $ar["SITE_ID"], $ar["DATE_ENTER_FULL"]);
			}

			$strSql = "DELETE FROM b_stat_event_list WHERE ID='$ID'";
			$DB->Query($strSql, false, $err_mess.__LINE__);

			$DB->Commit();
			return true;
		}
		return false;
	}
}
?>

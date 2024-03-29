CREATE TABLE b_timeman_entries
(
	ID number(18) NOT NULL,
	TIMESTAMP_X date DEFAULT SYSDATE NOT NULL,
	USER_ID number(18) NOT NULL,
	MODIFIED_BY number(18) NOT NULL,
	ACTIVE CHAR(1 CHAR) DEFAULT 'Y' NOT NULL,
	PAUSED CHAR(1 CHAR) DEFAULT 'N' NULL,
	DATE_START DATE,
	DATE_FINISH DATE,
	TIME_START number(6),
	TIME_FINISH number(6),
	DURATION number(6) default 0 null,
	TIME_LEAKS number(6) default 0 null,
	TASKS CLOB,
	IP_OPEN varchar(50 CHAR) default '' null,
	IP_CLOSE varchar(50 CHAR) default '' null,
	FORUM_TOPIC_ID number(18) null,
	CONSTRAINT PK_B_TIMEMAN_ENTRIES PRIMARY KEY (ID),
	CONSTRAINT fk_b_timeman_entries_user_id FOREIGN KEY (USER_ID) REFERENCES b_user(ID)
)
/
CREATE INDEX ix_b_timeman_entries_1 ON b_timeman_entries(USER_ID)
/
CREATE SEQUENCE sq_b_timeman_entries
/

CREATE OR REPLACE TRIGGER b_timeman_entries_insert
BEFORE INSERT
ON b_timeman_entries
FOR EACH ROW
BEGIN
	IF :NEW.ID IS NULL THEN
 		SELECT sq_b_timeman_entries.NEXTVAL INTO :NEW.ID FROM dual;
	END IF;
END;
/
CREATE OR REPLACE TRIGGER b_timeman_entries_update
BEFORE UPDATE
ON b_timeman_entries
REFERENCING OLD AS OLD NEW AS NEW
FOR EACH ROW
BEGIN
	IF :NEW.TIMESTAMP_X IS NOT NULL THEN
		:NEW.TIMESTAMP_X := SYSDATE;
	ELSE
		:NEW.TIMESTAMP_X := :OLD.TIMESTAMP_X;
	END IF;
END;
/

CREATE TABLE b_timeman_reports
(
	ID number(18) NOT NULL,
	TIMESTAMP_X date DEFAULT SYSDATE NOT NULL,
	ENTRY_ID number(18) not null,
	USER_ID number(18) not null,
	ACTIVE CHAR(1 CHAR) DEFAULT 'Y' NOT NULL,
	REPORT_TYPE varchar(50 CHAR) default 'REPORT' null,
	REPORT CLOB,
	CONSTRAINT PK_B_TIMEMAN_REPORTS PRIMARY KEY (ID),
	CONSTRAINT fk_b_timeman_reports_user_id FOREIGN KEY (USER_ID) REFERENCES b_user(ID),
	CONSTRAINT fk_b_timeman_reports_entry_id FOREIGN KEY (ENTRY_ID) REFERENCES b_timeman_entries(ID)
)
/
CREATE INDEX ix_b_timeman_reports_1 ON b_timeman_reports(ENTRY_ID, REPORT_TYPE, ACTIVE)
/
CREATE SEQUENCE sq_b_timeman_reports
/

CREATE OR REPLACE TRIGGER b_timeman_reports_insert
BEFORE INSERT
ON b_timeman_reports
FOR EACH ROW
BEGIN
	IF :NEW.ID IS NULL THEN
 		SELECT sq_b_timeman_reports.NEXTVAL INTO :NEW.ID FROM dual;
	END IF;
END;
/
CREATE OR REPLACE TRIGGER b_timeman_reports_update
BEFORE UPDATE
ON b_timeman_reports
REFERENCING OLD AS OLD NEW AS NEW
FOR EACH ROW
BEGIN
	IF :NEW.TIMESTAMP_X IS NOT NULL THEN
		:NEW.TIMESTAMP_X := SYSDATE;
	ELSE
		:NEW.TIMESTAMP_X := :OLD.TIMESTAMP_X;
	END IF;
END;
/

CREATE TABLE b_timeman_report_daily
(
	ID number(18) NOT NULL,
	TIMESTAMP_X date DEFAULT SYSDATE NOT NULL,
	ACTIVE CHAR(1 CHAR) DEFAULT 'Y' NOT NULL,
	USER_ID number(18) not null,
	ENTRY_ID number(18) not null,
	REPORT_DATE DATE,
	TASKS CLOB null,
	EVENTS CLOB null,
	REPORT CLOB null,
	MARK number(5) default 0 null,

	CONSTRAINT PK_B_TIMEMAN_REPORT_DAILY PRIMARY KEY (ID),
	CONSTRAINT fk_b_tm_report_daily_user_id FOREIGN KEY (USER_ID) REFERENCES b_user(ID),
	CONSTRAINT fk_b_tm_report_daily_entry_id FOREIGN KEY (ENTRY_ID) REFERENCES b_timeman_entries(ID)
)
/
CREATE SEQUENCE sq_b_timeman_report_daily
/

CREATE OR REPLACE TRIGGER b_timeman_report_daily_insert
BEFORE INSERT
ON b_timeman_report_daily
FOR EACH ROW
BEGIN
	IF :NEW.ID IS NULL THEN
 		SELECT sq_b_timeman_report_daily.NEXTVAL INTO :NEW.ID FROM dual;
	END IF;
END;
/
CREATE OR REPLACE TRIGGER b_timeman_report_daily_update
BEFORE UPDATE
ON b_timeman_report_daily
REFERENCING OLD AS OLD NEW AS NEW
FOR EACH ROW
BEGIN
	IF :NEW.TIMESTAMP_X IS NOT NULL THEN
		:NEW.TIMESTAMP_X := SYSDATE;
	ELSE
		:NEW.TIMESTAMP_X := :OLD.TIMESTAMP_X;
	END IF;
END;
/
CREATE TABLE b_timeman_report_full
(
	ID number(18) NOT NULL,
	TIMESTAMP_X date DEFAULT SYSDATE NOT NULL,
	ACTIVE CHAR(1 CHAR) DEFAULT 'Y' NOT NULL,
	USER_ID number(18) not null,
	REPORT_DATE DATE,
	DATE_FROM DATE,
	DATE_TO DATE,
	TASKS CLOB null,
	EVENTS CLOB null,
	FILES CLOB null,
	REPORT CLOB null,
	PLANS CLOB null,
	MARK CHAR(1 CHAR) default 'N' null,
	APPROVE CHAR(1 CHAR) default 'N' null,
	APPROVE_DATE DATE,
	APPROVER number(18) null,
	FORUM_TOPIC_ID number(18) null,
	PRIMARY KEY (ID)
)
/
CREATE SEQUENCE sq_b_timeman_report_full
/

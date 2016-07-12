CREATE TABLE B_LDAP_SERVER
(
	ID				int 			not null	IDENTITY (1, 1),
	TIMESTAMP_X		datetime		not null,
	NAME			varchar(255)	not null,
	DESCRIPTION		varchar(5000)	null,
	CODE			varchar(255)	null,
	ACTIVE			char(1)			not null,
	SERVER			varchar(255)	not null,
	PORT			int				not null,
	ADMIN_LOGIN		varchar(255)	not null,
	ADMIN_PASSWORD	varchar(255)	not null,
	BASE_DN			varchar(255)	not null,
	GROUP_FILTER	varchar(255)	not null,
	GROUP_ID_ATTR	varchar(255)	not null,
	GROUP_NAME_ATTR	varchar(255),
	GROUP_MEMBERS_ATTR	varchar(255),
	USER_FILTER 	varchar(255)	not null,
	USER_ID_ATTR	varchar(255)	not null,
	USER_NAME_ATTR	varchar(255),
	USER_LAST_NAME_ATTR	varchar(255),
	USER_EMAIL_ATTR	varchar(255),
	USER_GROUP_ATTR	varchar(255),
	USER_GROUP_ACCESSORY 	char(1)	null,
	USER_DEPARTMENT_ATTR varchar(255),
	USER_MANAGER_ATTR varchar(255),
	CONVERT_UTF8	char(1)	null,
	SYNC_PERIOD 	int,
	FIELD_MAP 		varchar(2000),
	ROOT_DEPARTMENT	int,
	DEFAULT_DEPARTMENT_NAME varchar(255),
	IMPORT_STRUCT 	char(1)	null,
	STRUCT_HAVE_DEFAULT	char(1),
	SYNC 			char(1),
	SYNC_ATTR 		varchar(255),
	SYNC_LAST 		datetime
)
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT PK_B_LDAP_SERVER PRIMARY KEY (ID)
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT DF_B_LDAP_SERVER_ACTIVE DEFAULT 'Y' FOR ACTIVE
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT DF_B_LDAP_SERVER_USER_GROUP_ACCESSORY DEFAULT 'N' FOR USER_GROUP_ACCESSORY
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT DF_B_LDAP_SERVER_CONVERT_UTF8 DEFAULT 'N' FOR CONVERT_UTF8
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT DF_B_LDAP_SERVER_PORT DEFAULT '389' FOR PORT
GO
ALTER TABLE B_LDAP_SERVER ADD CONSTRAINT DF_B_LDAP_SERVER_TIMESTAMP_X DEFAULT GETDATE() FOR TIMESTAMP_X
GO
create trigger B_LDAP_SERVER_UPDATE on B_LDAP_SERVER for update as
if (not update(TIMESTAMP_X))
begin
	UPDATE B_LDAP_SERVER SET
		TIMESTAMP_X = GETDATE()
	FROM
		B_LDAP_SERVER U,
		INSERTED I
	WHERE
		U.ID = I.ID
end
GO

CREATE TABLE B_LDAP_GROUP
(
	LDAP_SERVER_ID	int				not null,
	GROUP_ID		int				not null,
	LDAP_GROUP_ID	varchar(255)	not null
)
GO
ALTER TABLE B_LDAP_GROUP ADD CONSTRAINT PK_B_LDAP_GROUP PRIMARY KEY (LDAP_SERVER_ID, GROUP_ID, LDAP_GROUP_ID)
GO
SET @schema_name = DATABASE();

SET @enabled_column_exists = (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = @schema_name
		AND TABLE_NAME = 'sys_user'
		AND COLUMN_NAME = 'totp_enabled'
);
SET @statement = IF(
	@enabled_column_exists > 0,
	'SELECT 1',
	'ALTER TABLE `sys_user` ADD COLUMN `totp_enabled` TINYINT(1) NOT NULL DEFAULT 0'
);
PREPARE migration_statement FROM @statement;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @secret_column_exists = (
	SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE TABLE_SCHEMA = @schema_name
		AND TABLE_NAME = 'sys_user'
		AND COLUMN_NAME = 'u_totp_secret'
);
SET @statement = IF(
	@secret_column_exists > 0,
	'SELECT 1',
	'ALTER TABLE `sys_user` ADD COLUMN `u_totp_secret` VARCHAR(255) NULL'
);
PREPARE migration_statement FROM @statement;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

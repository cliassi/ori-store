-- Run after push_multidevice_logging_migration.sql on each existing database.
-- This is safe to rerun and leaves pre-queue log rows non-queued.
SET @push_schema = DATABASE();

SET @push_payload_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @push_schema
      AND table_name = 'push_delivery_log'
      AND column_name = 'payload'
);
SET @push_sql = IF(
    @push_payload_exists = 0,
    'ALTER TABLE push_delivery_log ADD COLUMN payload MEDIUMTEXT DEFAULT NULL AFTER message',
    'SELECT 1'
);
PREPARE push_stmt FROM @push_sql;
EXECUTE push_stmt;
DEALLOCATE PREPARE push_stmt;

SET @push_status_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = @push_schema
      AND table_name = 'push_delivery_log'
      AND column_name = 'status'
);
SET @push_sql = IF(
    @push_status_exists = 0,
    'ALTER TABLE push_delivery_log ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT ''logged'' AFTER outcome',
    'SELECT 1'
);
PREPARE push_stmt FROM @push_sql;
EXECUTE push_stmt;
DEALLOCATE PREPARE push_stmt;

SET @push_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = @push_schema
      AND table_name = 'push_delivery_log'
      AND index_name = 'idx_push_queue_status_id'
);
SET @push_sql = IF(
    @push_index_exists = 0,
    'ALTER TABLE push_delivery_log ADD INDEX idx_push_queue_status_id (status, id)',
    'SELECT 1'
);
PREPARE push_stmt FROM @push_sql;
EXECUTE push_stmt;
DEALLOCATE PREPARE push_stmt;

ALTER TABLE push_delivery_log ALTER COLUMN status SET DEFAULT 'logged';
